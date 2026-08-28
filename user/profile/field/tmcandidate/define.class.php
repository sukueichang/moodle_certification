<?php
/**
 * Definition UI for the TM candidate ID profile field type (no extra settings).
 *
 * @package    profilefield_tmcandidate
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/profile/definelib.php');

class profile_define_tmcandidate extends profile_define_base {
    // Intentionally empty — datatype has no type-specific definition options.
}
