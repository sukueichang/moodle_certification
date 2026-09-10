<?php
/**
 * Phased migration for pre-plugin examinee name format.
 *
 * Legacy: lastname = 6-digit candidate ID, firstname = full Chinese name.
 * Phase 1 binds the ID into local_tm_cert_candidates (names unchanged).
 * Phase 2 splits firstname into 姓/名 after phase 1 is verified.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/candidate_manager.php');

class legacy_name_migrator {

    public const STATUS_READY = 'ready';
    public const STATUS_ALREADY_BOUND = 'already_bound';
    public const STATUS_CONFLICT_TAKEN = 'conflict_taken';
    public const STATUS_CONFLICT_DIFFERENT = 'conflict_different';
    public const STATUS_NAME_SHORT = 'name_short';
    public const STATUS_PHASE2_READY = 'phase2_ready';
    public const STATUS_PHASE2_DONE = 'phase2_done';

    /** Resolve: give legacy user next free ID; holder keeps contested ID. */
    public const DECISION_ASSIGN_NEXT = 'assign_next';
    /** Resolve: give legacy user a custom free ID. */
    public const DECISION_ASSIGN_CUSTOM = 'assign_custom';
    /** Resolve: move contested ID to legacy; holder gets next free ID. */
    public const DECISION_RECLAIM = 'reclaim';
    /** Resolve (conflict_different): keep existing binding; names fixed in phase 2. */
    public const DECISION_KEEP_EXISTING = 'keep_existing';
    /** Resolve (conflict_different): switch binding to lastname ID if free. */
    public const DECISION_SWITCH_LASTNAME = 'switch_lastname';

    /**
     * True when lastname is exactly a 6-digit candidate ID (legacy pattern).
     */
    public static function is_legacy_lastname_id(string $lastname): bool {
        return candidate_manager::is_candidate_id_format(trim($lastname));
    }

    /**
     * Split full Chinese given-name field into 姓 (first char) + 名 (rest).
     * Compound surnames are not handled.
     *
     * @return array{lastname:string,firstname:string}|null
     */
    public static function split_fullname_simple(string $fullname): ?array {
        $name = trim(preg_replace('/\s+/u', '', $fullname) ?? '');
        if ($name === '' || \core_text::strlen($name) < 2) {
            return null;
        }
        return [
            'lastname' => \core_text::substr($name, 0, 1),
            'firstname' => \core_text::substr($name, 1),
        ];
    }

    /**
     * All active users whose lastname looks like a candidate ID, ordered ascending.
     *
     * @return \stdClass[] id, email, username, firstname, lastname, …
     */
    public static function find_legacy_users(): array {
        global $DB, $CFG;

        $users = $DB->get_records_sql(
            "SELECT id, email, username, firstname, lastname,
                    middlename, alternatename, firstnamephonetic, lastnamephonetic
               FROM {user}
              WHERE deleted = 0
                AND mnethostid = :mnet
                AND id > 1
                AND " . $DB->sql_length('lastname') . " = 6
                AND lastname >= :lo
                AND lastname <= :hi
           ORDER BY lastname ASC, id ASC",
            [
                'mnet' => $CFG->mnet_localhost_id,
                'lo' => '000000',
                'hi' => '999999',
            ]
        );

        $out = [];
        foreach ($users as $u) {
            if (self::is_legacy_lastname_id((string)$u->lastname)) {
                $out[] = $u;
            }
        }
        return $out;
    }

    /**
     * Classify one legacy user for phase 1 (bind ID only).
     *
     * @return array{
     *   status:string,
     *   userid:int,
     *   email:string,
     *   username:string,
     *   firstname:string,
     *   lastname:string,
     *   candidateid:string,
     *   existing_id:?string,
     *   holder_userid:?int,
     *   holder_email:?string,
     *   holder_fullname:?string
     * }
     */
    public static function classify_phase1(\stdClass $user): array {
        global $DB;

        $userid = (int)$user->id;
        $candidateid = trim((string)$user->lastname);
        $existing = candidate_manager::get_candidate_id($userid);

        $base = [
            'userid' => $userid,
            'email' => (string)$user->email,
            'username' => (string)$user->username,
            'firstname' => (string)$user->firstname,
            'lastname' => (string)$user->lastname,
            'candidateid' => $candidateid,
            'existing_id' => $existing,
            'bound_via' => null,
            'holder_userid' => null,
            'holder_email' => null,
            'holder_fullname' => null,
        ];

        if ($existing !== null && $existing === $candidateid) {
            $base['status'] = self::STATUS_ALREADY_BOUND;
            $base['bound_via'] = self::bound_via_for_user($userid, $existing);
            return $base;
        }

        if ($existing !== null && $existing !== $candidateid) {
            // Admin already accepted keeping the existing binding for this legacy row.
            if (self::is_mismatch_accepted($userid)) {
                $base['status'] = self::STATUS_ALREADY_BOUND;
                $base['bound_via'] = self::bound_via_for_user($userid, $existing);
                return $base;
            }
            $base['status'] = self::STATUS_CONFLICT_DIFFERENT;
            $base['bound_via'] = self::bound_via_for_user($userid, $existing);
            return $base;
        }

        $taken = $DB->get_record('local_tm_cert_candidates', ['candidateid' => $candidateid]);
        if ($taken && (int)$taken->userid !== $userid) {
            if (!candidate_manager::is_binding_reclaimable((int)$taken->userid)) {
                $holder = $DB->get_record('user', ['id' => (int)$taken->userid, 'deleted' => 0],
                    'id,email,firstname,lastname,middlename,alternatename,firstnamephonetic,lastnamephonetic');
                $base['status'] = self::STATUS_CONFLICT_TAKEN;
                $base['holder_userid'] = (int)$taken->userid;
                $base['holder_email'] = $holder ? (string)$holder->email : '';
                $base['holder_fullname'] = $holder ? fullname($holder) : '';
                return $base;
            }
        }

        // Conflict if another active user already uses this ID as username.
        $byuser = $DB->get_record_sql(
            "SELECT id, email, firstname, lastname, middlename, alternatename,
                    firstnamephonetic, lastnamephonetic
               FROM {user}
              WHERE deleted = 0
                AND id <> :uid
                AND username = :cid",
            ['uid' => $userid, 'cid' => $candidateid]
        );
        if ($byuser) {
            $base['status'] = self::STATUS_CONFLICT_TAKEN;
            $base['holder_userid'] = (int)$byuser->id;
            $base['holder_email'] = (string)$byuser->email;
            $base['holder_fullname'] = fullname($byuser);
            return $base;
        }

        $base['status'] = self::STATUS_READY;
        return $base;
    }

    /**
     * Scan all legacy users for phase 1.
     *
     * @return array{rows:array,counts:array<string,int>}
     */
    public static function scan_phase1(): array {
        $rows = [];
        $counts = [
            self::STATUS_READY => 0,
            self::STATUS_ALREADY_BOUND => 0,
            self::STATUS_CONFLICT_TAKEN => 0,
            self::STATUS_CONFLICT_DIFFERENT => 0,
        ];
        foreach (self::find_legacy_users() as $user) {
            $row = self::classify_phase1($user);
            $rows[] = $row;
            $status = $row['status'];
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
            $counts[$status]++;
        }
        return ['rows' => $rows, 'counts' => $counts];
    }

    /**
     * How get_candidate_id resolved for this user.
     *
     * @return string|null 'table'|'username'|null
     */
    public static function bound_via_for_user(int $userid, ?string $knownid = null): ?string {
        global $DB;

        $knownid = $knownid ?? candidate_manager::get_candidate_id($userid);
        if ($knownid === null) {
            return null;
        }
        $tableval = $DB->get_field('local_tm_cert_candidates', 'candidateid', ['userid' => $userid]);
        if ($tableval !== false && candidate_manager::is_candidate_id_format((string)$tableval)) {
            return 'table';
        }
        $username = $DB->get_field('user', 'username', ['id' => $userid, 'deleted' => 0]);
        if ($username !== false && (string)$username === $knownid) {
            return 'username';
        }
        return 'table';
    }

    /**
     * Counts grouped by candidate-ID year prefix (first 3 digits), then status.
     *
     * @param array $rows scan_phase1 rows
     * @return array<string,array{total:int,ready:int,already_bound:int,conflict_taken:int,conflict_different:int,bound_via_table:int,bound_via_username:int}>
     */
    public static function summarize_by_prefix(array $rows): array {
        $out = [];
        foreach ($rows as $row) {
            $cid = (string)($row['candidateid'] ?? '');
            $prefix = strlen($cid) >= 3 ? substr($cid, 0, 3) : '?';
            if (!isset($out[$prefix])) {
                $out[$prefix] = [
                    'total' => 0,
                    'ready' => 0,
                    'already_bound' => 0,
                    'conflict_taken' => 0,
                    'conflict_different' => 0,
                    'bound_via_table' => 0,
                    'bound_via_username' => 0,
                ];
            }
            $out[$prefix]['total']++;
            $status = (string)$row['status'];
            if ($status === self::STATUS_READY) {
                $out[$prefix]['ready']++;
            } else if ($status === self::STATUS_ALREADY_BOUND) {
                $out[$prefix]['already_bound']++;
                if (($row['bound_via'] ?? '') === 'username') {
                    $out[$prefix]['bound_via_username']++;
                } else {
                    $out[$prefix]['bound_via_table']++;
                }
            } else if ($status === self::STATUS_CONFLICT_TAKEN) {
                $out[$prefix]['conflict_taken']++;
            } else if ($status === self::STATUS_CONFLICT_DIFFERENT) {
                $out[$prefix]['conflict_different']++;
            }
        }
        ksort($out);
        return $out;
    }

    /**
     * Bind candidate IDs for STATUS_READY rows (and ensure table row for ALREADY_BOUND).
     * Does not change firstname / lastname / username.
     *
     * When $userids is non-null, only those user ids are considered (partial run).
     * Unselected READY rows stay pending; phase 2 stays locked until none remain.
     *
     * @param int[]|null $userids
     * @return array{bound:int,ensured:int,skipped:int,errors:array}
     */
    public static function run_phase1(?array $userids = null, int $actedby = 0): array {
        global $USER;

        $actedby = $actedby > 0 ? $actedby : (int)$USER->id;
        $filter = null;
        if ($userids !== null) {
            $filter = [];
            foreach ($userids as $id) {
                $id = (int)$id;
                if ($id > 1) {
                    $filter[$id] = true;
                }
            }
        }

        $bound = 0;
        $ensured = 0;
        $skipped = 0;
        $errors = [];

        $scan = self::scan_phase1();
        foreach ($scan['rows'] as $row) {
            $userid = (int)$row['userid'];
            if ($filter !== null && empty($filter[$userid])) {
                $skipped++;
                continue;
            }
            $cid = (string)$row['candidateid'];
            try {
                if ($row['status'] === self::STATUS_READY) {
                    candidate_manager::bind_candidate_id($userid, $cid, $actedby);
                    $bound++;
                } else if ($row['status'] === self::STATUS_ALREADY_BOUND && $filter === null) {
                    // Full run only: ensure plugin table row when ID only lived on username.
                    candidate_manager::bind_candidate_id($userid, $cid, $actedby);
                    $ensured++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'userid' => $userid,
                    'candidateid' => $cid,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'bound' => $bound,
            'ensured' => $ensured,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Phase 1 verification: no remaining READY rows among legacy lastnames.
     * Conflicts may remain (manual follow-up); they do not block phase 2 for bound users.
     *
     * @return array{
     *   ok:bool,
     *   ready:int,
     *   already_bound:int,
     *   conflicts:int,
     *   legacy_total:int,
     *   phase2_eligible:int
     * }
     */
    public static function verify_phase1(): array {
        $scan = self::scan_phase1();
        $ready = (int)($scan['counts'][self::STATUS_READY] ?? 0);
        $bound = (int)($scan['counts'][self::STATUS_ALREADY_BOUND] ?? 0);
        $conflicts = (int)($scan['counts'][self::STATUS_CONFLICT_TAKEN] ?? 0)
            + (int)($scan['counts'][self::STATUS_CONFLICT_DIFFERENT] ?? 0);

        $phase2 = 0;
        foreach ($scan['rows'] as $row) {
            if ($row['status'] === self::STATUS_ALREADY_BOUND) {
                $phase2++;
            }
        }

        return [
            'ok' => $ready === 0,
            'ready' => $ready,
            'already_bound' => $bound,
            'conflicts' => $conflicts,
            'legacy_total' => count($scan['rows']),
            'phase2_eligible' => $phase2,
        ];
    }

    /**
     * Userids whose lastname/ID mismatch was explicitly accepted (keep existing).
     *
     * @return int[]
     */
    public static function accepted_mismatch_userids(): array {
        $raw = (string)get_config('local_tm_certification', 'legacy_migrate_accepted_uids');
        if ($raw === '') {
            return [];
        }
        $ids = [];
        foreach (preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $id = (int)$part;
            if ($id > 1) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    public static function is_mismatch_accepted(int $userid): bool {
        return in_array($userid, self::accepted_mismatch_userids(), true);
    }

    public static function accept_mismatch(int $userid): void {
        $ids = self::accepted_mismatch_userids();
        if (!in_array($userid, $ids, true)) {
            $ids[] = $userid;
            set_config('legacy_migrate_accepted_uids', implode(',', $ids), 'local_tm_certification');
        }
    }

    /**
     * Apply an on-page conflict decision for one legacy user.
     * Does not change firstname/lastname/username (except reclaim may change holder's binding only).
     *
     * @throws \moodle_exception
     * @return array{decision:string,userid:int,candidateid:string,holder_userid:?int,holder_new_id:?string}
     */
    public static function resolve_conflict(int $userid, string $decision, string $customid = '', int $actedby = 0): array {
        global $DB, $USER;

        $actedby = $actedby > 0 ? $actedby : (int)$USER->id;
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!self::is_legacy_lastname_id((string)$user->lastname)) {
            throw new \moodle_exception('legacy_migrate_resolve_not_legacy', 'local_tm_certification');
        }

        $row = self::classify_phase1($user);
        $lastnameid = (string)$row['candidateid'];
        $decision = trim($decision);
        $customid = trim($customid);

        if ($decision === self::DECISION_KEEP_EXISTING) {
            if ($row['status'] !== self::STATUS_CONFLICT_DIFFERENT || empty($row['existing_id'])) {
                throw new \moodle_exception('legacy_migrate_resolve_invalid', 'local_tm_certification');
            }
            self::accept_mismatch($userid);
            return [
                'decision' => $decision,
                'userid' => $userid,
                'candidateid' => (string)$row['existing_id'],
                'holder_userid' => null,
                'holder_new_id' => null,
            ];
        }

        if ($decision === self::DECISION_SWITCH_LASTNAME) {
            if ($row['status'] !== self::STATUS_CONFLICT_DIFFERENT) {
                throw new \moodle_exception('legacy_migrate_resolve_invalid', 'local_tm_certification');
            }
            candidate_manager::change_candidate_id($userid, $lastnameid, $actedby);
            return [
                'decision' => $decision,
                'userid' => $userid,
                'candidateid' => $lastnameid,
                'holder_userid' => null,
                'holder_new_id' => null,
            ];
        }

        if ($decision === self::DECISION_ASSIGN_NEXT) {
            // Intentionally not offered in UI — admin must pick an explicit ID.
            throw new \moodle_exception('legacy_migrate_resolve_invalid', 'local_tm_certification');
        }

        if ($decision === self::DECISION_ASSIGN_CUSTOM) {
            if ($row['status'] !== self::STATUS_CONFLICT_TAKEN && $row['status'] !== self::STATUS_CONFLICT_DIFFERENT) {
                throw new \moodle_exception('legacy_migrate_resolve_invalid', 'local_tm_certification');
            }
            if (!candidate_manager::is_candidate_id_format($customid)) {
                throw new \moodle_exception('error_candidate_id_invalid', 'local_tm_certification');
            }
            if ($customid === $lastnameid && $row['status'] === self::STATUS_CONFLICT_TAKEN) {
                throw new \moodle_exception('legacy_migrate_resolve_custom_is_contested', 'local_tm_certification', '', $customid);
            }
            $err = candidate_manager::validate_candidate_id_change($userid, $customid);
            if ($err !== null) {
                throw new \moodle_exception('error_candidate_id_in_use', 'local_tm_certification', '', $customid);
            }
            if ($row['existing_id']) {
                candidate_manager::change_candidate_id($userid, $customid, $actedby);
            } else {
                candidate_manager::bind_candidate_id($userid, $customid, $actedby);
            }
            if ($customid !== $lastnameid) {
                self::accept_mismatch($userid);
            }
            return [
                'decision' => $decision,
                'userid' => $userid,
                'candidateid' => $customid,
                'holder_userid' => $row['holder_userid'],
                'holder_new_id' => null,
            ];
        }

        if ($decision === self::DECISION_RECLAIM) {
            if ($row['status'] !== self::STATUS_CONFLICT_TAKEN || empty($row['holder_userid'])) {
                throw new \moodle_exception('legacy_migrate_resolve_invalid', 'local_tm_certification');
            }
            if (!candidate_manager::is_candidate_id_format($customid)) {
                throw new \moodle_exception('legacy_migrate_resolve_holder_id_required', 'local_tm_certification');
            }
            if ($customid === $lastnameid) {
                throw new \moodle_exception('legacy_migrate_resolve_holder_id_same', 'local_tm_certification', '', $customid);
            }
            $holderid = (int)$row['holder_userid'];
            $err = candidate_manager::validate_candidate_id_change($holderid, $customid);
            if ($err !== null) {
                throw new \moodle_exception('error_candidate_id_in_use', 'local_tm_certification', '', $customid);
            }
            // Move holder to the admin-chosen ID first, freeing the contested ID.
            candidate_manager::change_candidate_id($holderid, $customid, $actedby);
            if ($row['existing_id']) {
                candidate_manager::change_candidate_id($userid, $lastnameid, $actedby);
            } else {
                candidate_manager::bind_candidate_id($userid, $lastnameid, $actedby);
            }
            return [
                'decision' => $decision,
                'userid' => $userid,
                'candidateid' => $lastnameid,
                'holder_userid' => $holderid,
                'holder_new_id' => $customid,
            ];
        }

        throw new \moodle_exception('legacy_migrate_resolve_invalid', 'local_tm_certification');
    }

    /**
     * Classify one user for phase 2 (name split).
     * Eligible when lastname is still legacy ID and the user already has any candidate binding.
     *
     * @return array{
     *   status:string,
     *   userid:int,
     *   email:string,
     *   username:string,
     *   firstname:string,
     *   lastname:string,
     *   candidateid:string,
     *   new_lastname:?string,
     *   new_firstname:?string
     * }
     */
    public static function classify_phase2(\stdClass $user): array {
        $userid = (int)$user->id;
        $lastnameid = trim((string)$user->lastname);
        $existing = candidate_manager::get_candidate_id($userid);

        $base = [
            'userid' => $userid,
            'email' => (string)$user->email,
            'username' => (string)$user->username,
            'firstname' => (string)$user->firstname,
            'lastname' => (string)$user->lastname,
            'candidateid' => $existing ?? $lastnameid,
            'new_lastname' => null,
            'new_firstname' => null,
        ];

        if ($existing === null) {
            // Still unresolved conflict / not bound — stay in phase 1.
            $base['status'] = self::STATUS_CONFLICT_DIFFERENT;
            return $base;
        }

        $split = self::split_fullname_simple((string)$user->firstname);
        if ($split === null) {
            $base['status'] = self::STATUS_NAME_SHORT;
            return $base;
        }

        $base['new_lastname'] = $split['lastname'];
        $base['new_firstname'] = $split['firstname'];
        $base['status'] = self::STATUS_PHASE2_READY;
        return $base;
    }

    /**
     * @return array{rows:array,counts:array<string,int>}
     */
    public static function scan_phase2(): array {
        $rows = [];
        $counts = [
            self::STATUS_PHASE2_READY => 0,
            self::STATUS_NAME_SHORT => 0,
            self::STATUS_CONFLICT_DIFFERENT => 0,
        ];
        foreach (self::find_legacy_users() as $user) {
            $row = self::classify_phase2($user);
            // Only surface rows that still have legacy lastname and are bound (or name-short).
            if ($row['status'] === self::STATUS_CONFLICT_DIFFERENT) {
                continue;
            }
            $rows[] = $row;
            $counts[$row['status']] = ($counts[$row['status']] ?? 0) + 1;
        }
        return ['rows' => $rows, 'counts' => $counts];
    }

    /**
     * Rewrite firstname/lastname for phase-2-ready users. Username unchanged.
     *
     * When $userids is non-null, only those user ids are updated (partial run).
     *
     * @param int[]|null $userids
     * @return array{updated:int,skipped:int,errors:array}
     */
    public static function run_phase2(?array $userids = null): array {
        global $DB;

        require_once($GLOBALS['CFG']->dirroot . '/user/lib.php');

        $filter = null;
        if ($userids !== null) {
            $filter = [];
            foreach ($userids as $id) {
                $id = (int)$id;
                if ($id > 1) {
                    $filter[$id] = true;
                }
            }
        }

        $updated = 0;
        $skipped = 0;
        $errors = [];

        $scan = self::scan_phase2();
        foreach ($scan['rows'] as $row) {
            if ($row['status'] !== self::STATUS_PHASE2_READY) {
                $skipped++;
                continue;
            }
            $userid = (int)$row['userid'];
            if ($filter !== null && empty($filter[$userid])) {
                $skipped++;
                continue;
            }
            try {
                $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
                if (!self::is_legacy_lastname_id((string)$user->lastname)) {
                    $skipped++;
                    continue;
                }
                if (candidate_manager::get_candidate_id($userid) === null) {
                    $skipped++;
                    continue;
                }
                $user->lastname = (string)$row['new_lastname'];
                $user->firstname = (string)$row['new_firstname'];
                \user_update_user($user, false, false);
                $updated++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'userid' => $userid,
                    'candidateid' => (string)$row['candidateid'],
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}