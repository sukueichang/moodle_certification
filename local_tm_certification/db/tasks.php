<?php
/**
 * Scheduled tasks for local_tm_certification.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_tm_certification\\task\\refresh_instructor_expiry',
        'blocking' => 0,
        'minute' => '20',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
