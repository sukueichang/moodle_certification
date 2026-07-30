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

    public const STATUS_OPEN = 1;
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
        }
        $record->timemodified = time();

        $DB->update_record('local_tm_cert_sessions', $record);
    }

    public static function get_session(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_tm_cert_sessions', ['id' => $id], '*', MUST_EXIST);
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
        return array_values($DB->get_records_sql($sql, $params));
    }

    public static function count_enrols(int $sessionid, ?int $status = null): int {
        global $DB;
        $params = ['sessionid' => $sessionid];
        if ($status === null) {
            return (int)$DB->count_records('local_tm_cert_enrols', $params);
        }
        $params['status'] = $status;
        return (int)$DB->count_records('local_tm_cert_enrols', $params);
    }

    public static function close_session(int $id): void {
        self::update_session($id, ['status' => self::STATUS_CLOSED]);
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
        if (!$venue || (int)$venue->active !== 1 || (string)$venue->certlevel !== $level) {
            throw new \moodle_exception('error_session_venue_level_mismatch', 'local_tm_certification');
        }

        $instructorid = (int)($data['instructorid'] ?? ($existing->instructorid ?? 0));
        if ($instructorid <= 0) {
            throw new \moodle_exception('error_session_instructor_required', 'local_tm_certification');
        }
        if (!catalog_manager::instructor_qualified_for_level($instructorid, $level)) {
            throw new \moodle_exception('error_session_instructor_level_mismatch', 'local_tm_certification');
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
