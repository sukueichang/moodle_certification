<?php
/**
 * Event observers for local_tm_certification.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_updated',
        'callback' => '\local_tm_certification\observer::user_updated',
    ],
];
