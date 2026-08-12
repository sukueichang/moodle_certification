<?php
/**
 * Lifelong examinee candidate ID allocation.
 *
 * Format: [ROC year 3 digits][serial 3 digits], e.g. 2026 -> 115001.
 * Continuity: next ID = max(plugin table, existing Moodle usernames matching the pattern) + 1
 * for the current ROC year. Never overwrite an existing ID.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class candidate_manager {

    public const SCENARIO_NEW = 'new';
    public const SCENARIO_ISSUE_ID = 'issue_id';
    public const SCENARIO_KEEP_ID = 'keep_id';

    public static function roc_year(?int $timestamp = null): int {
        return (int)date('Y', $timestamp ?? time()) - 1911;
    }

    /**
     * Default Moodle login password for an examinee: Tmai + candidate ID.
     */
    public static function default_password_for(string $candidateid): string {
        $candidateid = trim($candidateid);
        return 'Tmai' . $candidateid;
    }

    /**
     * True when value looks like a certification candidate ID (6 digits).
     */
    public static function is_candidate_id_format(string $value): bool {
        return (bool)preg_match('/^\d{6}$/', trim($value));
    }

    /**
     * Resolve candidate ID for a user: plugin table first, then username fallback.
     */
    public static function get_candidate_id(int $userid): ?string {
        global $DB;

        $val = $DB->get_field('local_tm_cert_candidates', 'candidateid', ['userid' => $userid]);
        if ($val !== false && self::is_candidate_id_format((string)$val)) {
            return (string)$val;
        }

        $username = $DB->get_field('user', 'username', ['id' => $userid, 'deleted' => 0]);
        if ($username !== false && self::is_candidate_id_format((string)$username)) {
            return (string)$username;
        }
        return null;
    }

    /**
     * Map userid => candidateid for a set of users.
     *
     * @param int[] $userids
     * @return array<int,string>
     */
    public static function get_candidate_ids_for_users(array $userids): array {
        $out = [];
        foreach ($userids as $userid) {
            $cid = self::get_candidate_id((int)$userid);
            if ($cid !== null) {
                $out[(int)$userid] = $cid;
            }
        }
        return $out;
    }

    /**
     * Bind an already-chosen candidate ID to a user (idempotent).
     *
     * If the ID is held by a missing/deleted Moodle user, reclaim it.
     * If held by another active user, throw a clear conflict error.
     */
    public static function bind_candidate_id(int $userid, string $candidateid, int $createdby = 0): void {
        global $DB, $USER;

        $candidateid = trim($candidateid);
        if (!self::is_candidate_id_format($candidateid)) {
            throw new \moodle_exception('error_candidate_allocate_failed', 'local_tm_certification');
        }
        $existing = $DB->get_record('local_tm_cert_candidates', ['userid' => $userid]);
        if ($existing) {
            return;
        }

        $taken = $DB->get_record('local_tm_cert_candidates', ['candidateid' => $candidateid]);
        if ($taken && (int)$taken->userid !== $userid) {
            if (self::is_binding_reclaimable((int)$taken->userid)) {
                $DB->delete_records('local_tm_cert_candidates', ['id' => (int)$taken->id]);
            } else {
                throw new \moodle_exception(
                    'error_candidate_id_in_use',
                    'local_tm_certification',
                    '',
                    $candidateid
                );
            }
        }

        $record = new \stdClass();
        $record->userid = $userid;
        $record->candidateid = $candidateid;
        $record->timecreated = time();
        $record->createdby = $createdby > 0 ? $createdby : (int)$USER->id;
        try {
            $DB->insert_record('local_tm_cert_candidates', $record);
        } catch (\dml_exception $e) {
            // Concurrent insert for same userid is fine.
            if (!$DB->record_exists('local_tm_cert_candidates', ['userid' => $userid])) {
                // Race: another process may have claimed the ID; retry reclaim once.
                $takenagain = $DB->get_record('local_tm_cert_candidates', ['candidateid' => $candidateid]);
                if ($takenagain && (int)$takenagain->userid !== $userid
                        && self::is_binding_reclaimable((int)$takenagain->userid)) {
                    $DB->delete_records('local_tm_cert_candidates', ['id' => (int)$takenagain->id]);
                    $DB->insert_record('local_tm_cert_candidates', $record);
                    return;
                }
                throw new \moodle_exception('error_candidate_allocate_failed', 'local_tm_certification');
            }
        }
    }

    /**
     * True when a candidates.userid no longer belongs to an active local user.
     */
    public static function is_binding_reclaimable(int $bounduserid): bool {
        global $DB, $CFG;

        if ($bounduserid <= 0) {
            return true;
        }
        $user = $DB->get_record('user', [
            'id' => $bounduserid,
            'mnethostid' => $CFG->mnet_localhost_id,
        ], 'id,deleted', IGNORE_MISSING);
        if (!$user) {
            return true;
        }
        return (int)$user->deleted === 1;
    }

    /**
     * Ensure user has a lifelong candidate ID. Never overwrite an existing one.
     * Backfills plugin table when ID is already carried as Moodle username.
     */
    public static function ensure_candidate_id(int $userid, int $createdby = 0): string {
        global $DB, $USER;

        $existing = self::get_candidate_id($userid);
        if ($existing !== null) {
            if (!$DB->record_exists('local_tm_cert_candidates', ['userid' => $userid])) {
                self::bind_candidate_id($userid, $existing, $createdby);
            }
            return $existing;
        }

        $createdby = $createdby > 0 ? $createdby : (int)$USER->id;
        $attempts = 0;
        while ($attempts < 8) {
            $attempts++;
            $candidateid = self::next_candidate_id();
            try {
                self::bind_candidate_id($userid, $candidateid, $createdby);
                return $candidateid;
            } catch (\Throwable $e) {
                $again = self::get_candidate_id($userid);
                if ($again !== null) {
                    return $again;
                }
            }
        }
        throw new \moodle_exception('error_candidate_allocate_failed', 'local_tm_certification');
    }

    /**
     * Peek the next free candidate ID for the current ROC year (no DB write).
     * Reuses gaps: e.g. if 115178 was deleted, the next allocate can be 115178 again.
     * Only counts active (non-deleted) Moodle users and live candidate bindings.
     */
    public static function next_candidate_id(): string {
        $year = self::roc_year();
        $prefix = sprintf('%03d', $year);
        $used = self::used_candidate_numbers_for_year($year);
        $start = (int)($prefix . '001');
        $end = (int)($prefix . '999');
        for ($n = $start; $n <= $end; $n++) {
            if (!isset($used[$n])) {
                return sprintf('%06d', $n);
            }
        }
        throw new \moodle_exception('error_candidate_year_full', 'local_tm_certification', '', $year);
    }

    /**
     * Release candidate ID binding when an examinee account is deleted.
     */
    public static function release_for_userid(int $userid): void {
        global $DB;
        $DB->delete_records('local_tm_cert_candidates', ['userid' => $userid]);
    }

    /**
     * Classify email against Moodle users + candidate sources (no writes).
     *
     * @return array{scenario:string,userid:?int,candidateid:?string,email:string,firstname:string,lastname:string,institution:string}
     */
    public static function classify_row(string $email, string $firstname, string $lastname, string $institution = ''): array {
        global $DB, $CFG;

        $email = \core_text::strtolower(trim($email));
        $firstname = clean_param(trim($firstname), PARAM_TEXT);
        $lastname = clean_param(trim($lastname), PARAM_TEXT);
        $institution = clean_param(trim($institution), PARAM_TEXT);

        $user = $DB->get_record('user', [
            'email' => $email,
            'deleted' => 0,
            'mnethostid' => $CFG->mnet_localhost_id,
        ], 'id,firstname,lastname,institution,username', IGNORE_MULTIPLE);

        if (!$user) {
            return [
                'scenario' => self::SCENARIO_NEW,
                'userid' => null,
                'candidateid' => null,
                'email' => $email,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'institution' => $institution,
            ];
        }

        $cid = self::get_candidate_id((int)$user->id);
        return [
            'scenario' => $cid !== null ? self::SCENARIO_KEEP_ID : self::SCENARIO_ISSUE_ID,
            'userid' => (int)$user->id,
            'candidateid' => $cid,
            'email' => $email,
            'firstname' => $firstname !== '' ? $firstname : (string)$user->firstname,
            'lastname' => $lastname !== '' ? $lastname : (string)$user->lastname,
            'institution' => $institution !== '' ? $institution : (string)$user->institution,
        ];
    }

    /**
     * Map of used candidate numbers for a ROC year (key = int id, value = true).
     *
     * @return array<int,bool>
     */
    private static function used_candidate_numbers_for_year(int $year): array {
        global $DB, $CFG;

        $prefix = sprintf('%03d', $year);
        $used = [];

        // Live bindings only (ignore rows whose user was soft-deleted).
        $fromtable = $DB->get_fieldset_sql(
            "SELECT c.candidateid
               FROM {local_tm_cert_candidates} c
               JOIN {user} u ON u.id = c.userid AND u.deleted = 0
              WHERE c.candidateid LIKE ?",
            [$prefix . '%']
        );
        foreach ($fromtable as $cid) {
            if (self::is_candidate_id_format((string)$cid)) {
                $used[(int)$cid] = true;
            }
        }

        // Active examinee usernames (= candidate ID).
        $usernames = $DB->get_fieldset_sql(
            "SELECT username
               FROM {user}
              WHERE deleted = 0
                AND mnethostid = :mnet
                AND username LIKE :prefix",
            [
                'mnet' => $CFG->mnet_localhost_id,
                'prefix' => $prefix . '%',
            ]
        );
        foreach ($usernames as $username) {
            if (self::is_candidate_id_format((string)$username)) {
                $used[(int)$username] = true;
            }
        }

        return $used;
    }
}
