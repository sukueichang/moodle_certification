<?php
/**
 * Version metadata for profilefield_tmcandidate.
 *
 * Deploy to: {moodle}/user/profile/field/tmcandidate/
 *
 * @package    profilefield_tmcandidate
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026082800;
$plugin->requires  = 2020061500; // Moodle 3.9+.
$plugin->component = 'profilefield_tmcandidate';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
$plugin->dependencies = [
    'local_tm_certification' => ANY_VERSION,
];
