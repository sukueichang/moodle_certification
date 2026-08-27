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
     * Backs up the previous hash once (for existing accounts) so roster removal can restore it.
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
        self::backup_password_hash_once($userid);
        \update_internal_user_password($user, $plain);
        self::clear_force_password_change($userid);
        return $plain;
    }

    /**
     * Store current user.password hash once per userid (do not overwrite).
     */
    public static function backup_password_hash_once(int $userid): void {
        global $DB;

        if ($userid < 2) {
            return;
        }
        if (!$DB->get_manager()->table_exists('local_tm_cert_pwbackup')) {
            return;
        }
        if ($DB->record_exists('local_tm_cert_pwbackup', ['userid' => $userid])) {
            return;
        }
        $hash = $DB->get_field('user', 'password', ['id' => $userid, 'deleted' => 0]);
        if ($hash === false || $hash === null || $hash === '') {
            return;
        }
        $row = new \stdClass();
        $row->userid = $userid;
        $row->passwordhash = (string)$hash;
        $row->timecreated = time();
        try {
            $DB->insert_record('local_tm_cert_pwbackup', $row);
        } catch (\dml_exception $e) {
            // Concurrent insert for same userid is fine.
            if (!$DB->record_exists('local_tm_cert_pwbackup', ['userid' => $userid])) {
                throw $e;
            }
        }
    }

    /**
     * Restore backed-up password hash and clear backup. No-op if none stored.
     *
     * @return bool true when a backup was applied
     */
    public static function restore_password_hash(int $userid): bool {
        global $DB;

        if ($userid < 2 || !$DB->get_manager()->table_exists('local_tm_cert_pwbackup')) {
            return false;
        }
        $backup = $DB->get_record('local_tm_cert_pwbackup', ['userid' => $userid]);
        if (!$backup || (string)$backup->passwordhash === '') {
            return false;
        }
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id', IGNORE_MISSING);
        if (!$user) {
            $DB->delete_records('local_tm_cert_pwbackup', ['userid' => $userid]);
            return false;
        }
        $DB->set_field('user', 'password', (string)$backup->passwordhash, ['id' => $userid]);
        self::clear_force_password_change($userid);
        $DB->delete_records('local_tm_cert_pwbackup', ['userid' => $userid]);
        return true;
    }

    /**
     * Drop password backup without restoring (e.g. account deleted).
     */
    public static function discard_password_backup(int $userid): void {
        global $DB;
        if ($userid < 2 || !$DB->get_manager()->table_exists('local_tm_cert_pwbackup')) {
            return;
        }
        $DB->delete_records('local_tm_cert_pwbackup', ['userid' => $userid]);
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
     * Count other non-cancelled roster rows for a user (optionally excluding one enrol id).
     */
    public static function count_other_active_enrols(int $userid, int $excludingenrolid = 0): int {
        global $DB;
        $select = 'userid = :userid AND status <> :cancelled';
        $params = [
            'userid' => $userid,
            'cancelled' => self::ENROL_CANCELLED,
        ];
        if ($excludingenrolid > 0) {
            $select .= ' AND id <> :enrolid';
            $params['enrolid'] = $excludingenrolid;
        }
        return (int)$DB->count_records_select('local_tm_cert_enrols', $select, $params);
    }

    /**
     * True when user has cert usage that must keep the candidate ID (quiz / practical).
     */
    public static function user_has_cert_usage_traces(int $userid): bool {
        global $DB;

        if ($DB->get_manager()->table_exists('quiz_attempts')
                && $DB->record_exists('quiz_attempts', ['userid' => $userid])) {
            return true;
        }

        return $DB->record_exists_select(
            'local_tm_cert_enrols',
            'userid = :userid AND (practicalpass = 1 OR practicalscore IS NOT NULL)',
            ['userid' => $userid]
        );
    }

    /**
     * Existing account (username ≠ candidate ID): first unused cert participation may release ID on remove.
     */
    public static function should_release_candidate_id_on_remove(int $userid, int $excludingenrolid, string $username): bool {
        require_once(__DIR__ . '/candidate_manager.php');

        if (candidate_manager::is_candidate_id_format($username)) {
            // Username is the ID — only freed when the Moodle account is deleted.
            return false;
        }
        if (candidate_manager::get_candidate_id($userid) === null) {
            return false;
        }
        if (self::count_other_active_enrols($userid, $excludingenrolid) > 0) {
            return false;
        }
        if (self::user_has_cert_usage_traces($userid)) {
            return false;
        }
        return true;
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
        require_once(__DIR__ . '/course_access_helper.php');
        require_once(__DIR__ . '/session_manager.php');

        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        $enrol->status = self::ENROL_CANCELLED;
        $enrol->timemodified = time();
        $DB->update_record('local_tm_cert_enrols', $enrol);

        if ($unenrolmoodle) {
            $session = session_manager::get_session((int)$enrol->sessionid);
            // Unenrol mapped level course only when no other active roster remains
            // for that course and the user is not an instructor (grandfather otherwise).
            course_access_helper::maybe_unenrol_level_after_roster_remove(
                (int)$enrol->userid,
                (string)$session->certlevel,
                (int)$enrolid
            );
        }
    }

    /**
     * Decide side-effects when removing a roster row.
     *
     * Delete Moodle user only when:
     * - username is a 6-digit candidate ID (plugin-created examinee), AND
     * - never logged in, AND
     * - no quiz attempts / practical traces, AND
     * - no other active certification roster rows.
     *
     * Existing accounts (incl. instructors): never deleted. May release candidate ID when
     * unused (no quiz/practical, no other active roster) and restore backed-up password.
     *
     * @return array{
     *   enrolid:int,sessionid:int,userid:int,fullname:string,username:string,email:string,
     *   will_delete_user:bool,will_release_candidate_id:bool,will_restore_password:bool,
     *   reasons:string[]
     * }
     */
    public static function assess_roster_removal(int $enrolid): array {
        global $DB;
        require_once(__DIR__ . '/candidate_manager.php');

        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => (int)$enrol->userid, 'deleted' => 0], '*', MUST_EXIST);
        $userid = (int)$user->id;
        $username = (string)$user->username;

        $reasons = [];
        $willdelete = true;

        if (is_siteadmin($user) || $userid < 2) {
            $willdelete = false;
            $reasons[] = 'protected_account';
        }

        // Only plugin-created examinee accounts (username = candidate ID) may be deleted.
        if (!candidate_manager::is_candidate_id_format($username)) {
            $willdelete = false;
            $reasons[] = 'existing_account';
        }

        if ((int)$user->lastaccess > 0 || (int)$user->firstaccess > 0) {
            $willdelete = false;
            $reasons[] = 'has_logged_in';
        }

        if (self::user_has_cert_usage_traces($userid)) {
            $willdelete = false;
            $reasons[] = 'has_cert_usage';
        }

        $otheractive = self::count_other_active_enrols($userid, $enrolid);
        if ($otheractive > 0) {
            $willdelete = false;
            $reasons[] = 'other_active_roster';
        }

        $willrelease = !$willdelete
            && self::should_release_candidate_id_on_remove($userid, $enrolid, $username);

        $hasbackup = $DB->get_manager()->table_exists('local_tm_cert_pwbackup')
            && $DB->record_exists('local_tm_cert_pwbackup', ['userid' => $userid]);
        $willrestore = !$willdelete && $otheractive === 0 && $hasbackup;

        return [
            'enrolid' => $enrolid,
            'sessionid' => (int)$enrol->sessionid,
            'userid' => $userid,
            'fullname' => fullname($user),
            'username' => $username,
            'email' => (string)$user->email,
            'will_delete_user' => $willdelete,
            'will_release_candidate_id' => $willrelease,
            'will_restore_password' => $willrestore,
            'reasons' => $reasons,
        ];
    }

    /**
     * Remove from session roster; optionally delete unused mistaken examinee account.
     * For kept accounts: restore password when leaving last active roster; release
     * candidate ID when first unused participation on an existing account.
     * Moodle course unenrol follows course_access_helper (last roster for level only;
     * instructors and historical enrols without a remove action are kept).
     *
     * @return array{removed:bool,deleted_user:bool,released_candidate_id:bool,restored_password:bool,assessment:array}
     */
    public static function remove_from_roster(int $enrolid, bool $confirmdeleteuser = false): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once(__DIR__ . '/candidate_manager.php');

        $assessment = self::assess_roster_removal($enrolid);
        // Cancel roster row; maybe unenrol mapped course (see course_access_helper).
        self::cancel_enrolment($enrolid, true);

        $deleted = false;
        $released = false;
        $restored = false;
        $userid = (int)$assessment['userid'];

        if (!empty($assessment['will_delete_user']) && $confirmdeleteuser) {
            $user = \core_user::get_user($userid, '*', MUST_EXIST);
            if (!is_siteadmin($user) && $userid > 1 && empty($user->deleted)) {
                candidate_manager::release_for_userid($userid);
                self::discard_password_backup($userid);
                \delete_user($user);
                $deleted = true;
                $released = true;
            }
        } else {
            if (!empty($assessment['will_restore_password'])) {
                $restored = self::restore_password_hash($userid);
            }
            if (!empty($assessment['will_release_candidate_id'])) {
                candidate_manager::release_for_userid($userid);
                $released = true;
            }
        }

        $assessment['released_candidate_id'] = $released;
        $assessment['restored_password'] = $restored;

        return [
            'removed' => true,
            'deleted_user' => $deleted,
            'released_candidate_id' => $released,
            'restored_password' => $restored,
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
