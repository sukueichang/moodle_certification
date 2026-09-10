<?php
/**
 * Post-install for profilefield_tmcandidate — register the user-edit field instance.
 *
 * @package    profilefield_tmcandidate
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_profilefield_tmcandidate_install() {
    $helper = $GLOBALS['CFG']->dirroot . '/local/tm_certification/classes/profile_candidate_field.php';
    if (file_exists($helper)) {
        require_once($helper);
        \local_tm_certification\profile_candidate_field::ensure_installed();
    }
}
