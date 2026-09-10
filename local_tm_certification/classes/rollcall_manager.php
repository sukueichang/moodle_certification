<?php
/**
 * Session rollcall (staff mark + student QR self check-in).
 * Independent from mod_attendance.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class rollcall_manager {

    public const STATUS_PRESENT = 'present';
    public const STATUS_LATE = 'late';
    public const STATUS_ABSENT = 'absent';

    public const SOURCE_STAFF = 'staff';
    public const SOURCE_SELF = 'self';

    public const WINDOW_PRESENT = 'present';
    public const WINDOW_LATE = 'late';
    public const WINDOW_CLOSED = 'closed';

    /**
     * @return string[] status => label
     */
    public static function status_options(): array {
        return [
            self::STATUS_PRESENT => get_string('rollcall_status_present', 'local_tm_certification'),
            self::STATUS_LATE => get_string('rollcall_status_late', 'local_tm_certification'),
            self::STATUS_ABSENT => get_string('rollcall_status_absent', 'local_tm_certification'),
        ];
    }

    public static function is_valid_status(string $status): bool {
        return isset(self::status_options()[$status]);
    }

    /**
     * Auto status for student self check-in from session times.
     * Before start → present; start..end → late; after end → closed (no write).
     */
    public static function self_checkin_window(\stdClass $session, ?int $now = null): string {
        $now = $now ?? time();
        $start = (int)$session->starttime;
        $end = (int)$session->endtime;
        if ($end > 0 && $now >= $end) {
            return self::WINDOW_CLOSED;
        }
        if ($start > 0 && $now >= $start) {
            return self::WINDOW_LATE;
        }
        return self::WINDOW_PRESENT;
    }

    /**
     * @return array<int,\stdClass> enrolid => rollcall row
     */
    public static function get_map_for_session(int $sessionid): array {
        global $DB;
        $rows = $DB->get_records('local_tm_cert_rollcall', ['sessionid' => $sessionid], '', '*');
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->enrolid] = $row;
        }
        return $map;
    }

    /**
     * @return array{present:int,late:int,absent:int,unknown:int,total:int}
     */
    public static function count_for_roster(int $sessionid, array $rosterrows): array {
        $map = self::get_map_for_session($sessionid);
        $counts = [
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'unknown' => 0,
            'total' => count($rosterrows),
        ];
        foreach ($rosterrows as $row) {
            $eid = (int)$row->id;
            if (!isset($map[$eid])) {
                $counts['unknown']++;
                continue;
            }
            $st = (string)$map[$eid]->status;
            if ($st === self::STATUS_PRESENT) {
                $counts['present']++;
            } else if ($st === self::STATUS_LATE) {
                $counts['late']++;
            } else if ($st === self::STATUS_ABSENT) {
                $counts['absent']++;
            } else {
                $counts['unknown']++;
            }
        }
        return $counts;
    }

    /**
     * Staff (or system) upsert. Always overwrites status/time.
     */
    public static function mark(
        int $sessionid,
        int $enrolid,
        string $status,
        int $markedby,
        string $source = self::SOURCE_STAFF
    ): \stdClass {
        global $DB;

        if (!self::is_valid_status($status)) {
            throw new \moodle_exception('error_rollcall_status_invalid', 'local_tm_certification');
        }

        require_once(__DIR__ . '/enrolment_manager.php');
        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        if ((int)$enrol->sessionid !== $sessionid) {
            throw new \moodle_exception('error_view_access_denied', 'local_tm_certification');
        }
        if ((int)$enrol->status === enrolment_manager::ENROL_CANCELLED) {
            throw new \moodle_exception('error_rollcall_enrol_cancelled', 'local_tm_certification');
        }

        $now = time();
        $existing = $DB->get_record('local_tm_cert_rollcall', [
            'sessionid' => $sessionid,
            'enrolid' => $enrolid,
        ]);

        if ($existing) {
            $existing->status = $status;
            $existing->source = $source;
            $existing->timemarked = $now;
            $existing->markedby = $markedby;
            $existing->timemodified = $now;
            $DB->update_record('local_tm_cert_rollcall', $existing);
            return $existing;
        }

        $rec = (object)[
            'sessionid' => $sessionid,
            'enrolid' => $enrolid,
            'userid' => (int)$enrol->userid,
            'status' => $status,
            'source' => $source,
            'timemarked' => $now,
            'markedby' => $markedby,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $rec->id = $DB->insert_record('local_tm_cert_rollcall', $rec);
        return $rec;
    }

    /**
     * Mark every active roster row present (staff).
     */
    public static function mark_all_present(int $sessionid, int $markedby): int {
        require_once(__DIR__ . '/enrolment_manager.php');
        $n = 0;
        foreach (enrolment_manager::get_session_roster($sessionid) as $row) {
            self::mark($sessionid, (int)$row->id, self::STATUS_PRESENT, $markedby, self::SOURCE_STAFF);
            $n++;
        }
        return $n;
    }

    /**
     * Ensure session has a check-in token; return it.
     */
    public static function ensure_checkin_token(int $sessionid): string {
        global $DB;
        $session = $DB->get_record('local_tm_cert_sessions', ['id' => $sessionid], 'id,checkintoken', MUST_EXIST);
        $token = trim((string)($session->checkintoken ?? ''));
        if ($token !== '') {
            return $token;
        }
        return self::regenerate_checkin_token($sessionid);
    }

    public static function regenerate_checkin_token(int $sessionid): string {
        global $DB;
        $token = bin2hex(random_bytes(16));
        $DB->set_field('local_tm_cert_sessions', 'checkintoken', $token, ['id' => $sessionid]);
        $DB->set_field('local_tm_cert_sessions', 'timemodified', time(), ['id' => $sessionid]);
        return $token;
    }

    public static function get_checkin_url(int $sessionid, ?string $token = null): \moodle_url {
        if ($token === null) {
            $token = self::ensure_checkin_token($sessionid);
        }
        return new \moodle_url('/local/tm_certification/checkin.php', [
            'sessionid' => $sessionid,
            't' => $token,
        ]);
    }

    public static function validate_checkin_token(int $sessionid, string $token): bool {
        global $DB;
        $token = trim($token);
        if ($token === '') {
            return false;
        }
        $stored = (string)$DB->get_field('local_tm_cert_sessions', 'checkintoken', ['id' => $sessionid]);
        return $stored !== '' && hash_equals($stored, $token);
    }

    /**
     * Active enrol row for user in session, or null.
     */
    public static function get_active_enrol(int $sessionid, int $userid): ?\stdClass {
        global $DB;
        require_once(__DIR__ . '/enrolment_manager.php');
        $enrol = $DB->get_record_select(
            'local_tm_cert_enrols',
            'sessionid = :sessionid AND userid = :userid AND status <> :cancelled',
            [
                'sessionid' => $sessionid,
                'userid' => $userid,
                'cancelled' => enrolment_manager::ENROL_CANCELLED,
            ]
        );
        return $enrol ?: null;
    }

    /**
     * Student self check-in. Does not overwrite an existing mark.
     *
     * @return array{
     *   ok:bool,
     *   code:string,
     *   status:?string,
     *   row:?\stdClass,
     *   window:string
     * }
     */
    public static function self_checkin(int $sessionid, int $userid, string $token): array {
        require_once(__DIR__ . '/session_manager.php');

        $session = session_manager::get_session($sessionid);
        $window = self::self_checkin_window($session);

        if (!self::validate_checkin_token($sessionid, $token)) {
            return ['ok' => false, 'code' => 'bad_token', 'status' => null, 'row' => null, 'window' => $window];
        }

        $enrol = self::get_active_enrol($sessionid, $userid);
        if (!$enrol) {
            return ['ok' => false, 'code' => 'not_roster', 'status' => null, 'row' => null, 'window' => $window];
        }

        global $DB;
        $existing = $DB->get_record('local_tm_cert_rollcall', [
            'sessionid' => $sessionid,
            'enrolid' => (int)$enrol->id,
        ]);
        if ($existing) {
            return [
                'ok' => true,
                'code' => 'already',
                'status' => (string)$existing->status,
                'row' => $existing,
                'window' => $window,
            ];
        }

        if ($window === self::WINDOW_CLOSED) {
            return ['ok' => false, 'code' => 'closed', 'status' => null, 'row' => null, 'window' => $window];
        }

        $status = ($window === self::WINDOW_LATE) ? self::STATUS_LATE : self::STATUS_PRESENT;
        $row = self::mark($sessionid, (int)$enrol->id, $status, $userid, self::SOURCE_SELF);
        return [
            'ok' => true,
            'code' => 'marked',
            'status' => $status,
            'row' => $row,
            'window' => $window,
        ];
    }

    /**
     * Generate QR PNG bytes for a URL (TCPDF if available).
     */
    public static function qr_png_data(string $text, int $pixels = 6): ?string {
        global $CFG;
        $path = $CFG->libdir . '/tcpdf/tcpdf_barcodes_2d.php';
        if (!is_readable($path)) {
            return null;
        }
        require_once($path);
        if (!class_exists('TCPDF2DBarcode')) {
            return null;
        }
        try {
            $barcode = new \TCPDF2DBarcode($text, 'QRCODE,M');
            $png = $barcode->getBarcodePngData($pixels, $pixels, [0, 0, 0]);
            return is_string($png) && $png !== '' ? $png : null;
        } catch (\Throwable $e) {
            debugging('rollcall QR png failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * External fallback image URL when TCPDF QR is unavailable.
     */
    public static function qr_fallback_img_url(string $text, int $size = 240): string {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . (int)$size . 'x' . (int)$size
            . '&data=' . rawurlencode($text);
    }

    public static function format_meta_line(?\stdClass $row): string {
        if (!$row) {
            return get_string('rollcall_status_unknown', 'local_tm_certification');
        }
        $when = userdate((int)$row->timemarked, get_string('strftimetime', 'langconfig'));
        if ((string)$row->source === self::SOURCE_SELF) {
            return $when . ' · ' . get_string('rollcall_source_self', 'local_tm_certification');
        }
        // fullname() needs all configured name fields (not just first/last).
        $marker = \core_user::get_user((int)$row->markedby, '*', IGNORE_MISSING);
        $name = $marker ? fullname($marker) : get_string('rollcall_source_staff', 'local_tm_certification');
        return $when . ' · ' . $name;
    }
}
