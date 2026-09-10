<?php
/**
 * Assessment blueprint + written/practical helpers.
 *
 * Per-level components (quiz / manual) drive pass rules. Legacy plugin config
 * keys are kept and used only to seed empty blueprints.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/catalog_manager.php');
require_once(__DIR__ . '/session_manager.php');
require_once(__DIR__ . '/attendance_helper.php');
require_once(__DIR__ . '/enrolment_manager.php');

class assessment_manager {

    public const TYPE_QUIZ = 'quiz';
    /** Manual 0–100 score; on save, plugin writes 〔參〕/〔缺〕 to mapped attendance. */
    public const TYPE_MANUAL = 'manual';
    /** Optional: pass/fail read from mod_attendance 〔參〕 only (no score entry). */
    public const TYPE_ATTENDANCE = 'attendance';

    public const CODE_WRITTEN = 'written';
    public const CODE_PRACTICAL = 'practical';

    /**
     * @return string[]
     */
    public static function type_options(): array {
        return [
            self::TYPE_MANUAL => get_string('assess_type_manual', 'local_tm_certification'),
            self::TYPE_QUIZ => get_string('assess_type_quiz', 'local_tm_certification'),
            self::TYPE_ATTENDANCE => get_string('assess_type_attendance', 'local_tm_certification'),
        ];
    }

    /**
     * Practical pass threshold for a level (first active manual component, else legacy config).
     */
    public static function practical_pass_score(string $level): float {
        self::ensure_blueprint($level);
        $manuals = self::get_components($level, self::TYPE_MANUAL);
        if (!empty($manuals)) {
            $first = reset($manuals);
            return (float)$first->passscore;
        }
        // Attendance-based practicals have no numeric threshold.
        $atts = self::get_components($level, self::TYPE_ATTENDANCE);
        if (!empty($atts)) {
            return 0.0;
        }
        $v = get_config('local_tm_certification', 'practical_pass_' . $level);
        if ($v === false || $v === '' || $v === null) {
            return 90.0;
        }
        return (float)$v;
    }

    /**
     * Whether this level requires a written (quiz) exam.
     */
    public static function has_written_exam(string $level): bool {
        self::ensure_blueprint($level);
        return !empty(self::get_components($level, self::TYPE_QUIZ));
    }

    /**
     * Written pass score for a level (first quiz component, else legacy config).
     */
    public static function written_pass_score(string $level): float {
        self::ensure_blueprint($level);
        $quizzes = self::get_components($level, self::TYPE_QUIZ);
        if (!empty($quizzes)) {
            $first = reset($quizzes);
            return (float)$first->passscore;
        }
        return (float)get_config('local_tm_certification', 'written_pass_' . $level);
    }

    /**
     * Mapped quiz course-module id for written exam (0 = none).
     */
    public static function written_quiz_cmid(string $level): int {
        self::ensure_blueprint($level);
        $quizzes = self::get_components($level, self::TYPE_QUIZ);
        if (!empty($quizzes)) {
            $first = reset($quizzes);
            return (int)$first->quizcmid;
        }
        return (int)get_config('local_tm_certification', 'written_quiz_cmid_' . $level);
    }

    /**
     * Active components for a level, ordered by sortorder / id.
     *
     * @param string|null $type Filter by type, or null for all.
     * @return \stdClass[]
     */
    public static function get_components(string $level, ?string $type = null, bool $activeonly = true): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_tm_cert_assess_comp')) {
            return [];
        }

        $params = ['certlevel' => $level];
        $where = 'certlevel = :certlevel';
        if ($activeonly) {
            $where .= ' AND active = 1';
        }
        if ($type !== null) {
            $where .= ' AND type = :type';
            $params['type'] = $type;
        }
        return array_values($DB->get_records_select(
            'local_tm_cert_assess_comp',
            $where,
            $params,
            'sortorder ASC, id ASC'
        ));
    }

    /**
     * @return \stdClass|null
     */
    public static function get_component(int $id): ?\stdClass {
        global $DB;
        if ($id <= 0 || !$DB->get_manager()->table_exists('local_tm_cert_assess_comp')) {
            return null;
        }
        $row = $DB->get_record('local_tm_cert_assess_comp', ['id' => $id]);
        return $row ?: null;
    }

    /**
     * Ensure the level has at least one blueprint row (seed from legacy config if empty).
     */
    public static function ensure_blueprint(string $level): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('local_tm_cert_assess_comp')) {
            return;
        }
        if ($DB->record_exists('local_tm_cert_assess_comp', ['certlevel' => $level])) {
            return;
        }
        self::seed_level_from_legacy_config($level);
    }

    /**
     * Seed all levels that have no components yet. Never deletes existing rows or config.
     */
    public static function seed_blueprints_from_legacy_config(): void {
        foreach (array_keys(session_manager::level_options()) as $level) {
            self::ensure_blueprint($level);
        }
    }

    /**
     * Build default written + practical (or practical-only) from legacy plugin settings.
     */
    public static function seed_level_from_legacy_config(string $level): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_tm_cert_assess_comp')) {
            return;
        }
        if ($DB->record_exists('local_tm_cert_assess_comp', ['certlevel' => $level])) {
            return;
        }

        $now = time();
        $sort = 0;
        $writtenenabled = (int)get_config('local_tm_certification', 'written_enabled_' . $level) === 1;
        if ($writtenenabled) {
            $row = (object)[
                'certlevel' => $level,
                'code' => self::CODE_WRITTEN,
                'type' => self::TYPE_QUIZ,
                'label' => get_string('result_item_written', 'local_tm_certification'),
                'passscore' => (float)get_config('local_tm_certification', 'written_pass_' . $level),
                'required' => 1,
                'sortorder' => $sort++,
                'quizcmid' => (int)get_config('local_tm_certification', 'written_quiz_cmid_' . $level),
                'active' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            if ($row->passscore <= 0) {
                $row->passscore = 60.0;
            }
            $DB->insert_record('local_tm_cert_assess_comp', $row);
        }

        $practicalpass = get_config('local_tm_certification', 'practical_pass_' . $level);
        $pscore = ($practicalpass === false || $practicalpass === '' || $practicalpass === null)
            ? 90.0
            : (float)$practicalpass;
        $DB->insert_record('local_tm_cert_assess_comp', (object)[
            'certlevel' => $level,
            'code' => self::CODE_PRACTICAL,
            'type' => self::TYPE_MANUAL,
            'label' => get_string('result_item_practical', 'local_tm_certification'),
            'passscore' => $pscore,
            'required' => 1,
            'sortorder' => $sort,
            'quizcmid' => (int)get_config('local_tm_certification', 'mapping_attendance_cmid_' . $level),
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Copy legacy level→attendance mapping into manual/attendance components that have no cmid yet.
     * Does not overwrite existing per-component links; does not delete mapping_attendance_cmid_*.
     */
    public static function migrate_level_attendance_into_manuals(): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_tm_cert_assess_comp')) {
            return;
        }
        $now = time();
        foreach (array_keys(session_manager::level_options()) as $level) {
            $levelcmid = (int)get_config('local_tm_certification', 'mapping_attendance_cmid_' . $level);
            if ($levelcmid <= 0) {
                continue;
            }
            $rows = $DB->get_records_select(
                'local_tm_cert_assess_comp',
                'certlevel = :level AND quizcmid = 0 AND type IN (:t1, :t2)',
                [
                    'level' => $level,
                    't1' => self::TYPE_MANUAL,
                    't2' => self::TYPE_ATTENDANCE,
                ]
            );
            foreach ($rows as $row) {
                $row->quizcmid = $levelcmid;
                $row->timemodified = $now;
                $DB->update_record('local_tm_cert_assess_comp', $row);
            }
        }
    }

    /**
     * Attendance activity cmid for one component (quizcmid), with level-map fallback.
     */
    public static function attendance_cmid_for_component(\stdClass $comp): int {
        $cmid = (int)$comp->quizcmid;
        if ($cmid > 0) {
            return $cmid;
        }
        return (int)catalog_manager::get_attendance_cmid_for_level((string)$comp->certlevel);
    }

    /**
     * Unique attendance cmids linked by active manual + attendance components for a level.
     * Falls back to legacy level mapping when none are set on components.
     *
     * @return int[]
     */
    public static function attendance_cmids_for_level(string $level): array {
        self::ensure_blueprint($level);
        $seen = [];
        foreach (array_merge(
            self::get_components($level, self::TYPE_MANUAL),
            self::get_components($level, self::TYPE_ATTENDANCE)
        ) as $comp) {
            $cmid = self::attendance_cmid_for_component($comp);
            if ($cmid > 0) {
                $seen[$cmid] = $cmid;
            }
        }
        if (empty($seen)) {
            $legacy = (int)catalog_manager::get_attendance_cmid_for_level($level);
            if ($legacy > 0) {
                $seen[$legacy] = $legacy;
            }
        }
        return array_values($seen);
    }

    /**
     * Convert attendance-type practical* rows back to manual score entry
     * (score ≥ passscore → plugin writes 〔參〕/〔缺〕). Restores passscore from legacy config when 0.
     */
    public static function convert_attendance_practicals_to_manual(): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_tm_cert_assess_comp')) {
            return;
        }
        $rows = $DB->get_records('local_tm_cert_assess_comp', ['type' => self::TYPE_ATTENDANCE]);
        $now = time();
        foreach ($rows as $row) {
            $code = (string)$row->code;
            if ($code !== self::CODE_PRACTICAL && strpos($code, 'practical') !== 0) {
                continue;
            }
            $row->type = self::TYPE_MANUAL;
            if ((float)$row->passscore <= 0) {
                $legacy = get_config('local_tm_certification', 'practical_pass_' . $row->certlevel);
                $row->passscore = ($legacy === false || $legacy === '' || $legacy === null)
                    ? 90.0
                    : (float)$legacy;
            }
            // Keep linked attendance cmid when converting type; fill from level map if empty.
            if ((int)$row->quizcmid <= 0) {
                $row->quizcmid = (int)get_config(
                    'local_tm_certification',
                    'mapping_attendance_cmid_' . $row->certlevel
                );
            }
            $row->timemodified = $now;
            $DB->update_record('local_tm_cert_assess_comp', $row);
        }
    }

    /**
     * @deprecated Kept for older upgrade paths; prefer convert_attendance_practicals_to_manual.
     */
    public static function convert_manual_practicals_to_attendance(): void {
        // No-op for new installs; historical upgrade 2026083113 may have run this once.
        global $DB;

        if (!$DB->get_manager()->table_exists('local_tm_cert_assess_comp')) {
            return;
        }
        $rows = $DB->get_records('local_tm_cert_assess_comp', ['type' => self::TYPE_MANUAL]);
        $now = time();
        foreach ($rows as $row) {
            $code = (string)$row->code;
            if ($code !== self::CODE_PRACTICAL && strpos($code, 'practical') !== 0) {
                continue;
            }
            $row->type = self::TYPE_ATTENDANCE;
            if ((int)$row->quizcmid <= 0) {
                $row->quizcmid = (int)get_config(
                    'local_tm_certification',
                    'mapping_attendance_cmid_' . $row->certlevel
                );
            }
            $row->timemodified = $now;
            $DB->update_record('local_tm_cert_assess_comp', $row);
        }
    }

    /**
     * Copy legacy enrol.practicalscore into assess_res for code=practical (once).
     */
    public static function migrate_legacy_enrol_scores_to_results(): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_tm_cert_assess_res')
                || !$DB->get_manager()->table_exists('local_tm_cert_assess_comp')) {
            return;
        }

        $practicals = $DB->get_records('local_tm_cert_assess_comp', [
            'code' => self::CODE_PRACTICAL,
            'type' => self::TYPE_MANUAL,
            'active' => 1,
        ]);
        if (empty($practicals)) {
            return;
        }
        $bylevel = [];
        foreach ($practicals as $comp) {
            $bylevel[$comp->certlevel] = $comp;
        }

        $sql = "SELECT e.id, e.practicalscore, e.practicalpass, e.timemodified, s.certlevel
                  FROM {local_tm_cert_enrols} e
                  JOIN {local_tm_cert_sessions} s ON s.id = e.sessionid
                 WHERE e.practicalscore IS NOT NULL";
        $enrols = $DB->get_recordset_sql($sql);
        $now = time();
        foreach ($enrols as $enrol) {
            $level = (string)$enrol->certlevel;
            if (empty($bylevel[$level])) {
                continue;
            }
            $compid = (int)$bylevel[$level]->id;
            if ($DB->record_exists('local_tm_cert_assess_res', [
                'enrolid' => (int)$enrol->id,
                'componentid' => $compid,
            ])) {
                continue;
            }
            $score = (float)$enrol->practicalscore;
            $threshold = (float)$bylevel[$level]->passscore;
            $DB->insert_record('local_tm_cert_assess_res', (object)[
                'enrolid' => (int)$enrol->id,
                'componentid' => $compid,
                'score' => round($score, 2),
                'passed' => ($score >= $threshold) ? 1 : 0,
                'timemodified' => (int)$enrol->timemodified > 0 ? (int)$enrol->timemodified : $now,
                'modifiedby' => 0,
            ]);
        }
        $enrols->close();
    }

    /**
     * Create or update a component. Does not touch legacy config keys.
     *
     * @param array $data keys: certlevel, code, type, label, passscore, sortorder, quizcmid, active
     */
    public static function save_component(array $data, int $id = 0): int {
        global $DB;

        $level = clean_param((string)($data['certlevel'] ?? ''), PARAM_ALPHANUMEXT);
        if (!array_key_exists($level, session_manager::level_options())) {
            throw new \moodle_exception('error_invalid_level', 'local_tm_certification');
        }
        $code = clean_param(strtolower(trim((string)($data['code'] ?? ''))), PARAM_ALPHANUMEXT);
        if ($code === '' || strlen($code) > 40) {
            throw new \moodle_exception('error_assess_code', 'local_tm_certification');
        }
        $type = (string)($data['type'] ?? self::TYPE_MANUAL);
        if ($type !== self::TYPE_QUIZ && $type !== self::TYPE_MANUAL && $type !== self::TYPE_ATTENDANCE) {
            throw new \moodle_exception('error_assess_type', 'local_tm_certification');
        }
        $label = clean_param(trim((string)($data['label'] ?? '')), PARAM_TEXT);
        if ($label === '') {
            throw new \moodle_exception('error_assess_label', 'local_tm_certification');
        }

        $now = time();
        $cmid = 0;
        if ($type === self::TYPE_QUIZ || $type === self::TYPE_ATTENDANCE || $type === self::TYPE_MANUAL) {
            $cmid = (int)($data['quizcmid'] ?? 0);
        }
        $row = (object)[
            'certlevel' => $level,
            'code' => $code,
            'type' => $type,
            'label' => $label,
            'passscore' => ($type === self::TYPE_ATTENDANCE) ? 0.0 : (float)($data['passscore'] ?? 0),
            // Every active component counts toward overall pass (no optional items).
            'required' => 1,
            'sortorder' => (int)($data['sortorder'] ?? 0),
            'quizcmid' => $cmid,
            'active' => isset($data['active']) ? (!empty($data['active']) ? 1 : 0) : 1,
            'timemodified' => $now,
        ];

        $dup = $DB->get_record('local_tm_cert_assess_comp', ['certlevel' => $level, 'code' => $code]);
        if ($dup && (int)$dup->id !== $id) {
            throw new \moodle_exception('error_assess_code_dup', 'local_tm_certification');
        }

        if ($id > 0) {
            $existing = $DB->get_record('local_tm_cert_assess_comp', ['id' => $id], '*', MUST_EXIST);
            $row->id = $id;
            $row->timecreated = (int)$existing->timecreated;
            $DB->update_record('local_tm_cert_assess_comp', $row);
            return $id;
        }
        $row->timecreated = $now;
        return (int)$DB->insert_record('local_tm_cert_assess_comp', $row);
    }

    /**
     * Soft-deactivate a component (keeps historical results).
     */
    public static function deactivate_component(int $id): void {
        global $DB;
        $row = $DB->get_record('local_tm_cert_assess_comp', ['id' => $id], '*', MUST_EXIST);
        $row->active = 0;
        $row->timemodified = time();
        $DB->update_record('local_tm_cert_assess_comp', $row);
    }

    /**
     * Quick-jump target for the written exam of a level (null if not configured).
     */
    public static function written_quiz_url(string $level): ?\moodle_url {
        global $DB;

        if (!self::has_written_exam($level)) {
            return null;
        }
        $cmid = self::written_quiz_cmid($level);
        if ($cmid <= 0) {
            return null;
        }
        $moduleid = (int)$DB->get_field('modules', 'id', ['name' => 'quiz']);
        if ($moduleid <= 0) {
            return null;
        }
        $cm = $DB->get_record('course_modules', [
            'id' => $cmid,
            'module' => $moduleid,
            'deletioninprogress' => 0,
        ], 'id', IGNORE_MISSING);
        if (!$cm) {
            return null;
        }
        return new \moodle_url('/mod/quiz/view.php', ['id' => $cmid]);
    }

    /**
     * Examinee dashboard button state for starting the written exam.
     *
     * @return array{show:bool,enabled:bool,url:?\moodle_url,label:string,hint:string}
     */
    public static function examinee_written_button(\stdClass $session, ?int $now = null): array {
        require_once(__DIR__ . '/session_manager.php');
        $usingrealtime = ($now === null);
        $now = $now ?? time();
        $label = get_string('examinee_start_written', 'local_tm_certification');
        $url = self::written_quiz_url((string)$session->certlevel);
        if ($url === null) {
            return [
                'show' => false,
                'enabled' => false,
                'url' => null,
                'label' => $label,
                'hint' => '',
            ];
        }

        if ($usingrealtime) {
            $session = session_manager::sync_auto_end($session);
        }
        $ended = ((int)$session->status === session_manager::STATUS_ENDED)
            || ((int)$session->endtime > 0 && (int)$session->endtime <= $now);
        if ($ended) {
            return [
                'show' => false,
                'enabled' => false,
                'url' => null,
                'label' => $label,
                'hint' => '',
            ];
        }

        $started = $now >= (int)$session->starttime;
        return [
            'show' => true,
            'enabled' => $started,
            'url' => $url,
            'label' => $label,
            'hint' => $started ? '' : get_string('examinee_start_written_wait', 'local_tm_certification'),
        ];
    }

    /**
     * List quizzes in a course for admin dropdowns.
     *
     * @return array<int,string> cmid => label
     */
    public static function quiz_options_for_course(int $courseid): array {
        global $DB;
        if ($courseid <= 0) {
            return [];
        }
        $moduleid = (int)$DB->get_field('modules', 'id', ['name' => 'quiz']);
        if ($moduleid <= 0) {
            return [];
        }
        $sql = 'SELECT cm.id, q.name
                  FROM {course_modules} cm
                  JOIN {quiz} q ON q.id = cm.instance
                 WHERE cm.course = :courseid
                   AND cm.module = :moduleid
                   AND cm.deletioninprogress = 0
              ORDER BY q.name ASC';
        $rows = $DB->get_records_sql($sql, ['courseid' => $courseid, 'moduleid' => $moduleid]);
        $out = [];
        foreach ($rows as $row) {
            $out[(int)$row->id] = format_string($row->name) . ' (cmid ' . (int)$row->id . ')';
        }
        return $out;
    }

    /**
     * Evaluate a quiz-type component from Moodle gradebook / quiz attempts.
     *
     * @return array{required:bool,passed:?bool,score:?float,warning:?string}
     */
    public static function check_quiz_component(int $userid, \stdClass $component): array {
        global $DB, $CFG;

        $required = !empty($component->active); // Active quiz always counts.
        $cmid = (int)$component->quizcmid;
        $pass = (float)$component->passscore;
        if ($cmid <= 0) {
            return ['required' => $required, 'passed' => null, 'score' => null, 'warning' => 'written_quiz_unmapped'];
        }

        $moduleid = (int)$DB->get_field('modules', 'id', ['name' => 'quiz']);
        $cm = $DB->get_record('course_modules', [
            'id' => $cmid,
            'module' => $moduleid,
            'deletioninprogress' => 0,
        ]);
        if (!$cm) {
            return ['required' => $required, 'passed' => null, 'score' => null, 'warning' => 'written_quiz_missing'];
        }

        require_once($CFG->libdir . '/gradelib.php');
        $grades = grade_get_grades((int)$cm->course, 'mod', 'quiz', (int)$cm->instance, $userid);
        $score = null;
        if (!empty($grades->items[0]->grades[$userid])) {
            $g = $grades->items[0]->grades[$userid];
            if (isset($g->grade) && $g->grade !== null && $g->grade !== '') {
                $score = (float)$g->grade;
            }
        }

        if ($score === null) {
            $quiz = $DB->get_record('quiz', ['id' => (int)$cm->instance], 'id,grade,sumgrades');
            $attemptrows = $DB->get_records_sql(
                "SELECT sumgrades
                   FROM {quiz_attempts}
                  WHERE quiz = :quiz AND userid = :userid AND state = :finished
               ORDER BY sumgrades DESC, id DESC",
                ['quiz' => (int)$cm->instance, 'userid' => $userid, 'finished' => 'finished'],
                0,
                1
            );
            $attempt = $attemptrows ? reset($attemptrows) : false;
            if ($attempt && $quiz && (float)$quiz->sumgrades > 0) {
                $score = ((float)$attempt->sumgrades / (float)$quiz->sumgrades) * (float)$quiz->grade;
            }
        }

        if ($score === null) {
            return ['required' => $required, 'passed' => false, 'score' => null, 'warning' => 'written_no_grade'];
        }

        return [
            'required' => $required,
            'passed' => $score >= $pass,
            'score' => $score,
            'warning' => null,
        ];
    }

    /**
     * Check written exam pass (first quiz component / legacy).
     *
     * @return array{required:bool,passed:?bool,score:?float,warning:?string}
     */
    public static function check_written_result(int $userid, string $level): array {
        self::ensure_blueprint($level);
        $quizzes = self::get_components($level, self::TYPE_QUIZ);
        if (empty($quizzes)) {
            return ['required' => false, 'passed' => null, 'score' => null, 'warning' => null];
        }
        // Aggregate: all quiz components must pass; expose first score for display.
        $first = null;
        $allpassed = true;
        $anynull = false;
        foreach ($quizzes as $comp) {
            $r = self::check_quiz_component($userid, $comp);
            if ($first === null) {
                $first = $r;
            }
            if ($r['passed'] === null) {
                $anynull = true;
                $allpassed = false;
            } else if (empty($r['passed'])) {
                $allpassed = false;
            }
        }
        if ($first === null) {
            return ['required' => false, 'passed' => null, 'score' => null, 'warning' => null];
        }
        $first['required'] = true;
        if ($anynull) {
            $first['passed'] = null;
        } else {
            $first['passed'] = $allpassed;
        }
        return $first;
    }

    /**
     * Manual component result for an enrol row.
     *
     * @return array{score:?float,passed:?bool,pending:bool}
     */
    public static function get_manual_result(int $enrolid, int $componentid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('local_tm_cert_assess_res')) {
            return ['score' => null, 'passed' => null, 'pending' => true];
        }
        $row = $DB->get_record('local_tm_cert_assess_res', [
            'enrolid' => $enrolid,
            'componentid' => $componentid,
        ]);
        if (!$row || $row->score === null || $row->score === '') {
            return ['score' => null, 'passed' => null, 'pending' => true];
        }
        return [
            'score' => (float)$row->score,
            'passed' => ((int)$row->passed === 1),
            'pending' => false,
        ];
    }

    /**
     * Save one manual component score; refresh enrol cache + attendance when ready.
     *
     * @return array{enrolid:int,componentid:int,passed:int,attendance:?array,threshold:float}
     */
    public static function save_component_score(int $enrolid, int $componentid, float $score, int $takenby = 0): array {
        global $DB, $USER;

        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        if ((int)$enrol->status === enrolment_manager::ENROL_CANCELLED) {
            throw new \moodle_exception('error_enrol_cancelled', 'local_tm_certification');
        }
        $session = session_manager::get_session((int)$enrol->sessionid);
        $comp = $DB->get_record('local_tm_cert_assess_comp', ['id' => $componentid], '*', MUST_EXIST);
        if ((string)$comp->certlevel !== (string)$session->certlevel) {
            throw new \moodle_exception('error_assess_level_mismatch', 'local_tm_certification');
        }
        if ((string)$comp->type !== self::TYPE_MANUAL || !(int)$comp->active) {
            throw new \moodle_exception('error_assess_not_manual', 'local_tm_certification');
        }
        if ($score < 0 || $score > 100) {
            throw new \moodle_exception('error_assess_score_range', 'local_tm_certification');
        }

        $threshold = (float)$comp->passscore;
        $pass = ($score >= $threshold) ? 1 : 0;
        $now = time();
        $modifiedby = $takenby > 0 ? $takenby : (int)$USER->id;

        $existing = $DB->get_record('local_tm_cert_assess_res', [
            'enrolid' => $enrolid,
            'componentid' => $componentid,
        ]);
        if ($existing) {
            $existing->score = round($score, 2);
            $existing->passed = $pass;
            $existing->timemodified = $now;
            $existing->modifiedby = $modifiedby;
            $DB->update_record('local_tm_cert_assess_res', $existing);
        } else {
            $DB->insert_record('local_tm_cert_assess_res', (object)[
                'enrolid' => $enrolid,
                'componentid' => $componentid,
                'score' => round($score, 2),
                'passed' => $pass,
                'timemodified' => $now,
                'modifiedby' => $modifiedby,
            ]);
        }

        $att = self::refresh_enrol_practical_and_attendance($enrolid);

        return [
            'enrolid' => $enrolid,
            'componentid' => $componentid,
            'passed' => $pass,
            'threshold' => $threshold,
            'attendance' => $att,
            'takenby' => $modifiedby,
        ];
    }

    /**
     * Compat: save score into the first active manual component.
     *
     * @return array{enrolid:int,practicalpass:int,attendance:?array,threshold:float}
     */
    public static function save_practical_score(int $enrolid, float $score, int $takenby = 0): array {
        global $DB;

        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        $session = session_manager::get_session((int)$enrol->sessionid);
        $level = (string)$session->certlevel;
        self::ensure_blueprint($level);
        $manuals = self::get_components($level, self::TYPE_MANUAL);
        if (empty($manuals)) {
            throw new \moodle_exception('error_assess_no_manual', 'local_tm_certification');
        }
        $first = reset($manuals);
        $result = self::save_component_score($enrolid, (int)$first->id, $score, $takenby);
        return [
            'enrolid' => $enrolid,
            'practicalpass' => (int)$result['passed'],
            'threshold' => $result['threshold'],
            'attendance' => $result['attendance'],
            'takenby' => $result['takenby'],
        ];
    }

    /**
     * Sync enrol.practicalscore/pass cache; write 〔參〕/〔缺〕 per scored manual to its linked attendance.
     *
     * @return array|null last attendance upsert result, or null if nothing written
     */
    public static function refresh_enrol_practical_and_attendance(int $enrolid): ?array {
        global $DB;

        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        $session = session_manager::get_session((int)$enrol->sessionid);
        $level = (string)$session->certlevel;
        $manuals = self::get_components($level, self::TYPE_MANUAL);

        $allscored = true;
        $allpassed = true;
        $firstscore = null;
        $lastatt = null;

        foreach ($manuals as $comp) {
            $r = self::get_manual_result($enrolid, (int)$comp->id);
            if ($r['pending']) {
                $allscored = false;
                $allpassed = false;
                continue;
            }
            if ($firstscore === null) {
                $firstscore = $r['score'];
            }
            if (empty($r['passed'])) {
                $allpassed = false;
            }

            // Immediately sync this component's score to its own attendance activity.
            $cmid = self::attendance_cmid_for_component($comp);
            $remarks = $comp->label . ':' . format_float((float)$r['score'], 2);
            $att = attendance_helper::upsert_practical_mark(
                (int)$session->id,
                (int)$enrol->userid,
                !empty($r['passed']),
                $remarks,
                $cmid
            );
            if (!empty($att['warning'])) {
                debugging('local_tm_certification practical attendance: ' . $att['warning'], DEBUG_DEVELOPER);
            }
            $lastatt = $att;
        }

        if (empty($manuals)) {
            $enrol->practicalscore = $firstscore;
            $enrol->practicalpass = 0;
            $enrol->timemodified = time();
            $DB->update_record('local_tm_cert_enrols', $enrol);
            return null;
        }

        $enrol->practicalscore = $firstscore;
        $enrol->practicalpass = ($allscored && $allpassed) ? 1 : 0;
        $enrol->timemodified = time();
        $DB->update_record('local_tm_cert_enrols', $enrol);

        return $lastatt;
    }

    /**
     * Overall exam pass for display (all active components must pass).
     *
     * @return array{
     *   practical_pass:bool,
     *   written:?array,
     *   overall:?bool,
     *   components:array
     * }
     */
    public static function evaluate_overall(int $enrolid): array {
        global $DB;
        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        $session = session_manager::get_session((int)$enrol->sessionid);
        $level = (string)$session->certlevel;
        self::ensure_blueprint($level);

        $components = self::get_components($level);
        $items = [];
        $overall = true;
        $pending = false;

        foreach ($components as $comp) {
            $item = [
                'id' => (int)$comp->id,
                'code' => (string)$comp->code,
                'type' => (string)$comp->type,
                'label' => (string)$comp->label,
                'passscore' => (float)$comp->passscore,
                'required' => true,
                'score' => null,
                'passed' => null,
                'pending' => true,
            ];
            if ((string)$comp->type === self::TYPE_QUIZ) {
                $r = self::check_quiz_component((int)$enrol->userid, $comp);
                $item['score'] = $r['score'];
                $item['passed'] = $r['passed'];
                $item['pending'] = ($r['passed'] === null);
                $item['warning'] = $r['warning'];
            } else if ((string)$comp->type === self::TYPE_ATTENDANCE) {
                $r = self::check_attendance_component((int)$enrol->userid, $comp, $session);
                $item['score'] = $r['score'];
                $item['passed'] = $r['passed'];
                $item['pending'] = !empty($r['pending']);
                $item['warning'] = $r['warning'];
            } else {
                $r = self::get_manual_result($enrolid, (int)$comp->id);
                // Legacy fallback: single practical code may only live on enrol row.
                if ($r['pending'] && (string)$comp->code === self::CODE_PRACTICAL
                        && $enrol->practicalscore !== null && $enrol->practicalscore !== '') {
                    $r = [
                        'score' => (float)$enrol->practicalscore,
                        'passed' => ((int)$enrol->practicalpass === 1),
                        'pending' => false,
                    ];
                }
                $item['score'] = $r['score'];
                $item['passed'] = $r['passed'];
                $item['pending'] = $r['pending'];
            }
            $items[] = $item;

            if ($item['pending'] || $item['passed'] === null) {
                $pending = true;
                $overall = false;
            } else if (empty($item['passed'])) {
                $overall = false;
            }
        }

        if (empty($components)) {
            $overall = null;
        } else if ($pending) {
            $overall = null;
        }

        $manuals = array_values(array_filter($items, static function ($i) {
            return $i['type'] === self::TYPE_MANUAL || $i['type'] === self::TYPE_ATTENDANCE;
        }));
        $practicalpass = false;
        $practicalpending = false;
        if (empty($manuals)) {
            $practicalpass = true;
        } else {
            $practicalpass = true;
            foreach ($manuals as $m) {
                if (!empty($m['pending']) || $m['passed'] === null) {
                    $practicalpending = true;
                    $practicalpass = false;
                    break;
                }
                if (empty($m['passed'])) {
                    $practicalpass = false;
                }
            }
        }

        $written = self::check_written_result((int)$enrol->userid, $level);

        return [
            'practical_pass' => $practicalpass && !$practicalpending,
            'written' => $written,
            'overall' => $overall,
            'components' => $items,
        ];
    }

    /**
     * Evaluate an attendance-type component (〔參〕 = pass).
     *
     * @return array{required:bool,passed:?bool,pending:bool,warning:?string,score:?float}
     */
    public static function check_attendance_component(int $userid, \stdClass $component, ?\stdClass $session = null): array {
        $cmid = self::attendance_cmid_for_component($component);
        if ($cmid <= 0 && $session && !empty($session->attendancecmid)) {
            $cmid = (int)$session->attendancecmid;
        }
        $prefer = 0;
        if ($session && !empty($session->attendancesessid) && $cmid > 0) {
            $resolved = attendance_helper::resolve_from_cmid($cmid);
            $sesscm = !empty($session->attendancecmid) ? (int)$session->attendancecmid : $cmid;
            if ($resolved && $sesscm === $cmid) {
                $prefer = (int)$session->attendancesessid;
            }
        }
        $r = attendance_helper::check_present_on_cmid($userid, $cmid, $prefer);
        $r['required'] = true;
        return $r;
    }

    /**
     * Whether user has all active practical components (manual + attendance) passed on any enrol.
     */
    public static function user_has_manuals_passed_for_level(int $userid, string $level): bool {
        global $DB;

        self::ensure_blueprint($level);
        $required = array_merge(
            self::get_components($level, self::TYPE_MANUAL),
            self::get_components($level, self::TYPE_ATTENDANCE)
        );

        $sql = "SELECT e.id, e.practicalpass, e.practicalscore, e.status, e.sessionid
                  FROM {local_tm_cert_enrols} e
                  JOIN {local_tm_cert_sessions} s ON s.id = e.sessionid
                 WHERE e.userid = :userid
                   AND s.certlevel = :level
                   AND e.status <> :cancelled";
        $enrols = $DB->get_records_sql($sql, [
            'userid' => $userid,
            'level' => $level,
            'cancelled' => enrolment_manager::ENROL_CANCELLED,
        ]);

        if (empty($required)) {
            foreach ($enrols as $enrol) {
                if ((int)$enrol->practicalpass === 1
                        && $enrol->practicalscore !== null && $enrol->practicalscore !== '') {
                    return true;
                }
            }
            return false;
        }

        foreach ($enrols as $enrol) {
            $session = session_manager::get_session((int)$enrol->sessionid);
            $ok = true;
            foreach ($required as $comp) {
                if ((string)$comp->type === self::TYPE_ATTENDANCE) {
                    $r = self::check_attendance_component($userid, $comp, $session);
                    if (!empty($r['pending']) || empty($r['passed'])) {
                        $ok = false;
                        break;
                    }
                    continue;
                }
                $r = self::get_manual_result((int)$enrol->id, (int)$comp->id);
                if ($r['pending'] && (string)$comp->code === self::CODE_PRACTICAL
                        && $enrol->practicalscore !== null && $enrol->practicalscore !== '') {
                    $r = [
                        'pending' => false,
                        'passed' => ((int)$enrol->practicalpass === 1),
                    ];
                }
                if ($r['pending'] || empty($r['passed'])) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return true;
            }
        }
        // Level-wide: all attendance components pass without tying to one enrol.
        $onlyatt = true;
        foreach ($required as $comp) {
            if ((string)$comp->type !== self::TYPE_ATTENDANCE) {
                $onlyatt = false;
                break;
            }
        }
        if ($onlyatt) {
            $ok = true;
            foreach ($required as $comp) {
                $r = self::check_attendance_component($userid, $comp, null);
                if (!empty($r['pending']) || empty($r['passed'])) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return true;
            }
        }
        return false;
    }
}
