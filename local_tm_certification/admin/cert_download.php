<?php
/**
 * Proxy-download one learner exam certificate PDF (staff).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/enrolment_manager.php');
require_once(__DIR__ . '/../classes/assessment_manager.php');
require_once(__DIR__ . '/../classes/certificate_helper.php');
require_once(__DIR__ . '/../classes/candidate_manager.php');

use local_tm_certification\assessment_manager;
use local_tm_certification\candidate_manager;
use local_tm_certification\certificate_helper;
use local_tm_certification\enrolment_manager;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
require_sesskey();

global $DB, $USER, $PAGE, $OUTPUT;

$context = context_system::instance();
$PAGE->set_context($context);

$sessionid = required_param('sessionid', PARAM_INT);
$enrolid = required_param('enrolid', PARAM_INT);
$session = session_manager::get_session($sessionid);

$canmanage = permissions_manager::user_can_manage() || permissions_manager::user_can_approve();
$caninstruct = permissions_manager::user_can_instruct()
    && ((int)$session->instructorid === (int)$USER->id || (int)$session->instructorid === 0);
if (!$canmanage && !$caninstruct) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$enrol = $DB->get_record('local_tm_cert_enrols', ['id' => $enrolid], '*', MUST_EXIST);
if ((int)$enrol->sessionid !== $sessionid
        || (int)$enrol->status === enrolment_manager::ENROL_CANCELLED) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$overall = assessment_manager::evaluate_overall($enrolid);
if ($overall['overall'] !== true) {
    redirect(
        new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]),
        get_string('cert_download_not_passed', 'local_tm_certification'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$level = (string)$session->certlevel;
$userid = (int)$enrol->userid;
$info = certificate_helper::get_level_certificate_availability($userid, $level);
if (empty($info['available'])) {
    $msgkey = 'cert_download_unavailable';
    if (($info['reason'] ?? '') === 'customcert_missing') {
        $msgkey = 'cert_download_customcert_missing';
    } else if (($info['reason'] ?? '') === 'course_unmapped') {
        $msgkey = 'cert_download_course_unmapped';
    }
    redirect(
        new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]),
        get_string($msgkey, 'local_tm_certification'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

try {
    $pdf = certificate_helper::generate_pdf_for_user($userid, $level);
} catch (Throwable $e) {
    debugging('cert_download: ' . $e->getMessage(), DEBUG_DEVELOPER);
    $pdf = null;
}

if ($pdf === null || $pdf === '') {
    redirect(
        new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]),
        get_string('cert_download_unavailable', 'local_tm_certification'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$user = core_user::get_user($userid, '*', MUST_EXIST);
$cid = candidate_manager::get_candidate_id($userid) ?? (string)$user->username;
$safename = preg_replace('/[^0-9A-Za-z_\-]+/', '_', $cid . '_' . fullname($user));
$filename = $safename . '.pdf';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
echo $pdf;
die;
