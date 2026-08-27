<?php
/**
 * Written + practical assessment helpers.
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

    /**
     * Practical pass threshold for a level (default 90).
     */
    public static function practical_pass_score(string $level): float {
        $v = get_config('local_tm_certification', 'practical_pass_' . $level);
        if ($v === false || $v === '' || $v === null) {
            return 90.0;
        }
        return (float)$v;
    }

    /**
     * Whether this level requires a written exam.
     */
    public static function has_written_exam(string $level): bool {
        return (int)get_config('local_tm_certification', 'written_enabled_' . $level) === 1;
    }

    /**
     * Written pass score for a level (0 = not configured).
     */
    public static function written_pass_score(string $level): float {
        return (float)get_config('local_tm_certification', 'written_pass_' . $level);
    }

    /**
     * Mapped quiz course-module id for written exam (0 = none).
     */
    public static function written_quiz_cmid(string $level): int {
        return (int)get_config('local_tm_certification', 'written_quiz_cmid_' . $level);
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
     * Window aligns with the session: enabled only when starttime <= now < endtime
     * (and status is not already ended). Past endtime → button hidden (show=false).
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
            // Session over: hide the written-exam CTA entirely (results use a separate button).
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
     * Check written exam pass from Moodle quiz grade (best attempt percentage or raw grade).
     *
     * @return array{required:bool,passed:?bool,score:?float,warning:?string}
     */
    public static function check_written_result(int $userid, string $level): array {
        global $DB, $CFG;

        if (!self::has_written_exam($level)) {
            return ['required' => false, 'passed' => null, 'score' => null, 'warning' => null];
        }

        $cmid = self::written_quiz_cmid($level);
        $pass = self::written_pass_score($level);
        if ($cmid <= 0) {
            return ['required' => true, 'passed' => null, 'score' => null, 'warning' => 'written_quiz_unmapped'];
        }

        $moduleid = (int)$DB->get_field('modules', 'id', ['name' => 'quiz']);
        $cm = $DB->get_record('course_modules', [
            'id' => $cmid,
            'module' => $moduleid,
            'deletioninprogress' => 0,
        ]);
        if (!$cm) {
            return ['required' => true, 'passed' => null, 'score' => null, 'warning' => 'written_quiz_missing'];
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
            // Fallback: quiz_attempts sumgrades.
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
            return ['required' => true, 'passed' => false, 'score' => null, 'warning' => 'written_no_grade'];
        }

        return [
            'required' => true,
            'passed' => $score >= $pass,
            'score' => $score,
            'warning' => null,
        ];
    }

    /**
     * Save practical score, judge pass, sync attendance (best-effort).
     *
     * @return array{enrolid:int,practicalpass:int,attendance:?array}
     */
    public static function save_practical_score(int $enrolid, float $score, int $takenby = 0): array {
        global $DB, $USER;

        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        if ((int)$enrol->status === enrolment_manager::ENROL_CANCELLED) {
            throw new \moodle_exception('error_enrol_cancelled', 'local_tm_certification');
        }
        $session = session_manager::get_session((int)$enrol->sessionid);
        $threshold = self::practical_pass_score((string)$session->certlevel);
        $pass = ($score >= $threshold) ? 1 : 0;

        $enrol->practicalscore = round($score, 2);
        $enrol->practicalpass = $pass;
        $enrol->timemodified = time();
        $DB->update_record('local_tm_cert_enrols', $enrol);

        $remarks = (string)$enrol->practicalscore;
        $att = attendance_helper::upsert_practical_mark(
            (int)$session->id,
            (int)$enrol->userid,
            $pass === 1,
            $remarks
        );
        if (!empty($att['warning'])) {
            debugging('local_tm_certification practical attendance: ' . $att['warning'], DEBUG_DEVELOPER);
        }

        return [
            'enrolid' => $enrolid,
            'practicalpass' => $pass,
            'threshold' => $threshold,
            'attendance' => $att,
            'takenby' => $takenby > 0 ? $takenby : (int)$USER->id,
        ];
    }

    /**
     * Overall exam pass for display (written optional + practical).
     *
     * @return array{practical_pass:bool,written:?array,overall:?bool}
     */
    public static function evaluate_overall(int $enrolid): array {
        global $DB;
        $enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
        $session = session_manager::get_session((int)$enrol->sessionid);
        $practicalpass = ((int)$enrol->practicalpass === 1);
        $written = self::check_written_result((int)$enrol->userid, (string)$session->certlevel);

        $overall = null;
        if ($enrol->practicalscore === null || $enrol->practicalscore === '') {
            $overall = null;
        } else if (!$written['required']) {
            $overall = $practicalpass;
        } else if ($written['passed'] === null) {
            $overall = null;
        } else {
            $overall = $practicalpass && !empty($written['passed']);
        }

        return [
            'practical_pass' => $practicalpass,
            'written' => $written,
            'overall' => $overall,
        ];
    }
}
