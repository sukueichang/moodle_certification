<?php
/**
 * Course-opening application submit / review.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/catalog_manager.php');
require_once(__DIR__ . '/session_manager.php');
require_once(__DIR__ . '/attendance_helper.php');
require_once(__DIR__ . '/permissions_manager.php');

class application_manager {

    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    /**
     * @return string[]
     */
    public static function status_options(): array {
        return [
            self::STATUS_PENDING => get_string('app_status_pending', 'local_tm_certification'),
            self::STATUS_APPROVED => get_string('app_status_approved', 'local_tm_certification'),
            self::STATUS_REJECTED => get_string('app_status_rejected', 'local_tm_certification'),
        ];
    }

    /**
     * Submit a new application (instructor).
     *
     * @param array $data
     * @return int application id
     */
    public static function submit(array $data): int {
        global $DB, $USER;

        $level = (string)($data['certlevel'] ?? '');
        $instructorid = (int)($data['instructorid'] ?? $USER->id);
        $venueid = (int)($data['venueid'] ?? 0);
        $starttime = (int)($data['starttime'] ?? 0);
        $endtime = (int)($data['endtime'] ?? 0);
        $applicantnote = trim((string)($data['applicantnote'] ?? ''));
        $applicantid = (int)($data['applicantid'] ?? $USER->id);

        if (!array_key_exists($level, session_manager::level_options())) {
            throw new \moodle_exception('error_session_level_invalid', 'local_tm_certification');
        }
        if ($endtime <= $starttime) {
            throw new \moodle_exception('error_session_time_invalid', 'local_tm_certification');
        }
        if ($venueid <= 0) {
            throw new \moodle_exception('error_app_venue_required', 'local_tm_certification');
        }
        $venue = catalog_manager::get_venue($venueid);
        if (!$venue || !catalog_manager::venue_matches_level($venueid, $level)) {
            throw new \moodle_exception('error_app_venue_invalid', 'local_tm_certification');
        }
        if (!catalog_manager::instructor_qualified_for_level($instructorid, $level)) {
            throw new \moodle_exception('error_app_instructor_not_qualified', 'local_tm_certification');
        }
        $lvlstatus = catalog_manager::get_instructor_level_status($instructorid, $level);
        if ($lvlstatus['status'] === catalog_manager::INSTR_STATUS_EXPIRED) {
            throw new \moodle_exception('error_app_instructor_expired', 'local_tm_certification');
        }
        $applicant = $DB->get_record('user', ['id' => $applicantid, 'deleted' => 0]);
        if (!$applicant) {
            throw new \moodle_exception('error_view_access_denied', 'local_tm_certification');
        }
        $applicantok = catalog_manager::instructor_qualified_for_level($applicantid, $level)
            || permissions_manager::user_can_manage($applicant)
            || permissions_manager::user_can_approve($applicant);
        if (!$applicantok) {
            throw new \moodle_exception('error_app_applicant_not_qualified', 'local_tm_certification');
        }
        if (catalog_manager::get_moodle_courseid_for_level($level) <= 0) {
            throw new \moodle_exception('error_app_course_unmapped', 'local_tm_certification');
        }

        $now = time();
        $record = new \stdClass();
        $record->applicantid = $applicantid;
        $record->instructorid = $instructorid;
        $record->certlevel = $level;
        $record->venueid = $venueid;
        $record->starttime = $starttime;
        $record->endtime = $endtime;
        $record->status = self::STATUS_PENDING;
        $record->sessionid = null;
        $record->applicantnote = $applicantnote !== '' ? $applicantnote : null;
        $record->managernote = null;
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->timesubmitted = $now;
        $record->timereviewed = null;
        $record->reviewedby = 0;

        return (int)$DB->insert_record('local_tm_cert_apps', $record);
    }

    public static function get_application(int $id): \stdClass {
        global $DB;

        $sql = 'SELECT a.*, v.name AS venuename, v.venuecode,
                       ua.firstname AS applicant_firstname, ua.lastname AS applicant_lastname,
                       ua.middlename AS applicant_middlename, ua.alternatename AS applicant_alternatename,
                       ua.firstnamephonetic AS applicant_firstnamephonetic,
                       ua.lastnamephonetic AS applicant_lastnamephonetic,
                       ui.firstname AS instructor_firstname, ui.lastname AS instructor_lastname,
                       ui.middlename AS instructor_middlename, ui.alternatename AS instructor_alternatename,
                       ui.firstnamephonetic AS instructor_firstnamephonetic,
                       ui.lastnamephonetic AS instructor_lastnamephonetic
                  FROM {local_tm_cert_apps} a
             LEFT JOIN {local_tm_cert_venues} v ON v.id = a.venueid
             LEFT JOIN {user} ua ON ua.id = a.applicantid
             LEFT JOIN {user} ui ON ui.id = a.instructorid
                 WHERE a.id = :id';
        $row = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);
        $row->applicant = (object)[
            'id' => (int)$row->applicantid,
            'firstname' => $row->applicant_firstname ?? '',
            'lastname' => $row->applicant_lastname ?? '',
            'middlename' => $row->applicant_middlename ?? '',
            'alternatename' => $row->applicant_alternatename ?? '',
            'firstnamephonetic' => $row->applicant_firstnamephonetic ?? '',
            'lastnamephonetic' => $row->applicant_lastnamephonetic ?? '',
        ];
        $row->instructor = (object)[
            'id' => (int)$row->instructorid,
            'firstname' => $row->instructor_firstname ?? '',
            'lastname' => $row->instructor_lastname ?? '',
            'middlename' => $row->instructor_middlename ?? '',
            'alternatename' => $row->instructor_alternatename ?? '',
            'firstnamephonetic' => $row->instructor_firstnamephonetic ?? '',
            'lastnamephonetic' => $row->instructor_lastnamephonetic ?? '',
        ];
        return $row;
    }

    /**
     * @param array $filters keys: status, applicantid, instructorid
     * @return \stdClass[]
     */
    public static function get_applications(array $filters = []): array {
        global $DB;

        $params = [];
        $where = ['1=1'];
        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $where[] = 'a.status = :status';
            $params['status'] = (int)$filters['status'];
        }
        if (!empty($filters['applicantid'])) {
            $where[] = 'a.applicantid = :applicantid';
            $params['applicantid'] = (int)$filters['applicantid'];
        }
        if (!empty($filters['instructorid'])) {
            $where[] = 'a.instructorid = :instructorid';
            $params['instructorid'] = (int)$filters['instructorid'];
        }

        $sql = 'SELECT a.*, v.name AS venuename, v.venuecode,
                       ua.firstname AS applicant_firstname, ua.lastname AS applicant_lastname,
                       ua.middlename AS applicant_middlename, ua.alternatename AS applicant_alternatename,
                       ua.firstnamephonetic AS applicant_firstnamephonetic,
                       ua.lastnamephonetic AS applicant_lastnamephonetic,
                       ui.firstname AS instructor_firstname, ui.lastname AS instructor_lastname,
                       ui.middlename AS instructor_middlename, ui.alternatename AS instructor_alternatename,
                       ui.firstnamephonetic AS instructor_firstnamephonetic,
                       ui.lastnamephonetic AS instructor_lastnamephonetic
                  FROM {local_tm_cert_apps} a
             LEFT JOIN {local_tm_cert_venues} v ON v.id = a.venueid
             LEFT JOIN {user} ua ON ua.id = a.applicantid
             LEFT JOIN {user} ui ON ui.id = a.instructorid
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY a.timesubmitted DESC, a.id DESC';

        $rows = $DB->get_records_sql($sql, $params);
        foreach ($rows as $row) {
            $row->applicant = (object)[
                'id' => (int)$row->applicantid,
                'firstname' => $row->applicant_firstname ?? '',
                'lastname' => $row->applicant_lastname ?? '',
                'middlename' => $row->applicant_middlename ?? '',
                'alternatename' => $row->applicant_alternatename ?? '',
                'firstnamephonetic' => $row->applicant_firstnamephonetic ?? '',
                'lastnamephonetic' => $row->applicant_lastnamephonetic ?? '',
            ];
            $row->instructor = (object)[
                'id' => (int)$row->instructorid,
                'firstname' => $row->instructor_firstname ?? '',
                'lastname' => $row->instructor_lastname ?? '',
                'middlename' => $row->instructor_middlename ?? '',
                'alternatename' => $row->instructor_alternatename ?? '',
                'firstnamephonetic' => $row->instructor_firstnamephonetic ?? '',
                'lastnamephonetic' => $row->instructor_lastnamephonetic ?? '',
            ];
        }
        return array_values($rows);
    }

    /**
     * Approve application: create session + optional attendance slot.
     *
     * @return int created session id
     */
    public static function approve(int $appid, string $managernote = ''): int {
        global $DB, $USER;

        $app = self::get_application($appid);
        if ((int)$app->status !== self::STATUS_PENDING) {
            throw new \moodle_exception('error_app_not_pending', 'local_tm_certification');
        }

        $transaction = $DB->start_delegated_transaction();

        $sessionid = session_manager::create_session([
            'certlevel' => $app->certlevel,
            'venueid' => (int)$app->venueid,
            'starttime' => (int)$app->starttime,
            'endtime' => (int)$app->endtime,
            'instructorid' => (int)$app->instructorid,
            'status' => session_manager::STATUS_OPEN,
            'createdby' => (int)$USER->id,
            'attendancecmid' => catalog_manager::get_attendance_cmid_for_level((string)$app->certlevel),
        ]);

        $att = attendance_helper::ensure_attendance_slot_for_session($sessionid);
        if (!empty($att['warning'])) {
            debugging('local_tm_certification approve attendance: ' . $att['warning'], DEBUG_DEVELOPER);
        }

        // Applications no longer carry a roster; discard any legacy draft learners.
        $DB->delete_records('local_tm_cert_app_learners', ['applicationid' => $appid]);

        $now = time();
        $upd = new \stdClass();
        $upd->id = $appid;
        $upd->status = self::STATUS_APPROVED;
        $upd->sessionid = $sessionid;
        $upd->managernote = trim($managernote) !== '' ? trim($managernote) : null;
        $upd->timemodified = $now;
        $upd->timereviewed = $now;
        $upd->reviewedby = (int)$USER->id;
        $DB->update_record('local_tm_cert_apps', $upd);

        $transaction->allow_commit();

        require_once(__DIR__ . '/notification_helper.php');
        notification_helper::notify_application_approved($appid, $sessionid);

        return $sessionid;
    }

    public static function reject(int $appid, string $managernote = ''): void {
        global $DB, $USER;

        $app = self::get_application($appid);
        if ((int)$app->status !== self::STATUS_PENDING) {
            throw new \moodle_exception('error_app_not_pending', 'local_tm_certification');
        }

        $now = time();
        $upd = new \stdClass();
        $upd->id = $appid;
        $upd->status = self::STATUS_REJECTED;
        $upd->managernote = trim($managernote) !== '' ? trim($managernote) : null;
        $upd->timemodified = $now;
        $upd->timereviewed = $now;
        $upd->reviewedby = (int)$USER->id;
        $DB->update_record('local_tm_cert_apps', $upd);

        // Discard any legacy draft learners (applications no longer carry a roster).
        $DB->delete_records('local_tm_cert_app_learners', ['applicationid' => $appid]);

        require_once(__DIR__ . '/notification_helper.php');
        notification_helper::notify_application_rejected($appid);
    }

    /**
     * Levels the given user may apply for (qualified instructor levels).
     *
     * @return array<string,string>
     */
    public static function applyable_levels_for_user(int $userid): array {
        $all = session_manager::level_options();
        $out = [];
        foreach ($all as $level => $label) {
            if (!catalog_manager::instructor_qualified_for_level($userid, $level)) {
                continue;
            }
            $st = catalog_manager::get_instructor_level_status($userid, $level);
            if ($st['status'] === catalog_manager::INSTR_STATUS_EXPIRED) {
                continue;
            }
            $out[$level] = $label;
        }
        return $out;
    }
}
