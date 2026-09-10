<?php
/**
 * Backward-compatible redirect to the shared template download.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');

redirect(new moodle_url('/local/tm_certification/batch_template.php'));
