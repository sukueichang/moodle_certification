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
     * @return array<int,array<string,bool>>
     */
    public static function instructor_level_matrix(): array {
        global $DB;
        $levels = array_keys(session_manager::level_options());
        $rows = $DB->get_records_select('local_tm_cert_instlvl', 'active = 1', null, '', 'userid,certlevel');
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
}
