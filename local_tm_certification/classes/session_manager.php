<?php
/**
 * Certification session CRUD.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/catalog_manager.php');

class session_manager {

    /** Session still running (accepts new examinees until endtime). */
    public const STATUS_OPEN = 1;
    /** Session finished — visible to instructor, no new examinees. */
    public const STATUS_ENDED = 0;
    /** @deprecated Use STATUS_ENDED */
    public const STATUS_CLOSED = 0;

    public const LEVEL_BASIC = 'basic';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_ADVANCED = 'advanced';

    /**
     * @return string[]
     */
    public static function level_options(): array {
        return [
            self::LEVEL_BASIC => get_string('level_basic', 'local_tm_certification'),
            self::LEVEL_INTERMEDIATE => get_string('level_intermediate', 'local_tm_certification'),
            self::LEVEL_ADVANCED => get_string('level_advanced', 'local_tm_certification'),
        ];
    }

    /**
     * @return array<int,string>
     */
    public static function status_options(): array {
        return [
            self::STATUS_OPEN => get_string('session_status_open', 'local_tm_certification'),
            self::STATUS_ENDED => get_string('session_status_ended', 'local_tm_certification'),
        ];
    }

    /**
     * Persist auto-end when endtime has passed (immediate effect).
     */
    public static function sync_auto_end(\stdClass $session): \stdClass {
        global $DB;
        if ((int)$session->status === self::STATUS_OPEN
            && (int)$session->endtime > 0
            && (int)$session->endtime <= time()) {
            $upd = new \stdClass();
            $upd->id = (int)$session->id;
            $upd->status = self::STATUS_ENDED;
            $upd->timemodified = time();
            $DB->update_record('local_tm_cert_sessions', $upd);
            $session->status = self::STATUS_ENDED;
        }
        return $session;
    }

    /**
     * Effective ended: marked ended, or endtime already passed.
     */
    public static function is_ended(\stdClass $session): bool {
        $session = self::sync_auto_end($session);
        if ((int)$session->status === self::STATUS_ENDED) {
            return true;
        }
        return (int)$session->endtime > 0 && (int)$session->endtime <= time();
    }

    /**
     * Whether examinees may still be added (manual / batch).
     */
    public static function can_add_examinees(\stdClass $session): bool {
        return !self::is_ended($session);
    }

    public static function status_label(\stdClass $session): string {
        return self::is_ended($session)
            ? get_string('session_status_ended', 'local_tm_certification')
            : get_string('session_status_open', 'local_tm_certification');
    }

    public static function build_session_name(string $level): string {
        $label = self::level_options()[$level] ?? $level;
        return '[' . $label . ']' . get_string('session_name_suffix', 'local_tm_certification');
    }

    /**
     * @param array $data
     * @return int new session id
     */
    public static function create_session(array $data): int {
        global $DB, $USER;

        self::validate_session_data($data, true);

        $level = (string)$data['certlevel'];
        $now = time();
        $record = new \stdClass();
        $record->courseid = catalog_manager::get_moodle_courseid_for_level($level);
        $record->name = self::build_session_name($level);
        $record->certlevel = $level;
        $record->venueid = !empty($data['venueid']) ? (int)$data['venueid'] : null;
        $record->venue = self::resolve_venue_name((int)($record->venueid ?? 0));
        $record->starttime = (int)$data['starttime'];
        $record->endtime = (int)$data['endtime'];
        $record->instructorid = (int)($data['instructorid'] ?? 0);
        $record->status = isset($data['status']) ? (int)$data['status'] : self::STATUS_OPEN;
        $record->attendancecmid = !empty($data['attendancecmid']) ? (int)$data['attendancecmid'] : null;
        $record->attendancesessid = null;
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->createdby = (int)($data['createdby'] ?? $USER->id);

        return (int)$DB->insert_record('local_tm_cert_sessions', $record);
    }

    /**
     * @param int $id
     * @param array $data
     */
    public static function update_session(int $id, array $data): void {
        global $DB;

        $existing = self::get_session($id);
        self::validate_session_data($data, false, $existing);

        $record = new \stdClass();
        $record->id = $id;
        $currentlevel = (string)$existing->certlevel;
        $newlevel = array_key_exists('certlevel', $data) ? (string)$data['certlevel'] : $currentlevel;

        if ($newlevel !== $currentlevel) {
            $record->certlevel = $newlevel;
            $record->name = self::build_session_name($newlevel);
            $record->courseid = catalog_manager::get_moodle_courseid_for_level($newlevel);
        }

        if (array_key_exists('venueid', $data)) {
            $record->venueid = (int)$data['venueid'];
            $record->venue = self::resolve_venue_name((int)$record->venueid);
        }
        if (array_key_exists('starttime', $data)) {
            $record->starttime = (int)$data['starttime'];
        }
        if (array_key_exists('endtime', $data)) {
            $record->endtime = (int)$data['endtime'];
        }
        if (array_key_exists('instructorid', $data)) {
            $record->instructorid = (int)$data['instructorid'];
        }
        if (array_key_exists('status', $data)) {
            $record->status = (int)$data['status'];
            // Admin set back to in-progress but endtime already past → extend one day.
            if ((int)$record->status === self::STATUS_OPEN) {
                $end = array_key_exists('endtime', $data)
                    ? (int)$data['endtime']
                    : (int)$existing->endtime;
                if ($end <= time()) {
                    $record->endtime = time() + DAYSECS;
                }
            }
        }
        $record->timemodified = time();

        $DB->update_record('local_tm_cert_sessions', $record);
    }

    public static function get_session(int $id): \stdClass {
        global $DB;
        $session = $DB->get_record('local_tm_cert_sessions', ['id' => $id], '*', MUST_EXIST);
        return self::sync_auto_end($session);
    }

    /**
     * Effective Moodle course for a session: stored courseid, or current level mapping.
     * Backfills session.courseid when mapping exists and session still has 0.
     */
    public static function resolve_courseid(\stdClass $session, bool $backfill = true): int {
        global $DB;

        $courseid = (int)($session->courseid ?? 0);
        if ($courseid > 0) {
            return $courseid;
        }
        $mapped = catalog_manager::get_moodle_courseid_for_level((string)$session->certlevel);
        if ($mapped <= 0) {
            return 0;
        }
        if ($backfill && !empty($session->id)) {
            $upd = new \stdClass();
            $upd->id = (int)$session->id;
            $upd->courseid = $mapped;
            $upd->timemodified = time();
            $DB->update_record('local_tm_cert_sessions', $upd);
            $session->courseid = $mapped;
        }
        return $mapped;
    }

    /**
     * @param array $filters
     * @return \stdClass[]
     */
    public static function get_sessions(array $filters = []): array {
        global $DB;

        $where = ['1=1'];
        $params = [];

        if (isset($filters['status'])) {
            $where[] = 's.status = :status';
            $params['status'] = (int)$filters['status'];
        }
        if (!empty($filters['instructorid'])) {
            $where[] = 's.instructorid = :instructorid';
            $params['instructorid'] = (int)$filters['instructorid'];
        }
        if (!empty($filters['from'])) {
            $where[] = 's.starttime >= :fromts';
            $params['fromts'] = (int)$filters['from'];
        }

        $sql = "SELECT s.*, v.name AS venuename, v.venuecode AS venuecode
                  FROM {local_tm_cert_sessions} s
             LEFT JOIN {local_tm_cert_venues} v ON v.id = s.venueid
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY s.starttime ASC, s.id ASC";
        $rows = array_values($DB->get_records_sql($sql, $params));
        foreach ($rows as $i => $row) {
            $rows[$i] = self::sync_auto_end($row);
        }
        // Optional: after auto-end, drop rows that no longer match status filter.
        if (isset($filters['status'])) {
            $want = (int)$filters['status'];
            $rows = array_values(array_filter($rows, static function(\stdClass $s) use ($want): bool {
                return (int)$s->status === $want;
            }));
        }
        return $rows;
    }

    /**
     * Count examinees on a session.
     * Default: current active roster only (excludes cancelled removals).
     *
     * @param int|null $status null = active only; pass a status constant to filter exactly
     */
    public static function count_enrols(int $sessionid, ?int $status = null): int {
        global $DB;
        require_once(__DIR__ . '/enrolment_manager.php');

        if ($status === null) {
            return (int)$DB->count_records_select(
                'local_tm_cert_enrols',
                'sessionid = :sessionid AND status <> :cancelled',
                [
                    'sessionid' => $sessionid,
                    'cancelled' => enrolment_manager::ENROL_CANCELLED,
                ]
            );
        }
        return (int)$DB->count_records('local_tm_cert_enrols', [
            'sessionid' => $sessionid,
            'status' => $status,
        ]);
    }

    /**
     * Mark session ended (manual). Auto-end also runs when endtime passes.
     */
    public static function end_session(int $id): void {
        global $DB;
        $record = new \stdClass();
        $record->id = $id;
        $record->status = self::STATUS_ENDED;
        $record->timemodified = time();
        $DB->update_record('local_tm_cert_sessions', $record);
    }

    /**
     * @deprecated Use end_session()
     */
    public static function close_session(int $id): void {
        self::end_session($id);
    }

    /**
     * Admin-only: reopen an ended session.
     * If endtime already passed, extend endtime by one day so auto-end does not flip it back immediately.
     */
    public static function reopen_session(int $id): void {
        global $DB;
        $session = $DB->get_record('local_tm_cert_sessions', ['id' => $id], '*', MUST_EXIST);
        $record = new \stdClass();
        $record->id = $id;
        $record->status = self::STATUS_OPEN;
        if ((int)$session->endtime <= time()) {
            $record->endtime = time() + DAYSECS;
        }
        $record->timemodified = time();
        $DB->update_record('local_tm_cert_sessions', $record);
    }

    /**
     * Admin-only: permanently delete session + roster rows.
     */
    public static function delete_session(int $id): void {
        global $DB;
        $DB->delete_records('local_tm_cert_enrols', ['sessionid' => $id]);
        $DB->delete_records('local_tm_cert_sessions', ['id' => $id]);
    }

    /**
     * @param array $data
     * @param bool $isnew
     * @param \stdClass|null $existing
     */
    public static function validate_session_data(array $data, bool $isnew = false, ?\stdClass $existing = null): void {
        $level = $existing ? (string)$existing->certlevel : '';
        if ($isnew || array_key_exists('certlevel', $data)) {
            $level = (string)($data['certlevel'] ?? '');
            if (!array_key_exists($level, self::level_options())) {
                throw new \moodle_exception('error_session_level_invalid', 'local_tm_certification');
            }
        }

        if ($isnew || array_key_exists('starttime', $data) || array_key_exists('endtime', $data)) {
            $start = (int)($data['starttime'] ?? ($existing->starttime ?? 0));
            $end = (int)($data['endtime'] ?? ($existing->endtime ?? 0));
            if ($start <= 0 || $end <= 0 || $end <= $start) {
                throw new \moodle_exception('error_session_time_invalid', 'local_tm_certification');
            }
        }

        $venueid = (int)($data['venueid'] ?? ($existing->venueid ?? 0));
        if ($venueid <= 0) {
            throw new \moodle_exception('error_session_venue_required', 'local_tm_certification');
        }
        $venue = catalog_manager::get_venue($venueid);
        if (!$venue || !catalog_manager::venue_matches_level($venueid, $level)) {
            throw new \moodle_exception('error_session_venue_level_mismatch', 'local_tm_certification');
        }

        $instructorid = (int)($data['instructorid'] ?? ($existing->instructorid ?? 0));
        if ($instructorid <= 0) {
            throw new \moodle_exception('error_session_instructor_required', 'local_tm_certification');
        }
        // Expiry / qualification gate only when creating or changing instructor / level
        // (not when editing unrelated fields on an already-assigned session).
        $checkinstructorgate = $isnew
            || array_key_exists('instructorid', $data)
            || array_key_exists('certlevel', $data);
        if ($checkinstructorgate) {
            if (!catalog_manager::instructor_qualified_for_level($instructorid, $level)) {
                throw new \moodle_exception('error_session_instructor_level_mismatch', 'local_tm_certification');
            }
            $lvlstatus = catalog_manager::get_instructor_level_status($instructorid, $level);
            if ($lvlstatus['status'] === catalog_manager::INSTR_STATUS_EXPIRED) {
                throw new \moodle_exception('error_session_instructor_expired', 'local_tm_certification');
            }
        }
    }

    private static function resolve_venue_name(int $venueid): ?string {
        if ($venueid <= 0) {
            return null;
        }
        $venue = catalog_manager::get_venue($venueid);
        if (!$venue) {
            return null;
        }
        $code = trim((string)($venue->venuecode ?? ''));
        return $code !== '' ? ($code . ' - ' . (string)$venue->name) : (string)$venue->name;
    }
}
