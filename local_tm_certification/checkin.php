<?php
/**
 * Student session QR self check-in (requires Moodle login).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/session_manager.php');
require_once(__DIR__ . '/classes/rollcall_manager.php');
require_once(__DIR__ . '/classes/candidate_manager.php');

use local_tm_certification\candidate_manager;
use local_tm_certification\rollcall_manager;
use local_tm_certification\session_manager;

$sessionid = required_param('sessionid', PARAM_INT);
$token = required_param('t', PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/tm_certification/checkin.php', [
    'sessionid' => $sessionid,
    't' => $token,
]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('rollcall_checkin_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('rollcall_checkin_title', 'local_tm_certification'));

require_login();

$session = session_manager::get_session($sessionid);
$levels = session_manager::level_options();
$levellabel = $levels[$session->certlevel] ?? $session->certlevel;
$window = rollcall_manager::self_checkin_window($session);
$result = null;

if ($confirm && confirm_sesskey()) {
    $result = rollcall_manager::self_checkin($sessionid, (int)$USER->id, $token);
} else {
    // Preview state without writing.
    if (!rollcall_manager::validate_checkin_token($sessionid, $token)) {
        $result = ['ok' => false, 'code' => 'bad_token', 'status' => null, 'row' => null, 'window' => $window];
    } else {
        $enrol = rollcall_manager::get_active_enrol($sessionid, (int)$USER->id);
        if (!$enrol) {
            $result = ['ok' => false, 'code' => 'not_roster', 'status' => null, 'row' => null, 'window' => $window];
        } else {
            global $DB;
            $existing = $DB->get_record('local_tm_cert_rollcall', [
                'sessionid' => $sessionid,
                'enrolid' => (int)$enrol->id,
            ]);
            if ($existing) {
                $result = [
                    'ok' => true,
                    'code' => 'already',
                    'status' => (string)$existing->status,
                    'row' => $existing,
                    'window' => $window,
                ];
            } else if ($window === rollcall_manager::WINDOW_CLOSED) {
                $result = ['ok' => false, 'code' => 'closed', 'status' => null, 'row' => null, 'window' => $window];
            } else {
                $result = [
                    'ok' => true,
                    'code' => 'ready',
                    'status' => ($window === rollcall_manager::WINDOW_LATE)
                        ? rollcall_manager::STATUS_LATE
                        : rollcall_manager::STATUS_PRESENT,
                    'row' => null,
                    'window' => $window,
                ];
            }
        }
    }
}

$cid = candidate_manager::get_candidate_id((int)$USER->id)
    ?? (candidate_manager::is_candidate_id_format((string)$USER->username) ? (string)$USER->username : '');

$statuslabels = rollcall_manager::status_options();
$willlabel = '';
if (!empty($result['status']) && isset($statuslabels[$result['status']])) {
    $willlabel = $statuslabels[$result['status']];
}

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash tm-cert-checkin');

echo html_writer::start_div('tm-cert-checkin-card');
echo html_writer::tag('h2', get_string('rollcall_checkin_title', 'local_tm_certification'));
echo html_writer::div(
    format_string($session->name) . ' · ' . $levellabel . ' · '
    . userdate((int)$session->starttime, get_string('strftimedatetimeshort', 'langconfig'))
    . ' – '
    . userdate((int)$session->endtime, get_string('strftimetime', 'langconfig')),
    'tm-cert-checkin-lead'
);

$code = (string)($result['code'] ?? '');

if ($code === 'bad_token') {
    echo html_writer::div(get_string('rollcall_checkin_bad_token', 'local_tm_certification'), 'tm-cert-checkin-pill dim');
    echo html_writer::div(get_string('rollcall_checkin_bad_token_hint', 'local_tm_certification'), 'tm-cert-checkin-lead');
} else if ($code === 'not_roster') {
    echo html_writer::div(get_string('rollcall_checkin_not_roster', 'local_tm_certification'), 'tm-cert-checkin-pill bad');
    echo html_writer::div(fullname($USER), 'tm-cert-checkin-who');
    echo html_writer::div(get_string('rollcall_checkin_not_roster_hint', 'local_tm_certification'), 'tm-cert-checkin-lead');
} else if ($code === 'closed') {
    echo html_writer::div(get_string('rollcall_checkin_closed', 'local_tm_certification'), 'tm-cert-checkin-pill dim');
    echo html_writer::div(get_string('rollcall_checkin_closed_hint', 'local_tm_certification'), 'tm-cert-checkin-lead');
} else if ($code === 'already' || $code === 'marked') {
    $pillmod = 'ok';
    if ((string)$result['status'] === rollcall_manager::STATUS_LATE) {
        $pillmod = 'late';
    } else if ((string)$result['status'] === rollcall_manager::STATUS_ABSENT) {
        $pillmod = 'bad';
    }
    $title = ($code === 'marked')
        ? get_string('rollcall_checkin_done', 'local_tm_certification')
        : get_string('rollcall_checkin_already', 'local_tm_certification');
    echo html_writer::div($title, 'tm-cert-checkin-pill ' . $pillmod);
    echo html_writer::div(fullname($USER), 'tm-cert-checkin-who');
    $meta = $willlabel;
    if (!empty($result['row'])) {
        $meta .= ' · ' . rollcall_manager::format_meta_line($result['row']);
    }
    echo html_writer::div($meta, 'tm-cert-checkin-when');
    echo html_writer::div(get_string('rollcall_checkin_done_hint', 'local_tm_certification'), 'tm-cert-checkin-lead');
} else if ($code === 'ready') {
    $pillmod = ((string)$result['status'] === rollcall_manager::STATUS_LATE) ? 'late' : 'ok';
    echo html_writer::div(
        get_string('rollcall_checkin_will_be', 'local_tm_certification', $willlabel),
        'tm-cert-checkin-pill ' . $pillmod
    );
    echo html_writer::div(
        get_string('rollcall_checkin_now', 'local_tm_certification',
            userdate(time(), get_string('strftimedatetimeshort', 'langconfig'))),
        'tm-cert-checkin-lead'
    );
    echo html_writer::div(fullname($USER), 'tm-cert-checkin-who');
    $sub = get_string('rollcall_checkin_logged_in', 'local_tm_certification');
    if ($cid !== '') {
        $sub = get_string('candidate_id', 'local_tm_certification') . ' ' . $cid . ' · ' . $sub;
    }
    echo html_writer::div($sub, 'tm-cert-checkin-when');

    $confirmurl = new moodle_url('/local/tm_certification/checkin.php', [
        'sessionid' => $sessionid,
        't' => $token,
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]);
    echo html_writer::link($confirmurl, get_string('rollcall_checkin_confirm', 'local_tm_certification'),
        ['class' => 'btn btn-primary']);
}

echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
