<?php
/**
 * Download session examinee login sheet CSV for instructors (class-day handout).
 * Columns: username, full name, default password (Tmai+candidate ID), institution.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/enrolment_manager.php');
require_once(__DIR__ . '/../classes/batch_helper.php');

use local_tm_certification\batch_helper;
use local_tm_certification\enrolment_manager;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();

// Must set context before any fullname()/format_string() to avoid $PAGE debugging HTML in CSV.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/roster_export.php'));
$PAGE->set_pagelayout('popup');

$sessionid = required_param('sessionid', PARAM_INT);
$session = session_manager::get_session($sessionid);

$canmanage = permissions_manager::user_can_manage() || permissions_manager::user_can_approve();
$caninstruct = permissions_manager::user_can_instruct()
    && ((int)$session->instructorid === (int)$USER->id || (int)$session->instructorid === 0);
if (!$canmanage && !$caninstruct) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$roster = enrolment_manager::get_session_roster($sessionid);
$content = batch_helper::roster_login_csv($roster);

$safename = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', (string)$session->name);
$filename = 'roster_' . $safename . '_' . $sessionid . '.csv';

// Drop any accidental buffered debug HTML before sending the file.
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
echo $content;
die;
