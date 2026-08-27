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
     * All mapped exam levels the user can currently receive or has issued
     * (basic → intermediate → advanced order; one entry per level).
     *
     * @return string[] level codes
     */
    public static function get_user_exam_levels(int $userid): array {
        $rank = [
            session_manager::LEVEL_BASIC => 1,
            session_manager::LEVEL_INTERMEDIATE => 2,
            session_manager::LEVEL_ADVANCED => 3,
        ];
        $found = [];
        foreach (self::get_user_exam_certificates($userid) as $row) {
            $lv = (string)($row->certlevel ?? '');
            if ($lv === '' || !isset($rank[$lv])) {
                continue;
            }
            $found[$lv] = $rank[$lv];
        }
        asort($found, SORT_NUMERIC);
        return array_keys($found);
    }

    /**
     * Highest mapped exam level the user can currently receive or has issued.
     */
    public static function get_user_highest_exam_level(int $userid): ?string {
        $levels = self::get_user_exam_levels($userid);
        if (empty($levels)) {
            return null;
        }
        return end($levels);
    }

    /**
     * First exam certificate row for a mapped level (issued or receivable), if any.
     */
    public static function get_user_exam_certificate_for_level(int $userid, string $level): ?\stdClass {
        $level = trim($level);
        if ($level === '') {
            return null;
        }
        foreach (self::get_user_exam_certificates($userid) as $row) {
            if ((string)($row->certlevel ?? '') === $level) {
                return $row;
            }
        }
        return null;
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

    /**
     * Moodle customcert status for a user at a mapped level.
     *
     * @return array{
     *   available:bool,
     *   status:?string,
     *   cmid:int,
     *   customcertid:int,
     *   courseid:int,
     *   reason:?string,
     *   row:?\stdClass
     * }
     */
    public static function get_level_certificate_availability(int $userid, string $level): array {
        $empty = [
            'available' => false,
            'status' => null,
            'cmid' => 0,
            'customcertid' => 0,
            'courseid' => 0,
            'reason' => 'no_certificate',
            'row' => null,
        ];
        if (!self::is_customcert_installed() || $userid <= 0 || trim($level) === '') {
            $empty['reason'] = !self::is_customcert_installed() ? 'customcert_missing' : 'no_certificate';
            return $empty;
        }

        $courseid = (int)catalog_manager::get_moodle_courseid_for_level($level);
        if ($courseid <= 0) {
            $empty['reason'] = 'course_unmapped';
            return $empty;
        }

        $row = self::get_user_exam_certificate_for_level($userid, $level);
        if (!$row) {
            return $empty;
        }

        return [
            'available' => true,
            'status' => (string)$row->status,
            'cmid' => (int)$row->cmid,
            'customcertid' => (int)$row->customcertid,
            'courseid' => (int)$row->courseid,
            'reason' => null,
            'row' => $row,
        ];
    }

    /**
     * Staff proxy URL into customcert (requires viewreport on that activity).
     * Prefer {@see generate_pdf_for_user()} when packaging or when capability may be missing.
     */
    public static function get_proxy_download_url(int $cmid, int $userid): \moodle_url {
        return new \moodle_url('/mod/customcert/view.php', [
            'id' => $cmid,
            'downloadissue' => $userid,
        ]);
    }

    /**
     * Generate certificate PDF bytes for a learner (issues receivable cert if needed).
     *
     * @return string|null PDF binary or null when unavailable
     */
    public static function generate_pdf_for_user(int $userid, string $level): ?string {
        global $CFG, $DB;

        $info = self::get_level_certificate_availability($userid, $level);
        if (empty($info['available']) || empty($info['customcertid'])) {
            return null;
        }

        $customcert = $DB->get_record('customcert', ['id' => (int)$info['customcertid']], '*', MUST_EXIST);
        if (empty($customcert->templateid)) {
            return null;
        }

        // Match downloadown: create issue row when receivable but not yet issued.
        if (empty($info['row']->issueid)
                && class_exists('\\mod_customcert\\certificate')) {
            \mod_customcert\certificate::issue_certificate((int)$customcert->id, $userid);
        }

        $templaterec = $DB->get_record('customcert_templates', ['id' => (int)$customcert->templateid], '*', MUST_EXIST);
        require_once($CFG->dirroot . '/mod/customcert/classes/template.php');
        $template = new \mod_customcert\template($templaterec);
        $pdf = $template->generate_pdf(false, $userid, true);
        return is_string($pdf) && $pdf !== '' ? $pdf : null;
    }

    /**
     * Build one zip of PDFs for users who have an available Moodle certificate at one level.
     *
     * @param int[] $userids
     * @return array{ok:bool,path:?string,filename:string,included:int,skipped:int,errors:string[]}
     */
    public static function build_zip_for_users(array $userids, string $level, string $zipbasename = 'certificates'): array {
        require_once(__DIR__ . '/candidate_manager.php');

        $userids = array_values(array_unique(array_map('intval', $userids)));
        $included = 0;
        $skipped = 0;
        $errors = [];
        $files = [];

        foreach ($userids as $userid) {
            if ($userid <= 0) {
                continue;
            }
            $info = self::get_level_certificate_availability($userid, $level);
            if (empty($info['available'])) {
                $skipped++;
                continue;
            }
            try {
                $pdf = self::generate_pdf_for_user($userid, $level);
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = 'userid ' . $userid . ': ' . $e->getMessage();
                debugging('cert zip pdf failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
                continue;
            }
            if ($pdf === null || $pdf === '') {
                $skipped++;
                continue;
            }
            $user = \core_user::get_user($userid, 'id,firstname,lastname,username', MUST_EXIST);
            $cid = candidate_manager::get_candidate_id($userid) ?? (string)$user->username;
            $safeid = preg_replace('/[^0-9A-Za-z_-]+/', '_', $cid);
            $safename = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]+/u', '_', fullname($user));
            $basename = $safeid . '_' . $safename . '.pdf';
            $n = 1;
            $final = $basename;
            while (isset($files[$final])) {
                $final = $safeid . '_' . $safename . '_' . $n . '.pdf';
                $n++;
            }
            $files[$final] = $pdf;
            $included++;
        }

        return self::pack_pdf_zip($files, $zipbasename, $included, $skipped, $errors);
    }

    /**
     * Build one zip of PDFs for users across all mapped levels that have a certificate.
     *
     * @param int[] $userids
     * @return array{ok:bool,path:?string,filename:string,included:int,skipped:int,errors:string[]}
     */
    public static function build_zip_for_users_all_levels(array $userids, string $zipbasename = 'certificates'): array {
        require_once(__DIR__ . '/candidate_manager.php');
        require_once(__DIR__ . '/session_manager.php');

        $userids = array_values(array_unique(array_map('intval', $userids)));
        $levels = array_keys(session_manager::level_options());
        $included = 0;
        $skipped = 0;
        $errors = [];
        $files = [];

        foreach ($userids as $userid) {
            if ($userid <= 0) {
                continue;
            }
            $userhad = false;
            try {
                $user = \core_user::get_user($userid, 'id,firstname,lastname,username', MUST_EXIST);
            } catch (\Throwable $e) {
                $skipped++;
                continue;
            }
            $cid = candidate_manager::get_candidate_id($userid) ?? (string)$user->username;
            $safeid = preg_replace('/[^0-9A-Za-z_-]+/', '_', $cid);
            $safename = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]+/u', '_', fullname($user));

            foreach ($levels as $level) {
                $info = self::get_level_certificate_availability($userid, (string)$level);
                if (empty($info['available'])) {
                    continue;
                }
                try {
                    $pdf = self::generate_pdf_for_user($userid, (string)$level);
                } catch (\Throwable $e) {
                    $errors[] = 'userid ' . $userid . ' ' . $level . ': ' . $e->getMessage();
                    debugging('cert zip pdf failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
                    continue;
                }
                if ($pdf === null || $pdf === '') {
                    continue;
                }
                $basename = $safeid . '_' . $safename . '_' . $level . '.pdf';
                $n = 1;
                $final = $basename;
                while (isset($files[$final])) {
                    $final = $safeid . '_' . $safename . '_' . $level . '_' . $n . '.pdf';
                    $n++;
                }
                $files[$final] = $pdf;
                $included++;
                $userhad = true;
            }
            if (!$userhad) {
                $skipped++;
            }
        }

        return self::pack_pdf_zip($files, $zipbasename, $included, $skipped, $errors);
    }

    /**
     * Build one zip from explicit userid+level selections (site batch cart).
     *
     * @param array<int,array{userid:int,level:string}> $selections
     * @return array{ok:bool,path:?string,filename:string,included:int,skipped:int,errors:string[]}
     */
    public static function build_zip_for_selections(array $selections, string $zipbasename = 'certificates'): array {
        require_once(__DIR__ . '/candidate_manager.php');
        require_once(__DIR__ . '/session_manager.php');

        $validlevels = array_flip(array_keys(session_manager::level_options()));
        $included = 0;
        $skipped = 0;
        $errors = [];
        $files = [];
        $seen = [];

        foreach ($selections as $sel) {
            $userid = (int)($sel['userid'] ?? 0);
            $level = trim((string)($sel['level'] ?? ''));
            if ($userid <= 0 || $level === '' || !isset($validlevels[$level])) {
                $skipped++;
                continue;
            }
            $ukey = $userid . ':' . $level;
            if (isset($seen[$ukey])) {
                continue;
            }
            $seen[$ukey] = true;

            $info = self::get_level_certificate_availability($userid, $level);
            if (empty($info['available'])) {
                $skipped++;
                continue;
            }
            try {
                $user = \core_user::get_user($userid, 'id,firstname,lastname,username', MUST_EXIST);
                $pdf = self::generate_pdf_for_user($userid, $level);
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = 'userid ' . $userid . ' ' . $level . ': ' . $e->getMessage();
                debugging('cert zip pdf failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
                continue;
            }
            if ($pdf === null || $pdf === '') {
                $skipped++;
                continue;
            }
            $cid = candidate_manager::get_candidate_id($userid) ?? (string)$user->username;
            $safeid = preg_replace('/[^0-9A-Za-z_-]+/', '_', $cid);
            $safename = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]+/u', '_', fullname($user));
            $basename = $safeid . '_' . $safename . '_' . $level . '.pdf';
            $n = 1;
            $final = $basename;
            while (isset($files[$final])) {
                $final = $safeid . '_' . $safename . '_' . $level . '_' . $n . '.pdf';
                $n++;
            }
            $files[$final] = $pdf;
            $included++;
        }

        return self::pack_pdf_zip($files, $zipbasename, $included, $skipped, $errors);
    }

    /**
     * @param array<string,string> $files
     * @return array{ok:bool,path:?string,filename:string,included:int,skipped:int,errors:string[]}
     */
    private static function pack_pdf_zip(
        array $files,
        string $zipbasename,
        int $included,
        int $skipped,
        array $errors
    ): array {
        if ($included === 0 || empty($files)) {
            return [
                'ok' => false,
                'path' => null,
                'filename' => '',
                'included' => 0,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        }

        $zipbasename = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $zipbasename);
        if ($zipbasename === '') {
            $zipbasename = 'certificates';
        }
        $filename = $zipbasename . '.zip';
        $path = make_request_directory() . '/' . $filename;

        if (!class_exists('ZipArchive')) {
            return [
                'ok' => false,
                'path' => null,
                'filename' => $filename,
                'included' => 0,
                'skipped' => $skipped + $included,
                'errors' => array_merge($errors, ['ZipArchive unavailable']),
            ];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return [
                'ok' => false,
                'path' => null,
                'filename' => $filename,
                'included' => 0,
                'skipped' => $skipped + $included,
                'errors' => array_merge($errors, ['Cannot create zip']),
            ];
        }
        foreach ($files as $name => $bytes) {
            $zip->addFromString(str_replace('\\', '/', $name), $bytes);
        }
        $zip->close();

        return [
            'ok' => true,
            'path' => $path,
            'filename' => $filename,
            'included' => $included,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
