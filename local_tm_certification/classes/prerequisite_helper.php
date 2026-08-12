<?php
/**
 * Prerequisite checks for intermediate / advanced enrolment.
 *
 * Mid/adv never create accounts; lookup by email and require previous level pass
 * (written if required + practical), any instructor/venue.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/session_manager.php');
require_once(__DIR__ . '/assessment_manager.php');
require_once(__DIR__ . '/candidate_manager.php');
require_once(__DIR__ . '/enrolment_manager.php');

class prerequisite_helper {

    /** Row may join mid/adv roster (existing account + prev level passed). */
    public const SCENARIO_PREREQ_OK = 'prereq_ok';

    /** Row blocked from mid/adv roster. */
    public const SCENARIO_PREREQ_FAIL = 'prereq_fail';

    public const REASON_ACCOUNT_NOT_FOUND = 'account_not_found';
    public const REASON_WRITTEN_FAIL = 'written_fail';
    public const REASON_PRACTICAL_FAIL = 'practical_fail';
    public const REASON_NO_CANDIDATE_ID = 'no_candidate_id';
    public const REASON_PREV_NOT_PASSED = 'prev_level_not_passed';

    /**
     * Previous level in the chain, or null for basic (no prerequisite).
     */
    public static function previous_level(string $targetlevel): ?string {
        switch ($targetlevel) {
            case session_manager::LEVEL_INTERMEDIATE:
                return session_manager::LEVEL_BASIC;
            case session_manager::LEVEL_ADVANCED:
                return session_manager::LEVEL_INTERMEDIATE;
            default:
                return null;
        }
    }

    /**
     * Intermediate / advanced require prerequisite (no new accounts).
     */
    public static function requires_prerequisite(string $level): bool {
        return self::previous_level($level) !== null;
    }

    /**
     * Find active local user by email (case-insensitive match via stored lower email in callers).
     */
    public static function find_user_by_email(string $email): ?\stdClass {
        global $DB, $CFG;

        $email = \core_text::strtolower(trim($email));
        if ($email === '' || !validate_email($email)) {
            return null;
        }
        $user = $DB->get_record('user', [
            'email' => $email,
            'deleted' => 0,
            'mnethostid' => $CFG->mnet_localhost_id,
        ], '*', IGNORE_MULTIPLE);
        return $user ?: null;
    }

    /**
     * Whether the user has overall-passed the given level on any session (any venue/instructor).
     *
     * Written (if required) uses level quiz grade; practical uses any non-cancelled enrol
     * on a session of that level with practicalpass=1.
     *
     * @return array{
     *   passed:bool,
     *   written_ok:bool,
     *   practical_ok:bool,
     *   written_required:bool,
     *   reasons:string[]
     * }
     */
    public static function evaluate_level_pass(int $userid, string $level): array {
        global $DB;

        $writtenrequired = assessment_manager::has_written_exam($level);
        $written = assessment_manager::check_written_result($userid, $level);
        $writtenok = !$writtenrequired || (!empty($written['passed']));

        $sql = "SELECT e.id, e.practicalpass, e.practicalscore, e.status
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

        $practicalok = false;
        foreach ($enrols as $enrol) {
            if ((int)$enrol->practicalpass === 1
                    && $enrol->practicalscore !== null && $enrol->practicalscore !== '') {
                $practicalok = true;
                break;
            }
        }

        $passed = $practicalok && $writtenok;
        $reasons = [];
        if (!$passed) {
            if ($writtenrequired && !$writtenok) {
                $reasons[] = self::REASON_WRITTEN_FAIL;
            }
            if (!$practicalok) {
                $reasons[] = self::REASON_PRACTICAL_FAIL;
            }
            if (empty($reasons)) {
                $reasons[] = self::REASON_PREV_NOT_PASSED;
            }
        }

        return [
            'passed' => $passed,
            'written_ok' => $writtenok,
            'practical_ok' => $practicalok,
            'written_required' => $writtenrequired,
            'reasons' => $reasons,
        ];
    }

    /**
     * Classify a roster row for a target session level (email-first).
     *
     * Basic → existing three-scenario classify.
     * Mid/adv → never create; prerequisite check + candidate ID required.
     *
     * @return array{
     *   scenario:string,
     *   eligible:bool,
     *   reasons:string[],
     *   userid:?int,
     *   candidateid:?string,
     *   email:string,
     *   firstname:string,
     *   lastname:string,
     *   institution:string,
     *   prevlevel:?string
     * }
     */
    public static function evaluate_row(
        string $email,
        string $firstname,
        string $lastname,
        string $institution,
        string $targetlevel
    ): array {
        $email = \core_text::strtolower(trim($email));
        $firstname = clean_param(trim($firstname), PARAM_TEXT);
        $lastname = clean_param(trim($lastname), PARAM_TEXT);
        $institution = clean_param(trim($institution), PARAM_TEXT);

        if (!self::requires_prerequisite($targetlevel)) {
            $item = candidate_manager::classify_row($email, $firstname, $lastname, $institution);
            $item['eligible'] = true;
            $item['reasons'] = [];
            $item['prevlevel'] = null;
            return $item;
        }

        $prev = self::previous_level($targetlevel);
        $user = self::find_user_by_email($email);
        if (!$user) {
            return [
                'scenario' => self::SCENARIO_PREREQ_FAIL,
                'eligible' => false,
                'reasons' => [self::REASON_ACCOUNT_NOT_FOUND],
                'userid' => null,
                'candidateid' => null,
                'email' => $email,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'institution' => $institution,
                'prevlevel' => $prev,
            ];
        }

        $displaynamefirst = $firstname !== '' ? $firstname : (string)$user->firstname;
        $displaynamelast = $lastname !== '' ? $lastname : (string)$user->lastname;
        $displayinst = $institution !== '' ? $institution : (string)$user->institution;
        $candidateid = candidate_manager::get_candidate_id((int)$user->id);

        $levelpass = self::evaluate_level_pass((int)$user->id, (string)$prev);
        $reasons = $levelpass['reasons'];
        if ($candidateid === null) {
            $reasons[] = self::REASON_NO_CANDIDATE_ID;
        }

        if (!$levelpass['passed'] || $candidateid === null) {
            // Deduplicate reasons.
            $reasons = array_values(array_unique($reasons));
            return [
                'scenario' => self::SCENARIO_PREREQ_FAIL,
                'eligible' => false,
                'reasons' => $reasons,
                'userid' => (int)$user->id,
                'candidateid' => $candidateid,
                'email' => $email,
                'firstname' => $displaynamefirst,
                'lastname' => $displaynamelast,
                'institution' => $displayinst,
                'prevlevel' => $prev,
            ];
        }

        return [
            'scenario' => self::SCENARIO_PREREQ_OK,
            'eligible' => true,
            'reasons' => [],
            'userid' => (int)$user->id,
            'candidateid' => $candidateid,
            'email' => $email,
            'firstname' => $displaynamefirst,
            'lastname' => $displaynamelast,
            'institution' => $displayinst,
            'prevlevel' => $prev,
        ];
    }

    /**
     * Human-readable reason labels (already get_string'd).
     *
     * @param string[] $reasons
     * @return string[]
     */
    public static function reason_labels(array $reasons): array {
        $labels = [];
        foreach ($reasons as $code) {
            $key = 'prereq_reason_' . $code;
            if (get_string_manager()->string_exists($key, 'local_tm_certification')) {
                $labels[] = get_string($key, 'local_tm_certification');
            } else {
                $labels[] = $code;
            }
        }
        return $labels;
    }

    /**
     * Join reason labels for notifications / table cells.
     */
    public static function format_reasons(array $reasons): string {
        $labels = self::reason_labels($reasons);
        return $labels ? implode('；', $labels) : '';
    }
}
