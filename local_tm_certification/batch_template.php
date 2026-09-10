<?php
/**
 * Download public CSV template for examinee batch upload.
 * Available to managers, approvers, and instructors.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/permissions_manager.php');
require_once(__DIR__ . '/classes/batch_helper.php');

use local_tm_certification\batch_helper;
use local_tm_certification\permissions_manager;

require_login();
if (!permissions_manager::user_can_manage()
    && !permissions_manager::user_can_approve()
    && !permissions_manager::user_can_instruct()) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$filename = 'tm_certification_examinees.csv';
$content = batch_helper::template_csv();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-cache, must-revalidate');
echo $content;
exit;
