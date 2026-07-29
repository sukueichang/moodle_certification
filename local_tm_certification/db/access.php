<?php
/**
 * Capability definitions for local_tm_certification.
 *
 * Archetypes are intentionally minimal — Site Admin assigns roles via plugin settings
 * and/or Moodle role definitions. Do not hard-code custom role shortnames in logic.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'local/tm_certification:manage' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    'local/tm_certification:approve' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    'local/tm_certification:instruct' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],

    'local/tm_certification:examinee' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],

    'local/tm_certification:viewdashboard' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
