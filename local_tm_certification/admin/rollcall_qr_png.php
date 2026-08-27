<?php
/**
 * Serve session check-in QR as PNG (staff only).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/rollcall_manager.php');

use local_tm_certification\permissions_manager;
use local_tm_certification\rollcall_manager;
use local_tm_certification\session_manager;

require_login();
require_sesskey();

$sessionid = required_param('sessionid', PARAM_INT);
$session = session_manager::get_session($sessionid);

$canmanage = permissions_manager::user_can_manage() || permissions_manager::user_can_approve();
$caninstruct = permissions_manager::user_can_instruct()
    && ((int)$session->instructorid === (int)$USER->id || (int)$session->instructorid === 0);
if (!$canmanage && !$caninstruct) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$token = rollcall_manager::ensure_checkin_token($sessionid);
$checkinurl = rollcall_manager::get_checkin_url($sessionid, $token)->out(false);
$png = rollcall_manager::qr_png_data($checkinurl, 8);

while (ob_get_level() > 0) {
    ob_end_clean();
}

if ($png !== null) {
    header('Content-Type: image/png');
    header('Content-Disposition: inline; filename="session' . $sessionid . '-checkin-qr.png"');
    header('Cache-Control: private, max-age=60');
    echo $png;
    die;
}

// Fallback: redirect to public QR image service.
redirect(rollcall_manager::qr_fallback_img_url($checkinurl, 240));
