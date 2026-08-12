<?php
/**
 * Daily summary of instructor certificate expiry (per-level months from admin settings).
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification\task;

defined('MOODLE_INTERNAL') || die();

class refresh_instructor_expiry extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_refresh_instructor_expiry', 'local_tm_certification');
    }

    public function execute(): void {
        require_once(__DIR__ . '/../catalog_manager.php');
        require_once(__DIR__ . '/../session_manager.php');

        // Auto-end open sessions past endtime.
        $open = \local_tm_certification\session_manager::get_sessions([
            'status' => \local_tm_certification\session_manager::STATUS_OPEN,
        ]);
        // get_sessions already sync_auto_ends; count remaining open for log.
        $stillopen = 0;
        foreach ($open as $s) {
            if (!\local_tm_certification\session_manager::is_ended($s)) {
                $stillopen++;
            }
        }
        mtrace('local_tm_certification sessions: open(after auto-end)=' . $stillopen);

        $matrix = \local_tm_certification\catalog_manager::instructor_level_matrix();
        $valid = 0;
        $expired = 0;
        $pending = 0;
        foreach (array_keys($matrix) as $userid) {
            foreach (\local_tm_certification\catalog_manager::get_instructor_levels_status((int)$userid) as $st) {
                if ($st['status'] === \local_tm_certification\catalog_manager::INSTR_STATUS_VALID) {
                    $valid++;
                } else if ($st['status'] === \local_tm_certification\catalog_manager::INSTR_STATUS_EXPIRED) {
                    $expired++;
                } else {
                    $pending++;
                }
            }
        }
        mtrace('local_tm_certification instructor level expiry: valid=' . $valid
            . ' expired=' . $expired . ' pending=' . $pending);
    }
}
