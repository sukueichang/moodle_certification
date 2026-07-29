<?php
/**
 * Privacy subsystem — null provider until personal data tables land.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
