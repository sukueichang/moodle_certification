<?php
/**
 * Link to existing mod_attendance activities (never create new activities).
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/catalog_manager.php');
require_once(__DIR__ . '/session_manager.php');

class attendance_helper {

    /**
     * Resolve attendance instance from a course-module id.
     *
     * @return array{cmid:int, attendanceid:int, courseid:int}|null
     */
    public static function resolve_from_cmid(int $cmid): ?array {
        global $DB;

        if ($cmid <= 0) {
            return null;
        }
        $moduleid = (int)$DB->get_field('modules', 'id', ['name' => 'attendance']);
        if ($moduleid <= 0) {
            return null;
        }
        $cm = $DB->get_record('course_modules', [
            'id' => $cmid,
            'module' => $moduleid,
            'deletioninprogress' => 0,
        ]);
        if (!$cm || empty($cm->instance)) {
            return null;
        }
        if (!$DB->record_exists('attendance', ['id' => (int)$cm->instance])) {
            return null;
        }
        return [
            'cmid' => (int)$cm->id,
            'attendanceid' => (int)$cm->instance,
            'courseid' => (int)$cm->course,
        ];
    }

    /**
     * List existing attendance activities in a course for admin dropdowns.
     *
     * @return array<int,string> cmid => label
     */
    public static function attendance_options_for_course(int $courseid): array {
        global $DB;

        if ($courseid <= 0) {
            return [];
        }
        $moduleid = (int)$DB->get_field('modules', 'id', ['name' => 'attendance']);
        if ($moduleid <= 0) {
            return [];
        }

        $sql = 'SELECT cm.id, a.name
                  FROM {course_modules} cm
                  JOIN {attendance} a ON a.id = cm.instance
                 WHERE cm.course = :courseid
                   AND cm.module = :moduleid
                   AND cm.deletioninprogress = 0
              ORDER BY a.name ASC';
        $rows = $DB->get_records_sql($sql, ['courseid' => $courseid, 'moduleid' => $moduleid]);
        $out = [];
        foreach ($rows as $row) {
            $out[(int)$row->id] = format_string($row->name) . ' (cmid ' . (int)$row->id . ')';
        }
        return $out;
    }

    /**
     * After session create/approve: add a slot inside the mapped existing attendance activity.
     * Does NOT create a new mod_attendance activity.
     *
     * @return array{cmid:int|null, attendancesessid:int|null, warning:?string}
     */
    public static function ensure_attendance_slot_for_session(int $sessionid): array {
        global $DB;

        $session = session_manager::get_session($sessionid);
        $result = ['cmid' => null, 'attendancesessid' => null, 'warning' => null];

        $cmid = !empty($session->attendancecmid)
            ? (int)$session->attendancecmid
            : catalog_manager::get_attendance_cmid_for_level((string)$session->certlevel);

        if ($cmid <= 0) {
            $result['warning'] = 'no_attendance_mapping';
            return $result;
        }

        $resolved = self::resolve_from_cmid($cmid);
        if (!$resolved) {
            $result['warning'] = 'attendance_cm_invalid';
            return $result;
        }

        // Prefer mapped course match; if mismatch, still allow but warn.
        $mappedcourse = catalog_manager::get_moodle_courseid_for_level((string)$session->certlevel);
        if ($mappedcourse > 0 && $resolved['courseid'] !== $mappedcourse) {
            $result['warning'] = 'attendance_course_mismatch';
        }

        $attendanceid = $resolved['attendanceid'];
        $slotdate = (int)$session->starttime;
        $duration = max(0, (int)$session->endtime - $slotdate);
        $desc = (string)$session->name;

        $existing = null;
        if (!empty($session->attendancesessid)) {
            $existing = $DB->get_record('attendance_sessions', [
                'id' => (int)$session->attendancesessid,
                'attendanceid' => $attendanceid,
            ]);
        }
        if (!$existing) {
            $existing = $DB->get_record_select(
                'attendance_sessions',
                'attendanceid = :aid AND sessdate = :sd AND description = :desc',
                ['aid' => $attendanceid, 'sd' => $slotdate, 'desc' => $desc]
            );
        }

        if ($existing) {
            $attsessid = (int)$existing->id;
        } else {
            $slot = new \stdClass();
            $slot->attendanceid = $attendanceid;
            $slot->groupid = 0;
            $slot->sessdate = $slotdate;
            $slot->duration = $duration;
            $slot->lasttaken = 0;
            $slot->lasttakenby = 0;
            $slot->timemodified = time();
            $slot->description = $desc;
            $slot->descriptionformat = FORMAT_HTML;
            $slot->studentscanmark = 0;
            $slot->autoassignstatus = 0;
            $slot->subnet = '';
            $slot->studentpassword = '';
            $slot->includeqrcode = 0;
            $attsessid = (int)$DB->insert_record('attendance_sessions', $slot);
        }

        $upd = new \stdClass();
        $upd->id = $sessionid;
        $upd->attendancecmid = $resolved['cmid'];
        $upd->attendancesessid = $attsessid;
        $upd->timemodified = time();
        $DB->update_record('local_tm_cert_sessions', $upd);

        $result['cmid'] = $resolved['cmid'];
        $result['attendancesessid'] = $attsessid;
        return $result;
    }

    /**
     * Write/update attendance_log for one student on the session's attendance slot.
     * Does not create activities. Skips quietly if unmapped.
     *
     * @param bool $present true = 〔參〕/Present, false = 〔缺〕/Absent
     * @return array{ok:bool,warning:?string}
     */
    public static function upsert_practical_mark(int $sessionid, int $userid, bool $present, string $remarks): array {
        global $DB, $USER;

        $out = ['ok' => false, 'warning' => null];
        $slot = self::ensure_attendance_slot_for_session($sessionid);
        if (empty($slot['attendancesessid']) || empty($slot['cmid'])) {
            $out['warning'] = $slot['warning'] ?? 'no_attendance_mapping';
            return $out;
        }

        $resolved = self::resolve_from_cmid((int)$slot['cmid']);
        if (!$resolved) {
            $out['warning'] = 'attendance_cm_invalid';
            return $out;
        }

        $attendanceid = (int)$resolved['attendanceid'];
        $attsessid = (int)$slot['attendancesessid'];
        $statusid = self::resolve_status_id($attendanceid, $present);
        if ($statusid <= 0) {
            $out['warning'] = 'attendance_status_missing';
            return $out;
        }

        $statusset = self::statusset_csv($attendanceid);
        $now = time();
        $takenby = (int)($USER->id ?? 0);
        $existing = $DB->get_record('attendance_log', [
            'sessionid' => $attsessid,
            'studentid' => $userid,
        ]);

        if ($existing) {
            $existing->statusid = $statusid;
            $existing->statusset = $statusset;
            $existing->remarks = \core_text::substr($remarks, 0, 255);
            $existing->timetaken = $now;
            $existing->takenby = $takenby;
            $existing->timemodified = $now;
            $DB->update_record('attendance_log', $existing);
        } else {
            $log = new \stdClass();
            $log->sessionid = $attsessid;
            $log->studentid = $userid;
            $log->statusid = $statusid;
            $log->statusset = $statusset;
            $log->timetaken = $now;
            $log->takenby = $takenby;
            $log->remarks = \core_text::substr($remarks, 0, 255);
            $log->ipaddress = '';
            $log->timemodified = $now;
            $DB->insert_record('attendance_log', $log);
        }

        $DB->set_field('attendance_sessions', 'lasttaken', $now, ['id' => $attsessid]);
        $DB->set_field('attendance_sessions', 'lasttakenby', $takenby, ['id' => $attsessid]);
        $DB->set_field('attendance_sessions', 'timemodified', $now, ['id' => $attsessid]);

        $out['ok'] = true;
        return $out;
    }

    /**
     * Resolve Present/Absent status id (supports 〔參〕／〔缺〕 and EN acronyms).
     */
    public static function resolve_status_id(int $attendanceid, bool $present): int {
        global $DB;

        $cfgkey = $present ? 'attendance_statusid_present' : 'attendance_statusid_absent';
        $override = (int)get_config('local_tm_certification', $cfgkey);
        if ($override > 0 && $DB->record_exists('attendance_statuses', [
            'id' => $override,
            'attendanceid' => $attendanceid,
            'deleted' => 0,
        ])) {
            return $override;
        }

        $rows = $DB->get_records('attendance_statuses', [
            'attendanceid' => $attendanceid,
            'deleted' => 0,
        ]);
        if (empty($rows)) {
            return 0;
        }

        $acronyms = $present
            ? ['參', 'PR', 'Pr', 'P', 'Present', '出席']
            : ['缺', 'Ab', 'Absent', 'A', '缺席'];
        foreach ($acronyms as $acronym) {
            foreach ($rows as $row) {
                if (strcasecmp(trim((string)($row->acronym ?? '')), $acronym) === 0) {
                    return (int)$row->id;
                }
            }
        }

        foreach ($rows as $row) {
            $desc = trim((string)($row->description ?? ''));
            $descl = \core_text::strtolower($desc);
            if ($present) {
                if ($desc === '出席' || $desc === '參' || $descl === 'present' || strpos($desc, '〔參〕') !== false) {
                    return (int)$row->id;
                }
            } else if ($desc === '缺席' || $desc === '缺' || $descl === 'absent' || strpos($desc, '〔缺〕') !== false) {
                return (int)$row->id;
            }
        }

        // Fallback: present = highest grade; absent = unmarked or lowest grade.
        $best = null;
        foreach ($rows as $row) {
            if ($present) {
                if ($best === null || (float)$row->grade > (float)$best->grade) {
                    $best = $row;
                }
            } else {
                if (!empty($row->setunmarked)) {
                    return (int)$row->id;
                }
                if ($best === null || (float)$row->grade < (float)$best->grade) {
                    $best = $row;
                }
            }
        }
        return $best ? (int)$best->id : 0;
    }

    private static function statusset_csv(int $attendanceid): string {
        global $DB;
        $rows = $DB->get_records('attendance_statuses', [
            'attendanceid' => $attendanceid,
            'deleted' => 0,
        ], 'id ASC', 'id');
        return implode(',', array_keys($rows));
    }
}
