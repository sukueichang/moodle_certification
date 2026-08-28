<?php
/**
 * Privacy provider (data lives in local_tm_certification tables).
 *
 * @package    profilefield_tmcandidate
 */

namespace profilefield_tmcandidate\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
