<?php
/**
 * Staff: session check-in QR display / print / regenerate.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/rollcall_manager.php');
require_once(__DIR__ . '/../classes/dashboard_helper.php');

use local_tm_certification\dashboard_helper;
use local_tm_certification\permissions_manager;
use local_tm_certification\rollcall_manager;
use local_tm_certification\session_manager;

require_login();

$sessionid = required_param('sessionid', PARAM_INT);
$session = session_manager::get_session($sessionid);

$canmanage = permissions_manager::user_can_manage() || permissions_manager::user_can_approve();
$caninstruct = permissions_manager::user_can_instruct()
    && ((int)$session->instructorid === (int)$USER->id || (int)$session->instructorid === 0);
if (!$canmanage && !$caninstruct) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$action = optional_param('action', '', PARAM_ALPHA);
$rosterurl = new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]);
$baseurl = new moodle_url('/local/tm_certification/admin/rollcall_qr.php', ['sessionid' => $sessionid]);

if ($action === 'regen' && confirm_sesskey()) {
    rollcall_manager::regenerate_checkin_token($sessionid);
    redirect($baseurl, get_string('rollcall_qr_regenerated', 'local_tm_certification'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

$token = rollcall_manager::ensure_checkin_token($sessionid);
$checkinurl = rollcall_manager::get_checkin_url($sessionid, $token);
$window = rollcall_manager::self_checkin_window($session);
$levels = session_manager::level_options();

$PAGE->set_context(context_system::instance());
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('rollcall_qr_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('rollcall_qr_title', 'local_tm_certification'));

$pngurl = new moodle_url('/local/tm_certification/admin/rollcall_qr_png.php', [
    'sessionid' => $sessionid,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash');

echo html_writer::start_div('tm-cert-head');
echo html_writer::start_div();
echo html_writer::tag('h2', get_string('rollcall_qr_title', 'local_tm_certification'));
echo html_writer::start_div('tm-cert-facts');
echo dashboard_helper::render_pill([
    'label' => format_string($session->name),
    'mod' => '',
]);
echo dashboard_helper::render_pill([
    'label' => $levels[$session->certlevel] ?? $session->certlevel,
    'mod' => '',
]);
echo dashboard_helper::render_pill([
    'label' => userdate((int)$session->starttime, get_string('strftimedatetimeshort', 'langconfig'))
        . ' – ' . userdate((int)$session->endtime, get_string('strftimetime', 'langconfig')),
    'mod' => '',
]);
if ($window === rollcall_manager::WINDOW_CLOSED) {
    echo dashboard_helper::render_pill([
        'label' => get_string('rollcall_window_closed', 'local_tm_certification'),
        'mod' => 'dim',
    ]);
} else {
    echo dashboard_helper::render_pill([
        'label' => get_string('rollcall_window_open', 'local_tm_certification'),
        'mod' => 'ok',
    ]);
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('tm-cert-head-actions');
echo html_writer::link($rosterurl, get_string('roster_back', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(get_string('rollcall_qr_rules_hint', 'local_tm_certification'), 'tm-cert-alert-slim');

echo html_writer::start_div('tm-cert-panel');
echo html_writer::start_div('tm-cert-ph');
echo html_writer::tag('h3', get_string('rollcall_qr_for_students', 'local_tm_certification'));
echo html_writer::start_div('tm-cert-ph-ops');
echo dashboard_helper::render_pill([
    'label' => get_string('rollcall_qr_present_until', 'local_tm_certification',
        userdate((int)$session->starttime, get_string('strftimetime', 'langconfig'))),
    'mod' => '',
]);
echo dashboard_helper::render_pill([
    'label' => get_string('rollcall_qr_closes_at', 'local_tm_certification',
        userdate((int)$session->endtime, get_string('strftimetime', 'langconfig'))),
    'mod' => '',
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('tm-cert-qr-layout');
echo html_writer::empty_tag('img', [
    'src' => $pngurl->out(false),
    'alt' => get_string('rollcall_qr_title', 'local_tm_certification'),
    'class' => 'tm-cert-qr-img',
    'width' => 220,
    'height' => 220,
]);
echo html_writer::start_div('tm-cert-qr-meta');
echo html_writer::tag('h3', get_string('rollcall_qr_scan_heading', 'local_tm_certification'));
echo html_writer::tag('p', get_string('rollcall_qr_scan_hint', 'local_tm_certification'));
echo html_writer::tag('code', $checkinurl->out(false), ['class' => 'tm-cert-qr-url']);
echo html_writer::start_div('tm-cert-ops');
echo html_writer::link($pngurl, get_string('rollcall_qr_download', 'local_tm_certification'), [
    'class' => 'btn btn-primary btn-sm',
    'download' => 'session' . $sessionid . '-checkin-qr.png',
    'target' => '_blank',
]);
echo html_writer::link($checkinurl, get_string('rollcall_qr_open_link', 'local_tm_certification'), [
    'class' => 'btn btn-secondary btn-sm',
    'target' => '_blank',
]);
echo html_writer::link(
    new moodle_url('/local/tm_certification/admin/rollcall_qr.php', [
        'sessionid' => $sessionid,
        'action' => 'regen',
        'sesskey' => sesskey(),
    ]),
    get_string('rollcall_qr_regen', 'local_tm_certification'),
    ['class' => 'btn btn-secondary btn-sm']
);
echo html_writer::end_div();
echo html_writer::tag('p', get_string('rollcall_qr_regen_hint', 'local_tm_certification'),
    ['class' => 'tm-cert-muted', 'style' => 'margin-top:14px;margin-bottom:0;font-size:0.82rem;']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
