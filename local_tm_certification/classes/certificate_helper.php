<?php
/**
 * customcert integration for certification exam certificates.
 * Copy-adapted from local_tm_course\certificate_helper (no runtime dependency).
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/catalog_manager.php');
require_once(__DIR__ . '/session_manager.php');

class certificate_helper {

    public const STATUS_ISSUED = 'issued';
    public const STATUS_RECEIVABLE = 'receivable';

    /**
     * Exam certificates for a user in mapped certification courses
     * (issued + receivable before View certificate).
     *
     * @return \stdClass[] each: issueid, code, userid, timecreated, customcertid,
     *         courseid, coursename, cmid, certlevel, status
     */
    public static function get_user_exam_certificates(int $userid, int $limit = 0): array {
        if (!self::is_customcert_installed() || $userid <= 0) {
            return [];
        }

        $courselevel = self::mapped_course_to_level();
        if (empty($courselevel)) {
            return [];
        }

        $courseids = array_keys($courselevel);
        $all = self::get_certificates_for_courses([$userid], $courseids, $limit);
        foreach ($all as $row) {
            $cid = (int)$row->courseid;
            $row->certlevel = $courselevel[$cid] ?? '';
            $row->status = !empty($row->issueid) ? self::STATUS_ISSUED : self::STATUS_RECEIVABLE;
        }
        return $all;
    }

    /**
     * Highest mapped exam level the user can currently receive or has issued.
     */
    public static function get_user_highest_exam_level(int $userid): ?string {
        $rank = [
            session_manager::LEVEL_BASIC => 1,
            session_manager::LEVEL_INTERMEDIATE => 2,
            session_manager::LEVEL_ADVANCED => 3,
        ];
        $best = null;
        $bestrank = 0;
        foreach (self::get_user_exam_certificates($userid) as $row) {
            $lv = (string)($row->certlevel ?? '');
            $r = $rank[$lv] ?? 0;
            if ($r > $bestrank) {
                $bestrank = $r;
                $best = $lv;
            }
        }
        return $best;
    }

    /**
     * Own-download URL (triggers issue on first View certificate).
     */
    public static function get_own_download_url(int $cmid): \moodle_url {
        return new \moodle_url('/mod/customcert/view.php', [
            'id' => $cmid,
            'downloadown' => 1,
        ]);
    }

    public static function is_customcert_installed(): bool {
        global $DB;
        $manager = $DB->get_manager();
        return $manager->table_exists('customcert')
            && $manager->table_exists('customcert_issues');
    }

    /**
     * @return array<int,string> courseid => level code
     */
    private static function mapped_course_to_level(): array {
        $map = [];
        foreach (catalog_manager::get_course_mapping_all_levels() as $level => $courseid) {
            $courseid = (int)$courseid;
            if ($courseid > 0) {
                $map[$courseid] = (string)$level;
            }
        }
        return $map;
    }

    /**
     * @param int[] $userids
     * @param int[] $courseids
     * @return \stdClass[]
     */
    private static function get_certificates_for_courses(array $userids, array $courseids, int $limit = 0): array {
        global $DB;

        $userids = array_values(array_unique(array_map('intval', $userids)));
        $courseids = array_values(array_unique(array_map('intval', $courseids)));
        $courseids = array_values(array_filter($courseids, static function(int $id): bool {
            return $id > 0;
        }));
        if (empty($userids) || empty($courseids)) {
            return [];
        }

        [$uinsql, $uparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        [$cinsql, $cparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');

        $sql = "SELECT ci.id AS issueid,
                       ci.code,
                       ci.userid,
                       ci.timecreated,
                       cc.id AS customcertid,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       cm.id AS cmid
                  FROM {customcert_issues} ci
                  JOIN {customcert} cc ON cc.id = ci.customcertid
                  JOIN {course} c ON c.id = cc.course
                  JOIN {course_modules} cm ON cm.instance = cc.id AND cm.course = cc.course
                  JOIN {modules} m ON m.id = cm.module
                 WHERE m.name = :modname
                   AND cm.deletioninprogress = 0
                   AND ci.userid $uinsql
                   AND c.id $cinsql
              ORDER BY ci.timecreated DESC, ci.id DESC";
        $params = $uparams + $cparams + ['modname' => 'customcert'];
        $issued = self::dedupe_certificate_rows(self::fetch_sql_rows($sql, $params));

        $issuedkeys = [];
        foreach ($issued as $row) {
            $issuedkeys[self::certificate_row_key($row)] = true;
        }

        $receivable = [];
        foreach (self::get_enrolled_customcert_candidates($userids, $courseids) as $candidate) {
            $key = self::certificate_row_key($candidate);
            if (isset($issuedkeys[$key])) {
                continue;
            }
            if (!self::user_can_receive_certificate(
                (int)$candidate->customcertid,
                (int)$candidate->courseid,
                (int)$candidate->cmid,
                (int)$candidate->userid
            )) {
                continue;
            }
            $receivable[] = (object)[
                'issueid' => 0,
                'code' => '',
                'userid' => (int)$candidate->userid,
                'timecreated' => 0,
                'customcertid' => (int)$candidate->customcertid,
                'courseid' => (int)$candidate->courseid,
                'coursename' => (string)$candidate->coursename,
                'cmid' => (int)$candidate->cmid,
            ];
        }

        $merged = array_merge($issued, $receivable);
        usort($merged, static function(\stdClass $a, \stdClass $b): int {
            $atime = (int)($a->timecreated ?? 0);
            $btime = (int)($b->timecreated ?? 0);
            if ($atime !== $btime) {
                return $btime <=> $atime;
            }
            return ((int)($b->issueid ?? 0)) <=> ((int)($a->issueid ?? 0));
        });

        if ($limit > 0) {
            return array_slice($merged, 0, $limit);
        }
        return $merged;
    }

    /**
     * Mirrors mod_customcert view.php / email task eligibility.
     */
    public static function user_can_receive_certificate(
        int $customcertid,
        int $courseid,
        int $cmid,
        int $userid
    ): bool {
        global $DB;

        $coursecontext = \context_course::instance($courseid);
        if (!is_enrolled($coursecontext, $userid)) {
            return false;
        }

        $modinfo = get_fast_modinfo($courseid, $userid);
        if (empty($modinfo->instances['customcert'][$customcertid])) {
            return false;
        }
        $cminfo = $modinfo->instances['customcert'][$customcertid];
        if (!$cminfo->uservisible) {
            return false;
        }

        $context = \context_module::instance($cmid);
        if (has_capability('mod/customcert:manage', $context, $userid)) {
            return false;
        }
        if (!has_capability('mod/customcert:receiveissue', $context, $userid)) {
            return false;
        }

        $customcert = $DB->get_record('customcert', ['id' => $customcertid], 'id, requiredtime');
        if (!$customcert) {
            return false;
        }
        if (!empty($customcert->requiredtime)
            && class_exists('\\mod_customcert\\certificate')
            && \mod_customcert\certificate::get_course_time($courseid, $userid)
                < ((int)$customcert->requiredtime * 60)) {
            return false;
        }

        return true;
    }

    /**
     * @param int[] $userids
     * @param int[] $courseids
     * @return \stdClass[]
     */
    private static function get_enrolled_customcert_candidates(array $userids, array $courseids): array {
        global $DB;

        [$uinsql, $uparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        [$cinsql, $cparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
        $sql = "SELECT ue.userid,
                       cc.id AS customcertid,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       cm.id AS cmid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.status = 0
                  JOIN {customcert} cc ON cc.course = e.courseid
                  JOIN {course} c ON c.id = cc.course
                  JOIN {course_modules} cm ON cm.instance = cc.id AND cm.course = cc.course
                  JOIN {modules} m ON m.id = cm.module
                 WHERE m.name = :modname
                   AND ue.status = 0
                   AND cm.deletioninprogress = 0
                   AND ue.userid $uinsql
                   AND c.id $cinsql
              ORDER BY cc.id DESC, cm.id DESC";
        $params = $uparams + $cparams + ['modname' => 'customcert'];
        return self::dedupe_certificate_rows(self::fetch_sql_rows($sql, $params));
    }

    /**
     * @return \stdClass[]
     */
    private static function fetch_sql_rows(string $sql, array $params): array {
        global $DB;
        $rows = [];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $row) {
            $rows[] = $row;
        }
        $rs->close();
        return $rows;
    }

    /**
     * @param \stdClass[] $rows
     * @return \stdClass[]
     */
    private static function dedupe_certificate_rows(array $rows): array {
        $deduped = [];
        $seen = [];
        foreach ($rows as $row) {
            $key = self::certificate_row_key($row);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $row;
        }
        return $deduped;
    }

    private static function certificate_row_key(\stdClass $row): string {
        return (int)$row->userid . ':' . (int)$row->courseid . ':' . (int)$row->customcertid;
    }
}
