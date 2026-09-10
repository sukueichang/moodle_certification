<?php
/**
 * Event callbacks for local_tm_certification.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * After admin saves user/editadvanced.php, apply injected candidate ID if posted.
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event): void {
        require_once(__DIR__ . '/user_edit_candidate_field.php');
        user_edit_candidate_field::save_from_request((int)$event->objectid);
    }
}
