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
     * New examinee account rules:
     * - username = candidate ID (continues after existing 6-digit usernames / plugin IDs)
     * - lastname = student family name (姓)
     * - firstname = student given name (名)
     *
     * @return array{userid:int,created:bool,linked:bool,email:string,initial_password:string,candidateid?:string}
     */
    public static function provision_or_link_user(
        string $email,
        string $firstname,
        string $lastname,
        string $institution = ''
    ): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once(__DIR__ . '/candidate_manager.php');

        $email = \core_text::strtolower(trim($email));
        // Moodle: lastname = 姓, firstname = 名.
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

        $newuserid = 0;
        $candidateid = '';
        $plainsecret = '';
        $attempts = 0;
        while ($attempts < 8) {
            $attempts++;
            $candidateid = candidate_manager::next_candidate_id();
            if ($DB->record_exists('user', [
                'username' => $candidateid,
                'mnethostid' => $CFG->mnet_localhost_id,
            ])) {
                continue;
            }

            $plainsecret = candidate_manager::default_password_for($candidateid);
            $now = time();
            $newuser = new \stdClass();
            $newuser->auth = 'manual';
            $newuser->confirmed = 1;
            $newuser->mnethostid = $CFG->mnet_localhost_id;
            $newuser->username = $candidateid;
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

            try {
                $newuserid = (int)\user_create_user($newuser, false, false);
            } catch (\Throwable $e) {
                $newuserid = 0;
            }
            if ($newuserid >= 2) {
                break;
            }
        }
        if ($newuserid < 2 || $candidateid === '') {
            throw new \moodle_exception('error_batch_user_invalid', 'local_tm_certification');
        }

        candidate_manager::bind_candidate_id($newuserid, $candidateid);
        // Class-day login: do not force password change.
        self::clear_force_password_change($newuserid);
        self::assign_configured_examinee_role($newuserid);

        return [
            'userid' => $newuserid,
            'created' => true,
            'linked' => false,
            'email' => $email,
            'initial_password' => $plainsecret,
            'candidateid' => $candidateid,
        ];
    }

    /**
     * Assign admin-configured examinee Moodle role at system context.
     * Additive only: never removes existing roles. Skips if already assigned.
     */
    public static function assign_configured_examinee_role(int $userid): void {
        global $DB;

        $roleid = (int)get_config('local_tm_certification', 'auto_role_examinee');
        if ($roleid <= 0 || !$DB->record_exists('role', ['id' => $roleid])) {
            return;
        }
        $context = \context_system::instance();
        if (user_has_role_assignment($userid, $roleid, $context->id)) {
            return;
        }
        role_assign($roleid, $userid, $context->id);
    }

    /**
     * Set examinee Moodle password to Tmai + candidate ID (manual add / batch).
     * Always clears force-password-change so class-day login works with the roster sheet.
     */
    public static function apply_default_password(int $userid, string $candidateid): string {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/lib/moodlelib.php');

        $plain = candidate_manager::default_password_for($candidateid);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if ((string)$user->auth !== 'manual') {
            debugging(
                'local_tm_certification: resetting password for non-manual auth user id=' . $userid
                . ' auth=' . $user->auth,
                DEBUG_DEVELOPER
            );
        }
        \update_internal_user_password($user, $plain);
        self::clear_force_password_change($userid);
        return $plain;
    }

    private static function clear_force_password_change(int $userid): void {
        global $DB;
        $usertable = new \xmldb_table('user');
        $forcepwfield = new \xmldb_field('forcepasswordchange');
        if ($DB->get_manager()->field_exists($usertable, $forcepwfield)) {
            $DB->set_field('user', 'forcepasswordchange', 0, ['id' => $userid]);
        }
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
        if (!session_manager::can_add_examinees($session)) {
            throw new \moodle_exception('error_session_ended_no_enrol', 'local_tm_certification');
        }
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

        // New + existing (issue ID / keep ID): add configured examinee role without removing others.
        if ($status === self::ENROL_APPROVED) {
            self::assign_configured_examinee_role($userid);
        }

        if ($syncmoodle && $status === self::ENROL_APPROVED) {
            $courseid = session_manager::resolve_courseid($session, true);
            if ($courseid > 0) {
                self::sync_moodle_enrolment($userid, $courseid, 'enrol');
            }
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
        require_once(__DIR__ . '/candidate_manager.php');
        require_once(__DIR__ . '/prerequisite_helper.php');
        require_once(__DIR__ . '/session_manager.php');

        $session = session_manager::get_session($sessionid);
        $certlevel = (string)$session->certlevel;

        if (prerequisite_helper::requires_prerequisite($certlevel)) {
            $check = prerequisite_helper::evaluate_row($email, $firstname, $lastname, $institution, $certlevel);
            if (empty($check['eligible'])) {
                throw new \moodle_exception(
                    'error_prereq_blocked',
                    'local_tm_certification',
                    '',
                    prerequisite_helper::format_reasons($check['reasons'] ?? [])
                );
            }
            $userid = (int)$check['userid'];
            $candidateid = (string)$check['candidateid'];
            $password = self::apply_default_password($userid, $candidateid);
            $enrolid = self::add_to_session($sessionid, $userid, $addedby, self::ENROL_APPROVED, true);
            $provision = [
                'userid' => $userid,
                'created' => false,
                'linked' => true,
                'email' => \core_text::strtolower(trim($email)),
                'initial_password' => $password,
                'candidateid' => $candidateid,
                'enrolid' => $enrolid,
            ];
            require_once(__DIR__ . '/notification_helper.php');
            notification_helper::notify_examinee_added(
                $userid,
                $sessionid,
                false,
                $candidateid,
                $password
            );
            return $provision;
        }

        $provision = self::provision_or_link_user($email, $firstname, $lastname, $institution);
        $provision['candidateid'] = candidate_manager::ensure_candidate_id((int)$provision['userid'], $addedby);
        // Always align login password to Tmai + candidate ID for class-day use.
        $provision['initial_password'] = self::apply_default_password(
            (int)$provision['userid'],
            (string)$provision['candidateid']
        );
        $enrolid = self::add_to_session($sessionid, (int)$provision['userid'], $addedby, self::ENROL_APPROVED, true);
        $provision['enrolid'] = $enrolid;

        require_once(__DIR__ . '/notification_helper.php');
        notification_helper::notify_examinee_added(
            (int)$provision['userid'],
            $sessionid,
            !empty($provision['created']),
            (string)($provision['candidateid'] ?? ''),
            (string)($provision['initial_password'] ?? '')
        );

        return $provision;
    }

    /**
     * @return \stdClass[]
     */
    public static function get_session_roster(int $sessionid): array {
        global $DB;
        require_once(__DIR__ . '/candidate_manager.php');

        $sql = "SELECT e.*, u.email, u.institution, u.username,
                       u.firstname, u.lastname, u.middlename, u.alternatename,
                       u.firstnamephonetic, u.lastnamephonetic,
                       c.candidateid
                  FROM {local_tm_cert_enrols} e
                  JOIN {user} u ON u.id = e.userid
             LEFT JOIN {local_tm_cert_candidates} c ON c.userid = u.id
                 WHERE e.sessionid = :sessionid AND e.status <> :cancelled
              ORDER BY u.lastname ASC, u.firstname ASC, e.id ASC";
        $rows = array_values($DB->get_records_sql($sql, [
            'sessionid' => $sessionid,
            'cancelled' => self::ENROL_CANCELLED,
        ]));
        foreach ($rows as $row) {
            if (empty($row->candidateid) && candidate_manager::is_candidate_id_format((string)$row->username)) {
                $row->candidateid = (string)$row->username;
            }
        }
        return $rows;
    }

    public static function cancel_enrolment(int $enrolid, bool $unenrolmoodle = false): void {
        global $DB;
        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        $enrol->status = self::ENROL_CANCELLED;
        $enrol->timemodified = time();
        $DB->update_record('local_tm_cert_enrols', $enrol);

        if ($unenrolmoodle) {
            $session = session_manager::get_session((int)$enrol->sessionid);
            $courseid = session_manager::resolve_courseid($session, true);
            if ($courseid > 0) {
                self::sync_moodle_enrolment((int)$enrol->userid, $courseid, 'unenrol');
            }
        }
    }

    /**
     * Decide whether removing this roster row should also delete the Moodle user.
     *
     * Delete only when:
     * - account looks like an examinee (candidate ID / 6-digit username), AND
     * - never logged in (firstaccess/lastaccess = 0), AND
     * - no quiz attempts, AND
     * - no other active certification roster rows.
     *
     * @return array{userid:int,fullname:string,username:string,will_delete_user:bool,reasons:string[]}
     */
    public static function assess_roster_removal(int $enrolid): array {
        global $DB;
        require_once(__DIR__ . '/candidate_manager.php');

        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => (int)$enrol->userid, 'deleted' => 0], '*', MUST_EXIST);

        $reasons = [];
        $willdelete = true;

        if (is_siteadmin($user) || (int)$user->id < 2) {
            $willdelete = false;
            $reasons[] = 'protected_account';
        }

        $candidateid = candidate_manager::get_candidate_id((int)$user->id);
        $isexaminee = ($candidateid !== null) || candidate_manager::is_candidate_id_format((string)$user->username);
        if (!$isexaminee) {
            $willdelete = false;
            $reasons[] = 'not_examinee_account';
        }

        if ((int)$user->lastaccess > 0 || (int)$user->firstaccess > 0) {
            $willdelete = false;
            $reasons[] = 'has_logged_in';
        }

        if ($DB->get_manager()->table_exists('quiz_attempts')
            && $DB->record_exists('quiz_attempts', ['userid' => (int)$user->id])) {
            $willdelete = false;
            $reasons[] = 'has_quiz_attempts';
        }

        $otheractive = $DB->count_records_select(
            'local_tm_cert_enrols',
            'userid = :userid AND status <> :cancelled AND id <> :enrolid',
            [
                'userid' => (int)$user->id,
                'cancelled' => self::ENROL_CANCELLED,
                'enrolid' => $enrolid,
            ]
        );
        if ($otheractive > 0) {
            $willdelete = false;
            $reasons[] = 'other_active_roster';
        }

        return [
            'enrolid' => $enrolid,
            'sessionid' => (int)$enrol->sessionid,
            'userid' => (int)$user->id,
            'fullname' => fullname($user),
            'username' => (string)$user->username,
            'email' => (string)$user->email,
            'will_delete_user' => $willdelete,
            'reasons' => $reasons,
        ];
    }

    /**
     * Remove from session roster; optionally delete unused mistaken account.
     *
     * @return array{removed:bool,deleted_user:bool,assessment:array}
     */
    public static function remove_from_roster(int $enrolid, bool $confirmdeleteuser = false): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $assessment = self::assess_roster_removal($enrolid);
        // Always remove from this session + unenrol mapped Moodle course.
        self::cancel_enrolment($enrolid, true);

        $deleted = false;
        if (!empty($assessment['will_delete_user']) && $confirmdeleteuser) {
            require_once(__DIR__ . '/candidate_manager.php');
            $userid = (int)$assessment['userid'];
            $user = \core_user::get_user($userid, '*', MUST_EXIST);
            if (!is_siteadmin($user) && $userid > 1 && empty($user->deleted)) {
                // Free candidate ID before soft-delete so the number can be reused.
                candidate_manager::release_for_userid($userid);
                \delete_user($user);
                $deleted = true;
            }
        }

        return [
            'removed' => true,
            'deleted_user' => $deleted,
            'assessment' => $assessment,
        ];
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

    /**
     * Sessions the examinee is actively rostered on (newest start first).
     *
     * @return \stdClass[] session rows + venue + instructor name fields + enrolid
     */
    public static function get_examinee_sessions(int $userid): array {
        global $DB;
        require_once(__DIR__ . '/session_manager.php');

        $sql = "SELECT s.*, e.id AS enrolid, e.status AS enrolstatus,
                       v.name AS venuename, v.venuecode AS venuecode,
                       u.firstname AS instructor_firstname, u.lastname AS instructor_lastname,
                       u.middlename AS instructor_middlename, u.alternatename AS instructor_alternatename,
                       u.firstnamephonetic AS instructor_firstnamephonetic,
                       u.lastnamephonetic AS instructor_lastnamephonetic
                  FROM {local_tm_cert_enrols} e
                  JOIN {local_tm_cert_sessions} s ON s.id = e.sessionid
             LEFT JOIN {local_tm_cert_venues} v ON v.id = s.venueid
             LEFT JOIN {user} u ON u.id = s.instructorid AND u.deleted = 0
                 WHERE e.userid = :userid AND e.status <> :cancelled
              ORDER BY s.starttime DESC, s.id DESC";
        $rows = array_values($DB->get_records_sql($sql, [
            'userid' => $userid,
            'cancelled' => self::ENROL_CANCELLED,
        ]));
        foreach ($rows as $i => $row) {
            $rows[$i] = session_manager::sync_auto_end($row);
            if (!empty($row->instructorid)) {
                $rows[$i]->instructor = (object)[
                    'id' => (int)$row->instructorid,
                    'firstname' => $row->instructor_firstname ?? '',
                    'lastname' => $row->instructor_lastname ?? '',
                    'middlename' => $row->instructor_middlename ?? '',
                    'alternatename' => $row->instructor_alternatename ?? '',
                    'firstnamephonetic' => $row->instructor_firstnamephonetic ?? '',
                    'lastnamephonetic' => $row->instructor_lastnamephonetic ?? '',
                ];
            } else {
                $rows[$i]->instructor = null;
            }
        }
        return $rows;
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

}
