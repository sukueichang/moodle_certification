<?php
/**
 * Outbound notifications for certification workflows.
 * Failures are logged only — never block approve / enrol / save.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class notification_helper {

    public const CFG_ENABLED = 'notify_enabled';
    public const CFG_APP_REVIEW = 'notify_app_review';
    public const CFG_NEW_ACCOUNT = 'notify_new_account';
    public const CFG_ENROL_EXISTING = 'notify_enrol_existing';

    /**
     * Application approved: email applicant (result + session).
     * If instructor differs, also email instructor (assigned as teacher).
     */
    public static function notify_application_approved(int $appid, int $sessionid): void {
        if (!self::is_enabled(self::CFG_APP_REVIEW)) {
            return;
        }
        try {
            require_once(__DIR__ . '/application_manager.php');
            require_once(__DIR__ . '/session_manager.php');

            $app = application_manager::get_application($appid);
            $session = session_manager::get_session($sessionid);
            $facts = self::session_facts($session);

            $applicantid = (int)$app->applicantid;
            $instructorid = (int)$app->instructorid;

            if ($applicantid > 0) {
                self::send_user_mail(
                    $applicantid,
                    get_string('notify_app_approved_subject', 'local_tm_certification', $facts),
                    get_string('notify_app_approved_body', 'local_tm_certification', $facts)
                );
            }

            if ($instructorid > 0 && $instructorid !== $applicantid) {
                self::send_user_mail(
                    $instructorid,
                    get_string('notify_instructor_assigned_subject', 'local_tm_certification', $facts),
                    get_string('notify_instructor_assigned_body', 'local_tm_certification', $facts)
                );
            }
        } catch (\Throwable $e) {
            debugging('local_tm_certification notify approve: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Application rejected: email applicant only.
     */
    public static function notify_application_rejected(int $appid): void {
        if (!self::is_enabled(self::CFG_APP_REVIEW)) {
            return;
        }
        try {
            require_once(__DIR__ . '/application_manager.php');
            require_once(__DIR__ . '/session_manager.php');

            $app = application_manager::get_application($appid);
            $levelopts = session_manager::level_options();
            $a = (object)[
                'level' => $levelopts[$app->certlevel] ?? $app->certlevel,
                'when' => userdate((int)$app->starttime, get_string('strftimedatetimeshort', 'langconfig')),
                'note' => trim((string)($app->managernote ?? '')),
            ];
            if ($a->note === '') {
                $a->note = get_string('notify_no_note', 'local_tm_certification');
            }
            self::send_user_mail(
                (int)$app->applicantid,
                get_string('notify_app_rejected_subject', 'local_tm_certification', $a),
                get_string('notify_app_rejected_body', 'local_tm_certification', $a)
            );
        } catch (\Throwable $e) {
            debugging('local_tm_certification notify reject: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * After provision + add to session.
     * New account: always (if setting on) include password.
     * Existing account: only when notify_enrol_existing is on.
     * Never used for roster delete-account path.
     */
    public static function notify_examinee_added(
        int $userid,
        int $sessionid,
        bool $created,
        string $candidateid,
        string $initialpassword
    ): void {
        try {
            if ($created) {
                if (!self::is_enabled(self::CFG_NEW_ACCOUNT)) {
                    return;
                }
            } else {
                if (!self::is_enabled(self::CFG_ENROL_EXISTING)) {
                    return;
                }
            }

            require_once(__DIR__ . '/session_manager.php');
            $session = session_manager::get_session($sessionid);
            $facts = self::session_facts($session);
            $facts->candidateid = $candidateid !== '' ? $candidateid : get_string('notify_candidate_pending', 'local_tm_certification');
            $facts->password = $initialpassword !== '' ? $initialpassword : get_string('notify_password_unchanged', 'local_tm_certification');
            $facts->username = '';

            $user = \core_user::get_user($userid, '*', MUST_EXIST);
            $facts->username = (string)$user->username;
            $facts->fullname = fullname($user);

            if ($created) {
                self::send_user_mail(
                    $userid,
                    get_string('notify_new_account_subject', 'local_tm_certification', $facts),
                    get_string('notify_new_account_body', 'local_tm_certification', $facts)
                );
            } else {
                self::send_user_mail(
                    $userid,
                    get_string('notify_enrol_existing_subject', 'local_tm_certification', $facts),
                    get_string('notify_enrol_existing_body', 'local_tm_certification', $facts)
                );
            }
        } catch (\Throwable $e) {
            debugging('local_tm_certification notify enrol: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Master + per-event gate. Unset configs use sensible defaults.
     */
    public static function is_enabled(string $eventcfg): bool {
        $master = get_config('local_tm_certification', self::CFG_ENABLED);
        if ($master === false) {
            $master = '1';
        }
        if (!(int)$master) {
            return false;
        }
        $val = get_config('local_tm_certification', $eventcfg);
        if ($val === false) {
            // Defaults: review + new account ON; existing enrol OFF.
            return $eventcfg !== self::CFG_ENROL_EXISTING;
        }
        return (bool)(int)$val;
    }

    /**
     * @return \stdClass name, level, venue, when, endwhen, sitename, siteurl
     */
    public static function session_facts(\stdClass $session): \stdClass {
        require_once(__DIR__ . '/session_manager.php');
        require_once(__DIR__ . '/catalog_manager.php');
        global $CFG;

        $levelopts = session_manager::level_options();
        $venue = '';
        if (!empty($session->venuename) || !empty($session->venuecode)) {
            $venue = trim((string)($session->venuecode ?? '') . ' ' . (string)($session->venuename ?? ''));
        } else if (!empty($session->venue)) {
            $venue = (string)$session->venue;
        } else if (!empty($session->venueid)) {
            $v = catalog_manager::get_venue((int)$session->venueid);
            if ($v) {
                $code = trim((string)($v->venuecode ?? ''));
                $venue = $code !== '' ? ($code . ' - ' . (string)$v->name) : (string)$v->name;
            }
        }
        if ($venue === '') {
            $venue = get_string('notify_venue_none', 'local_tm_certification');
        }

        return (object)[
            'name' => format_string($session->name),
            'level' => $levelopts[$session->certlevel] ?? $session->certlevel,
            'venue' => $venue,
            'when' => userdate((int)$session->starttime, get_string('strftimedatetimeshort', 'langconfig')),
            'endwhen' => userdate((int)$session->endtime, get_string('strftimedatetimeshort', 'langconfig')),
            'sitename' => format_string($CFG->sitename ?? 'Moodle'),
            'siteurl' => $CFG->wwwroot ?? '',
        ];
    }

    /**
     * Send plain-text email; swallow transport errors.
     */
    public static function send_user_mail(int $userid, string $subject, string $body): bool {
        global $CFG;
        require_once($CFG->libdir . '/moodlelib.php');

        if ($userid <= 0) {
            return false;
        }
        if (!empty($CFG->noemailever)) {
            return true;
        }

        $user = \core_user::get_user($userid, '*', IGNORE_MISSING);
        if (!$user || !empty($user->deleted) || !validate_email($user->email ?? '')) {
            return false;
        }

        $from = \core_user::get_support_user();
        $ok = email_to_user($user, $from, $subject, $body, text_to_html($body));
        if (!$ok) {
            debugging('local_tm_certification email_to_user failed userid=' . $userid, DEBUG_DEVELOPER);
        }
        return (bool)$ok;
    }
}
