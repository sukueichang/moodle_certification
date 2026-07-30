<?php
/**
 * Examinee provision, session roster, Moodle course enrol sync.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class enrolment_manager {

    public const ENROL_PENDING = 0;
    public const ENROL_APPROVED = 1;
    public const ENROL_CANCELLED = 2;

    /**
     * Find or create Moodle user by email (batch-style provision).
     *
     * @return array{userid:int,created:bool,linked:bool,email:string,initial_password:string}
     */
    public static function provision_or_link_user(
        string $email,
        string $firstname,
        string $lastname,
        string $institution = ''
    ): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $email = \core_text::strtolower(trim($email));
        $firstname = clean_param(trim($firstname), PARAM_TEXT);
        $lastname = clean_param(trim($lastname), PARAM_TEXT);
        $institution = clean_param(trim($institution), PARAM_TEXT);

        if ($email === '' || !validate_email($email)) {
            throw new \moodle_exception('error_batch_email_invalid', 'local_tm_certification');
        }
        if ($firstname === '' || $lastname === '') {
            throw new \moodle_exception('error_batch_name_required', 'local_tm_certification');
        }

        $existing = $DB->get_record('user', [
            'email' => $email,
            'deleted' => 0,
            'mnethostid' => $CFG->mnet_localhost_id,
        ], 'id,institution', IGNORE_MULTIPLE);
        if ($existing) {
            if ($institution !== '' && trim((string)$existing->institution) === '') {
                $DB->set_field('user', 'institution', $institution, ['id' => (int)$existing->id]);
            }
            return [
                'userid' => (int)$existing->id,
                'created' => false,
                'linked' => true,
                'email' => $email,
                'initial_password' => '',
            ];
        }

        $username = self::generate_username($email);
        $plainsecret = self::generate_initial_password();
        $now = time();
        $newuser = new \stdClass();
        $newuser->auth = 'manual';
        $newuser->confirmed = 1;
        $newuser->mnethostid = $CFG->mnet_localhost_id;
        $newuser->username = $username;
        $newuser->password = \hash_internal_user_password($plainsecret);
        $newuser->firstname = $firstname;
        $newuser->lastname = $lastname;
        $newuser->email = $email;
        $newuser->institution = $institution;
        $newuser->country = 'TW';
        $newuser->lang = $CFG->lang;
        $newuser->timezone = '99';
        $newuser->maildisplay = 0;
        $newuser->autosubscribe = 0;
        $newuser->trackforums = 0;
        $newuser->timecreated = $now;
        $newuser->timemodified = $now;
        $newuserid = (int)\user_create_user($newuser, false, false);
        if ($newuserid < 2) {
            throw new \moodle_exception('error_batch_user_invalid', 'local_tm_certification');
        }

        $usertable = new \xmldb_table('user');
        $forcepwfield = new \xmldb_field('forcepasswordchange');
        if ($DB->get_manager()->field_exists($usertable, $forcepwfield)) {
            $DB->set_field('user', 'forcepasswordchange', 1, ['id' => $newuserid]);
        }

        return [
            'userid' => $newuserid,
            'created' => true,
            'linked' => false,
            'email' => $email,
            'initial_password' => $plainsecret,
        ];
    }

    /**
     * Add user to certification session roster and optionally sync Moodle course enrol.
     *
     * @return int enrolment row id
     */
    public static function add_to_session(
        int $sessionid,
        int $userid,
        int $addedby = 0,
        int $status = self::ENROL_APPROVED,
        bool $syncmoodle = true
    ): int {
        global $DB, $USER;

        $session = session_manager::get_session($sessionid);
        $existing = $DB->get_record('local_tm_cert_enrols', [
            'sessionid' => $sessionid,
            'userid' => $userid,
        ]);
        $now = time();
        if ($existing) {
            if ((int)$existing->status === self::ENROL_CANCELLED) {
                $existing->status = $status;
                $existing->timemodified = $now;
                $DB->update_record('local_tm_cert_enrols', $existing);
            }
            $enrolid = (int)$existing->id;
        } else {
            $row = new \stdClass();
            $row->sessionid = $sessionid;
            $row->userid = $userid;
            $row->status = $status;
            $row->practicalscore = null;
            $row->practicalpass = 0;
            $row->timecreated = $now;
            $row->timemodified = $now;
            $row->addedby = $addedby > 0 ? $addedby : (int)$USER->id;
            $enrolid = (int)$DB->insert_record('local_tm_cert_enrols', $row);
        }

        if ($syncmoodle && $status === self::ENROL_APPROVED && (int)$session->courseid > 0) {
            self::sync_moodle_enrolment($userid, (int)$session->courseid, 'enrol');
        }

        return $enrolid;
    }

    /**
     * Provision by email then add to session.
     *
     * @return array provision result + enrolid
     */
    public static function provision_and_add(
        int $sessionid,
        string $email,
        string $firstname,
        string $lastname,
        string $institution = '',
        int $addedby = 0
    ): array {
        $provision = self::provision_or_link_user($email, $firstname, $lastname, $institution);
        $enrolid = self::add_to_session($sessionid, (int)$provision['userid'], $addedby, self::ENROL_APPROVED, true);
        $provision['enrolid'] = $enrolid;
        return $provision;
    }

    /**
     * @return \stdClass[]
     */
    public static function get_session_roster(int $sessionid): array {
        global $DB;

        $sql = "SELECT e.*, u.email, u.institution,
                       u.firstname, u.lastname, u.middlename, u.alternatename,
                       u.firstnamephonetic, u.lastnamephonetic
                  FROM {local_tm_cert_enrols} e
                  JOIN {user} u ON u.id = e.userid
                 WHERE e.sessionid = :sessionid AND e.status <> :cancelled
              ORDER BY u.lastname ASC, u.firstname ASC, e.id ASC";
        return array_values($DB->get_records_sql($sql, [
            'sessionid' => $sessionid,
            'cancelled' => self::ENROL_CANCELLED,
        ]));
    }

    public static function cancel_enrolment(int $enrolid, bool $unenrolmoodle = false): void {
        global $DB;
        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        $enrol->status = self::ENROL_CANCELLED;
        $enrol->timemodified = time();
        $DB->update_record('local_tm_cert_enrols', $enrol);

        if ($unenrolmoodle) {
            $session = session_manager::get_session((int)$enrol->sessionid);
            if ((int)$session->courseid > 0) {
                self::sync_moodle_enrolment((int)$enrol->userid, (int)$session->courseid, 'unenrol');
            }
        }
    }

    /**
     * Enrol or unenrol via manual enrol plugin.
     */
    public static function sync_moodle_enrolment(int $userid, int $courseid, string $action): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/enrol/manual/lib.php');

        if (!enrol_is_enabled('manual')) {
            throw new \moodle_exception('error_moodle_manual_enrol_disabled', 'local_tm_certification');
        }

        $enrolplugin = enrol_get_plugin('manual');
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course) {
            return;
        }

        if ($action === 'unenrol') {
            $manualwithue = $DB->get_record_sql(
                "SELECT e.*
                   FROM {enrol} e
                   JOIN {user_enrolments} ue ON ue.enrolid = e.id
                  WHERE e.courseid = :courseid AND e.enrol = :manual AND ue.userid = :userid",
                ['courseid' => $courseid, 'manual' => 'manual', 'userid' => $userid],
                IGNORE_MISSING
            );
            if ($manualwithue) {
                $enrolplugin->unenrol_user($manualwithue, $userid);
            }
            return;
        }

        if ($action !== 'enrol') {
            return;
        }

        $instances = $DB->get_records('enrol', [
            'courseid' => $courseid,
            'enrol' => 'manual',
            'status' => ENROL_INSTANCE_ENABLED,
        ], 'sortorder ASC, id ASC');
        $instance = reset($instances);
        if (!$instance) {
            $instanceid = $enrolplugin->add_default_instance($course);
            if (!$instanceid) {
                throw new \moodle_exception('error_moodle_manual_instance_missing', 'local_tm_certification');
            }
            $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        }

        $roleid = self::resolve_manual_enrol_role_id($instance);
        $enrolplugin->enrol_user($instance, $userid, $roleid, time(), 0, ENROL_USER_ACTIVE);

        $context = \context_course::instance($courseid);
        if (!is_enrolled($context, $userid, '', true)) {
            throw new \moodle_exception('error_moodle_enrol_verify_failed', 'local_tm_certification');
        }
    }

    private static function resolve_manual_enrol_role_id(\stdClass $instance): int {
        global $DB;

        $rid = (int)($instance->roleid ?? 0);
        if ($rid > 0 && $DB->record_exists('role', ['id' => $rid])) {
            return $rid;
        }
        $fallback = (int)get_config('enrol_manual', 'roleid');
        if ($fallback > 0 && $DB->record_exists('role', ['id' => $fallback])) {
            return $fallback;
        }
        throw new \moodle_exception('error_moodle_manual_role_not_configured', 'local_tm_certification');
    }

    private static function generate_username(string $email): string {
        global $DB, $CFG;

        $localpart = trim((string)preg_replace('/[^a-z0-9._-]+/i', '', strstr($email, '@', true)));
        if ($localpart === '') {
            $localpart = 'tm_cert';
        }
        $base = \core_text::strtolower(substr($localpart, 0, 20));
        $username = $base;
        $suffix = 0;
        while ($DB->record_exists('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) {
            $suffix++;
            $username = substr($base, 0, max(1, 28 - strlen((string)$suffix))) . '_' . $suffix;
        }
        return $username;
    }

    private static function generate_initial_password(): string {
        // Readable temporary password; force change on first login when supported.
        return 'Tm' . random_int(100000, 999999) . '!';
    }
}
