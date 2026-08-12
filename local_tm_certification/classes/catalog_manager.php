<?php
/**
 * Venue / course mapping / instructor-level catalog utilities.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class catalog_manager {

    /**
     * @return \stdClass[]
     */
    public static function get_venues(?string $level = null, bool $onlyactive = true): array {
        global $DB;
        $params = [];
        $where = ['1=1'];
        if ($onlyactive) {
            $where[] = 'active = 1';
        }
        if ($level !== null && $level !== '') {
            $where[] = 'certlevel = :certlevel';
            $params['certlevel'] = $level;
        }
        $sql = 'SELECT * FROM {local_tm_cert_venues} WHERE ' . implode(' AND ', $where)
            . ' ORDER BY certlevel ASC, name ASC';
        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * @return array<int,string>
     */
    public static function venue_options(string $level): array {
        $out = [];
        foreach (self::get_venues($level, true) as $v) {
            $label = trim((string)($v->venuecode ?? '')) !== ''
                ? (string)$v->venuecode . ' - ' . (string)$v->name
                : (string)$v->name;
            $out[(int)$v->id] = $label;
        }
        return $out;
    }

    /**
     * All active venues (any level) — for Moodle select option whitelist when JS filters by level.
     *
     * @return array<int,string>
     */
    public static function venue_options_all_active(): array {
        $out = [];
        foreach (self::get_venues(null, true) as $v) {
            $level = (string)$v->certlevel;
            $levelopts = session_manager::level_options();
            $lvlabel = $levelopts[$level] ?? $level;
            $base = trim((string)($v->venuecode ?? '')) !== ''
                ? (string)$v->venuecode . ' - ' . (string)$v->name
                : (string)$v->name;
            $out[(int)$v->id] = $base . ' [' . $lvlabel . ']';
        }
        return $out;
    }

    /**
     * True when venue exists, is active, and certlevel matches (trimmed).
     */
    public static function venue_matches_level(int $venueid, string $level): bool {
        $venue = self::get_venue($venueid);
        if (!$venue || (int)$venue->active !== 1) {
            return false;
        }
        return trim((string)$venue->certlevel) === trim($level);
    }

    /**
     * @return array<string,array<int,string>>
     */
    public static function venue_options_by_level(): array {
        $map = [];
        foreach (session_manager::level_options() as $level => $label) {
            $map[$level] = self::venue_options($level);
        }
        return $map;
    }

    public static function get_venue(int $venueid): ?\stdClass {
        global $DB;
        return $DB->get_record('local_tm_cert_venues', ['id' => $venueid]);
    }

    public static function save_venue(array $data, int $id = 0): int {
        global $DB, $USER;
        $name = trim((string)($data['name'] ?? ''));
        $venuecode = strtoupper(trim((string)($data['venuecode'] ?? '')));
        $level = (string)($data['certlevel'] ?? '');
        $passedat = !empty($data['passedat']) ? (int)$data['passedat'] : null;
        $active = !empty($data['active']) ? 1 : 0;
        if ($venuecode === '') {
            throw new \moodle_exception('error_venue_code_required', 'local_tm_certification');
        }
        if ($name === '') {
            throw new \moodle_exception('error_venue_name_required', 'local_tm_certification');
        }
        if (!array_key_exists($level, session_manager::level_options())) {
            throw new \moodle_exception('error_session_level_invalid', 'local_tm_certification');
        }
        $duplicate = $DB->get_record('local_tm_cert_venues', [
            'venuecode' => $venuecode,
            'certlevel' => $level,
        ], 'id');
        if ($duplicate && (int)$duplicate->id !== $id) {
            throw new \moodle_exception('error_venue_code_duplicated', 'local_tm_certification');
        }

        $record = new \stdClass();
        $record->venuecode = $venuecode;
        $record->name = $name;
        $record->certlevel = $level;
        $record->passedat = $passedat;
        $record->active = $active;
        $record->timemodified = time();
        if ($id > 0) {
            $record->id = $id;
            $DB->update_record('local_tm_cert_venues', $record);
            return $id;
        }
        $record->timecreated = time();
        $record->createdby = (int)$USER->id;
        return (int)$DB->insert_record('local_tm_cert_venues', $record);
    }

    public static function delete_venue(int $id): void {
        global $DB;
        $DB->delete_records('local_tm_cert_venues', ['id' => $id]);
    }

    public static function get_moodle_courseid_for_level(string $level): int {
        return (int)get_config('local_tm_certification', 'mapping_course_' . $level);
    }

    /**
     * Mapped existing mod_attendance course-module id for a level (0 = not set).
     */
    public static function get_attendance_cmid_for_level(string $level): int {
        return (int)get_config('local_tm_certification', 'mapping_attendance_cmid_' . $level);
    }

    /**
     * @return array<string,int>
     */
    public static function get_course_mapping_all_levels(): array {
        $out = [];
        foreach (array_keys(session_manager::level_options()) as $level) {
            $out[$level] = self::get_moodle_courseid_for_level($level);
        }
        return $out;
    }

    /**
     * @return \stdClass[]
     */
    public static function get_qualified_instructors(string $level): array {
        global $DB;
        $sql = "SELECT q.*, u.id AS userid, u.email, u.firstname, u.lastname,
                       u.middlename, u.alternatename, u.firstnamephonetic, u.lastnamephonetic
                  FROM {local_tm_cert_instlvl} q
                  JOIN {user} u ON u.id = q.userid
                 WHERE q.certlevel = :certlevel
                   AND q.active = 1
                   AND u.deleted = 0
                   AND u.suspended = 0
              ORDER BY u.lastname ASC, u.firstname ASC";
        return array_values($DB->get_records_sql($sql, ['certlevel' => $level]));
    }

    /**
     * @return array<int,string>
     */
    public static function instructor_options(string $level): array {
        $out = [];
        foreach (self::get_qualified_instructors($level) as $row) {
            $out[(int)$row->userid] = fullname($row) . ' (' . $row->email . ')';
        }
        return $out;
    }

    /**
     * Union of instructors qualified for any level (Moodle select whitelist).
     *
     * @return array<int,string>
     */
    public static function instructor_options_all(): array {
        $out = [];
        foreach (session_manager::level_options() as $level => $unused) {
            foreach (self::instructor_options($level) as $userid => $label) {
                $out[(int)$userid] = $label;
            }
        }
        return $out;
    }

    /**
     * @return array<string,array<int,string>>
     */
    public static function instructor_options_by_level(): array {
        $map = [];
        foreach (session_manager::level_options() as $level => $label) {
            $map[$level] = self::instructor_options($level);
        }
        return $map;
    }

    public static function instructor_qualified_for_level(int $userid, string $level): bool {
        global $DB;
        return $DB->record_exists('local_tm_cert_instlvl', [
            'userid' => $userid,
            'certlevel' => $level,
            'active' => 1,
        ]);
    }

    public static function set_instructor_qualification(int $userid, string $level, bool $active): void {
        global $DB, $USER;
        if (!array_key_exists($level, session_manager::level_options())) {
            throw new \moodle_exception('error_session_level_invalid', 'local_tm_certification');
        }
        $exists = $DB->get_record('local_tm_cert_instlvl', ['userid' => $userid, 'certlevel' => $level]);
        $now = time();
        if ($exists) {
            $exists->active = $active ? 1 : 0;
            $exists->timemodified = $now;
            $DB->update_record('local_tm_cert_instlvl', $exists);
            return;
        }
        $record = new \stdClass();
        $record->userid = $userid;
        $record->certlevel = $level;
        $record->active = $active ? 1 : 0;
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->createdby = (int)$USER->id;
        $DB->insert_record('local_tm_cert_instlvl', $record);
    }

    /**
     * Active instructor level authorisations keyed by userid.
     * A user may hold multiple levels at once (basic / intermediate / advanced).
     *
     * @return array<int,array<string,bool>>
     */
    public static function instructor_level_matrix(): array {
        global $DB;
        $levels = array_keys(session_manager::level_options());
        // First column must be unique (id): one userid can have several active level rows.
        $rows = $DB->get_records_select(
            'local_tm_cert_instlvl',
            'active = 1',
            null,
            'userid ASC, certlevel ASC',
            'id, userid, certlevel'
        );
        $matrix = [];
        foreach ($rows as $r) {
            $uid = (int)$r->userid;
            if (!isset($matrix[$uid])) {
                $matrix[$uid] = [];
                foreach ($levels as $lv) {
                    $matrix[$uid][$lv] = false;
                }
            }
            $matrix[$uid][(string)$r->certlevel] = true;
        }
        return $matrix;
    }

    /**
     * Active authorised levels for one instructor (may be multiple).
     *
     * @return string[] level codes
     */
    public static function get_user_instructor_levels(int $userid): array {
        global $DB;
        if ($userid <= 0) {
            return [];
        }
        $rows = $DB->get_records_select(
            'local_tm_cert_instlvl',
            'userid = :userid AND active = 1',
            ['userid' => $userid],
            'certlevel ASC',
            'id, certlevel'
        );
        $out = [];
        $allowed = session_manager::level_options();
        foreach ($rows as $r) {
            $lv = (string)$r->certlevel;
            if (array_key_exists($lv, $allowed)) {
                $out[] = $lv;
            }
        }
        return $out;
    }

    /**
     * List users from configured instructor role mapping (system-context roles only).
     *
     * @return \stdClass[]
     */
    public static function get_instructor_users_from_mapping(int $limit = 500): array {
        global $DB;
        $roleids = permissions_manager::get_configured_roleids(permissions_manager::CFG_ROLES_INSTRUCT);
        if (empty($roleids)) {
            return [];
        }
        [$insql, $inparams] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roleid');
        $params = array_merge([
            'contextlevel' => CONTEXT_SYSTEM,
        ], $inparams);
        $sql = "SELECT DISTINCT u.id, u.email, u.firstname, u.lastname,
                       u.middlename, u.alternatename, u.firstnamephonetic, u.lastnamephonetic
                  FROM {role_assignments} ra
                  JOIN {context} c ON c.id = ra.contextid
                  JOIN {user} u ON u.id = ra.userid
                 WHERE c.contextlevel = :contextlevel
                   AND ra.roleid {$insql}
                   AND u.deleted = 0
                   AND u.suspended = 0
              ORDER BY u.lastname ASC, u.firstname ASC";
        return array_values($DB->get_records_sql($sql, $params, 0, $limit));
    }

    /**
     * @param int[] $userids
     * @return array<int,\stdClass>
     */
    public static function get_instructor_cert_profiles(array $userids): array {
        global $DB;
        $userids = array_values(array_unique(array_map('intval', $userids)));
        $userids = array_values(array_filter($userids, static function(int $id): bool {
            return $id > 0;
        }));
        if (empty($userids)) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $rows = $DB->get_records_select('local_tm_cert_instrcert', "userid {$insql}", $params);
        $out = [];
        foreach ($rows as $row) {
            $out[(int)$row->userid] = $row;
        }
        return $out;
    }

    public static function save_instructor_cert_profile(int $userid, ?int $certifiedat, ?int $renewedat): void {
        global $DB, $USER;
        $userid = (int)$userid;
        if ($userid <= 0) {
            return;
        }
        $existing = $DB->get_record('local_tm_cert_instrcert', ['userid' => $userid]);
        $now = time();
        if ($existing) {
            $existing->certifiedat = $certifiedat;
            $existing->renewedat = $renewedat;
            $existing->timemodified = $now;
            $existing->updatedby = (int)$USER->id;
            $DB->update_record('local_tm_cert_instrcert', $existing);
            return;
        }
        $record = new \stdClass();
        $record->userid = $userid;
        $record->certifiedat = $certifiedat;
        $record->renewedat = $renewedat;
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->updatedby = (int)$USER->id;
        $DB->insert_record('local_tm_cert_instrcert', $record);
    }

    public const INSTR_STATUS_VALID = 'valid';
    public const INSTR_STATUS_EXPIRED = 'expired';
    public const INSTR_STATUS_PENDING = 'pending';

    /**
     * Configured validity length in months for an instructor level (default 12).
     * Admin setting applies immediately to all instructors.
     */
    public static function instructor_validity_months(string $level): int {
        $v = get_config('local_tm_certification', 'instructor_validity_months_' . $level);
        if ($v === false || $v === '' || $v === null) {
            return 12;
        }
        $months = (int)$v;
        return $months > 0 ? $months : 12;
    }

    /**
     * Whether teaching a session auto-extends the validity window (admin setting).
     * When enabled, last taught session date is included in the anchor (retrospective, live).
     */
    public static function instructor_extend_by_teaching(): bool {
        return (int)get_config('local_tm_certification', 'instructor_extend_by_teaching') === 1;
    }

    /**
     * Latest session starttime where user is the assigned instructor (0 if none).
     */
    public static function get_instructor_last_taught_at(int $userid): int {
        global $DB;
        if ($userid <= 0) {
            return 0;
        }
        $ts = (int)$DB->get_field_sql(
            "SELECT MAX(starttime)
               FROM {local_tm_cert_sessions}
              WHERE instructorid = :uid AND starttime > 0",
            ['uid' => $userid]
        );
        return $ts > 0 ? $ts : 0;
    }

    /**
     * Anchor timestamp for instructor profile (renewedat / certifiedat; optionally last taught).
     */
    public static function get_instructor_cert_anchor(int $userid): int {
        $profiles = self::get_instructor_cert_profiles([$userid]);
        $profile = $profiles[$userid] ?? null;
        $certified = ($profile && !empty($profile->certifiedat)) ? (int)$profile->certifiedat : 0;
        $renewed = ($profile && !empty($profile->renewedat)) ? (int)$profile->renewedat : 0;
        $anchor = max($certified, $renewed);
        if (self::instructor_extend_by_teaching()) {
            $anchor = max($anchor, self::get_instructor_last_taught_at($userid));
        }
        return $anchor;
    }

    /**
     * Validity for one authorised level (date + per-level months; optional teaching extend).
     *
     * @return array{status:string,anchor:?int,expiresat:?int,months:int,level:string,lasttaught:?int,extendbyteaching:bool}
     */
    public static function get_instructor_level_status(int $userid, string $level, ?int $now = null): array {
        $now = $now ?? time();
        $months = self::instructor_validity_months($level);
        $lasttaught = self::get_instructor_last_taught_at($userid);
        $extend = self::instructor_extend_by_teaching();
        $out = [
            'status' => self::INSTR_STATUS_PENDING,
            'anchor' => null,
            'expiresat' => null,
            'months' => $months,
            'level' => $level,
            'lasttaught' => $lasttaught > 0 ? $lasttaught : null,
            'extendbyteaching' => $extend,
        ];

        if (!self::instructor_qualified_for_level($userid, $level)) {
            return $out;
        }

        $anchor = self::get_instructor_cert_anchor($userid);
        if ($anchor <= 0) {
            return $out;
        }

        $expiresat = (int)strtotime('+' . $months . ' months', $anchor);
        $out['anchor'] = $anchor;
        $out['expiresat'] = $expiresat;
        $out['status'] = ($now < $expiresat) ? self::INSTR_STATUS_VALID : self::INSTR_STATUS_EXPIRED;
        return $out;
    }

    /**
     * Human-readable「更新效期期限」line for a level status row.
     */
    public static function format_renewal_deadline_label(array $levelstatus): string {
        $status = (string)($levelstatus['status'] ?? self::INSTR_STATUS_PENDING);
        if ($status === self::INSTR_STATUS_EXPIRED) {
            return get_string('cert_renewal_deadline_urgent', 'local_tm_certification');
        }
        if ($status === self::INSTR_STATUS_VALID && !empty($levelstatus['expiresat'])) {
            return get_string('cert_renewal_deadline_until', 'local_tm_certification',
                userdate((int)$levelstatus['expiresat'], get_string('strftimedate', 'langconfig')));
        }
        return get_string('cert_renewal_deadline_pending', 'local_tm_certification');
    }

    /**
     * Status for every active instructor level of a user.
     *
     * @return array<string,array{status:string,anchor:?int,expiresat:?int,months:int,level:string}>
     */
    public static function get_instructor_levels_status(int $userid, ?int $now = null): array {
        $out = [];
        foreach (self::get_user_instructor_levels($userid) as $level) {
            $out[$level] = self::get_instructor_level_status($userid, $level, $now);
        }
        return $out;
    }

    /**
     * Aggregate status for display (any valid → valid; else any pending → pending; else expired).
     *
     * @return array{status:string,anchor:?int,expiresat:?int,levels:array}
     */
    public static function get_instructor_certificate_status(int $userid, ?int $now = null): array {
        $levels = self::get_instructor_levels_status($userid, $now);
        if (empty($levels)) {
            return ['status' => self::INSTR_STATUS_PENDING, 'anchor' => null, 'expiresat' => null, 'levels' => []];
        }

        $hasvalid = false;
        $haspending = false;
        $hasexpired = false;
        $anchor = null;
        $expiresat = null;
        foreach ($levels as $st) {
            if ($st['status'] === self::INSTR_STATUS_VALID) {
                $hasvalid = true;
            } else if ($st['status'] === self::INSTR_STATUS_PENDING) {
                $haspending = true;
            } else if ($st['status'] === self::INSTR_STATUS_EXPIRED) {
                $hasexpired = true;
            }
            if (!empty($st['anchor'])) {
                $anchor = (int)$st['anchor'];
            }
            if (!empty($st['expiresat']) && ($expiresat === null || (int)$st['expiresat'] < $expiresat)) {
                $expiresat = (int)$st['expiresat'];
            }
        }

        if ($hasvalid) {
            $status = self::INSTR_STATUS_VALID;
        } else if ($haspending && !$hasexpired) {
            $status = self::INSTR_STATUS_PENDING;
        } else if ($hasexpired && !$haspending) {
            $status = self::INSTR_STATUS_EXPIRED;
        } else if ($hasexpired) {
            $status = self::INSTR_STATUS_EXPIRED;
        } else {
            $status = self::INSTR_STATUS_PENDING;
        }

        return [
            'status' => $status,
            'anchor' => $anchor,
            'expiresat' => $expiresat,
            'levels' => $levels,
        ];
    }
}
