<?php
/**
 * Progressive Moodle course access by roster level / instructor identity.
 *
 * Policy:
 * - Brochure category/courses are site-managed (always open); not mapped here.
 * - Level courses unlock when the user has an active session roster for that level.
 * - Instructor identity unlocks all mapped level courses + instructor zone.
 * - Never bulk-unenrol historical students. Unenrol level courses only when the
 *   plugin removes the last active roster for that level (and user is not an instructor).
 * - Losing instructor identity unenrols instructor zone only (level enrols kept).
 *
 * Note: Auto-hide / disable self-enrol for mapped courses is deferred — some learners
 * need course access without being on a plugin roster. Do not reintroduce without that case.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class course_access_helper {

    public const CFG_MAPPING_INSTRUCTOR = 'mapping_course_instructor';

    /**
     * Mapped Moodle course for the instructor zone (0 = not configured).
     */
    public static function get_instructor_zone_courseid(): int {
        return (int)get_config('local_tm_certification', self::CFG_MAPPING_INSTRUCTOR);
    }

    /**
     * Active roster levels for a user (non-cancelled enrols).
     *
     * @return string[] level codes
     */
    public static function get_user_roster_levels(int $userid): array {
        global $DB;
        if ($userid <= 0) {
            return [];
        }
        $sql = "SELECT DISTINCT s.certlevel
                  FROM {local_tm_cert_enrols} e
                  JOIN {local_tm_cert_sessions} s ON s.id = e.sessionid
                 WHERE e.userid = :userid AND e.status <> :cancelled";
        $levels = $DB->get_fieldset_sql($sql, [
            'userid' => $userid,
            'cancelled' => enrolment_manager::ENROL_CANCELLED,
        ]);
        $allowed = session_manager::level_options();
        $out = [];
        foreach ($levels as $lv) {
            $lv = (string)$lv;
            if (array_key_exists($lv, $allowed)) {
                $out[] = $lv;
            }
        }
        return $out;
    }

    /**
     * True when user still has another active roster whose mapped course equals $courseid.
     */
    public static function user_has_active_roster_for_course(
        int $userid,
        int $courseid,
        int $excludingenrolid = 0
    ): bool {
        global $DB;
        if ($userid <= 0 || $courseid <= 0) {
            return false;
        }
        $map = catalog_manager::get_course_mapping_all_levels();
        $levels = [];
        foreach ($map as $level => $cid) {
            if ((int)$cid === $courseid) {
                $levels[] = (string)$level;
            }
        }
        if (empty($levels)) {
            return false;
        }
        list($insql, $params) = $DB->get_in_or_equal($levels, SQL_PARAMS_NAMED, 'lv');
        $params['userid'] = $userid;
        $params['cancelled'] = enrolment_manager::ENROL_CANCELLED;
        $exclude = '';
        if ($excludingenrolid > 0) {
            $exclude = ' AND e.id <> :exid';
            $params['exid'] = $excludingenrolid;
        }
        $sql = "SELECT COUNT(1)
                  FROM {local_tm_cert_enrols} e
                  JOIN {local_tm_cert_sessions} s ON s.id = e.sessionid
                 WHERE e.userid = :userid
                   AND e.status <> :cancelled
                   AND s.certlevel {$insql}
                   {$exclude}";
        return ((int)$DB->count_records_sql($sql, $params)) > 0;
    }

    /**
     * Enrol user into the mapped Moodle course for a certification level (no-op if unmapped).
     */
    public static function unlock_level_course(int $userid, string $level): void {
        $courseid = catalog_manager::get_moodle_courseid_for_level($level);
        if ($userid <= 0 || $courseid <= 0) {
            return;
        }
        enrolment_manager::sync_moodle_enrolment($userid, $courseid, 'enrol');
    }

    /**
     * After roster removal: unenrol mapped level course only when
     * - user is not an instructor identity, and
     * - no other active roster remains for that mapped course.
     *
     * Does not touch historical enrolments for users never managed via roster remove.
     */
    public static function maybe_unenrol_level_after_roster_remove(
        int $userid,
        string $level,
        int $excludingenrolid = 0
    ): void {
        if ($userid <= 0) {
            return;
        }
        $user = \core_user::get_user($userid, '*', IGNORE_MISSING);
        if (!$user || !empty($user->deleted)) {
            return;
        }
        if (permissions_manager::user_can_instruct($user)) {
            return;
        }
        $courseid = catalog_manager::get_moodle_courseid_for_level($level);
        if ($courseid <= 0) {
            return;
        }
        if (self::user_has_active_roster_for_course($userid, $courseid, $excludingenrolid)) {
            return;
        }
        enrolment_manager::sync_moodle_enrolment($userid, $courseid, 'unenrol');
    }

    /**
     * Enrol instructor into all mapped level courses + instructor zone.
     * If user is no longer an instructor identity: unenrol instructor zone only
     * (level course enrolments are grandfathered / kept).
     */
    public static function sync_instructor_access(int $userid): void {
        if ($userid <= 0) {
            return;
        }
        $user = \core_user::get_user($userid, '*', IGNORE_MISSING);
        if (!$user || !empty($user->deleted)) {
            return;
        }

        $zoneid = self::get_instructor_zone_courseid();
        if (permissions_manager::user_can_instruct($user)) {
            foreach (array_keys(session_manager::level_options()) as $level) {
                self::unlock_level_course($userid, $level);
            }
            if ($zoneid > 0) {
                enrolment_manager::sync_moodle_enrolment($userid, $zoneid, 'enrol');
            }
            return;
        }

        if ($zoneid > 0) {
            enrolment_manager::sync_moodle_enrolment($userid, $zoneid, 'unenrol');
        }
    }

    /**
     * Sync every user currently listed via configured instructor role mapping.
     *
     * @return int number of users processed
     */
    public static function sync_all_mapped_instructors(int $limit = 500): int {
        $n = 0;
        foreach (catalog_manager::get_instructor_users_from_mapping($limit) as $u) {
            self::sync_instructor_access((int)$u->id);
            $n++;
        }
        return $n;
    }
}
