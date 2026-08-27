<?php
/**
 * Session roster (list + on-site rollcall + cert actions).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/enrolment_manager.php');
require_once(__DIR__ . '/../classes/dashboard_helper.php');
require_once(__DIR__ . '/../classes/assessment_manager.php');
require_once(__DIR__ . '/../classes/rollcall_manager.php');

use local_tm_certification\assessment_manager;
use local_tm_certification\dashboard_helper;
use local_tm_certification\enrolment_manager;
use local_tm_certification\permissions_manager;
use local_tm_certification\rollcall_manager;
use local_tm_certification\session_manager;

require_login();
$sessionid = required_param('sessionid', PARAM_INT);
$session = session_manager::get_session($sessionid);
$canaddenrol = session_manager::can_add_examinees($session);

$canmanage = permissions_manager::user_can_manage() || permissions_manager::user_can_approve();
$caninstruct = permissions_manager::user_can_instruct()
    && ((int)$session->instructorid === (int)$USER->id || (int)$session->instructorid === 0);
if (!$canmanage && !$caninstruct) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('session_roster', 'local_tm_certification'));
$PAGE->set_heading(format_string($session->name));

$cancelid = optional_param('cancelid', 0, PARAM_INT);
$confirmremove = optional_param('confirmremove', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$canremove = $canmanage || $caninstruct;
$rosterurl = new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]);
$enrolurl = new moodle_url('/local/tm_certification/admin/enrol.php', ['sessionid' => $sessionid]);
$dashurl = new moodle_url('/local/tm_certification/index.php');
$qrurl = new moodle_url('/local/tm_certification/admin/rollcall_qr.php', ['sessionid' => $sessionid]);
$certbatchurl = new moodle_url('/local/tm_certification/admin/cert_batch.php', ['sessionid' => $sessionid]);

// Staff rollcall mark / mark-all.
if ($action !== '' && confirm_sesskey()) {
    if ($action === 'rollcall') {
        $enrolid = required_param('enrolid', PARAM_INT);
        $status = required_param('status', PARAM_ALPHANUMEXT);
        rollcall_manager::mark($sessionid, $enrolid, $status, (int)$USER->id, rollcall_manager::SOURCE_STAFF);
        redirect($rosterurl);
    }
    if ($action === 'rollcall_all_present') {
        rollcall_manager::mark_all_present($sessionid, (int)$USER->id);
        redirect($rosterurl, get_string('rollcall_all_present_done', 'local_tm_certification'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Step 2: confirmed remove.
if ($cancelid && $confirmremove && confirm_sesskey() && $canremove) {
    $assessment = enrolment_manager::assess_roster_removal($cancelid);
    if ((int)$assessment['sessionid'] !== $sessionid) {
        throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
    }
    $result = enrolment_manager::remove_from_roster($cancelid, !empty($assessment['will_delete_user']));
    if (!empty($result['deleted_user'])) {
        $msg = get_string('enrol_removed_deleted_user', 'local_tm_certification', (object)[
            'name' => $assessment['fullname'],
            'username' => $assessment['username'],
        ]);
    } else {
        $msg = get_string('enrol_removed_keep_user', 'local_tm_certification', (object)[
            'name' => $assessment['fullname'],
            'username' => $assessment['username'],
        ]);
        if (!empty($result['released_candidate_id'])) {
            $msg .= ' ' . get_string('enrol_removed_released_id', 'local_tm_certification');
        }
        if (!empty($result['restored_password'])) {
            $msg .= ' ' . get_string('enrol_removed_restored_password', 'local_tm_certification');
        }
    }
    redirect($rosterurl, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
}

// Step 1: confirm page before removing.
if ($cancelid && !$confirmremove && $canremove) {
    $assessment = enrolment_manager::assess_roster_removal($cancelid);
    if ((int)$assessment['sessionid'] !== $sessionid) {
        throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
    }
    echo $OUTPUT->header();
    echo html_writer::start_div('tm-cert-dash');
    echo html_writer::tag('h3', get_string('enrol_remove_confirm_title', 'local_tm_certification'));
    echo html_writer::tag('p', get_string('enrol_remove_user_line', 'local_tm_certification', (object)[
        'name' => $assessment['fullname'],
        'username' => $assessment['username'],
        'email' => $assessment['email'],
    ]));
    if (!empty($assessment['will_delete_user'])) {
        echo $OUTPUT->notification(get_string('enrol_remove_will_delete', 'local_tm_certification'), 'warning');
    } else {
        echo $OUTPUT->notification(get_string('enrol_remove_keep_account', 'local_tm_certification'), 'info');
        if (!empty($assessment['will_release_candidate_id'])) {
            echo $OUTPUT->notification(get_string('enrol_remove_will_release_id', 'local_tm_certification'), 'info');
        }
        if (!empty($assessment['will_restore_password'])) {
            echo $OUTPUT->notification(get_string('enrol_remove_will_restore_password', 'local_tm_certification'), 'info');
        }
    }
    $yesurl = new moodle_url('/local/tm_certification/admin/roster.php', [
        'sessionid' => $sessionid,
        'cancelid' => $cancelid,
        'confirmremove' => 1,
        'sesskey' => sesskey(),
    ]);
    echo html_writer::div(
        html_writer::link($yesurl, get_string('enrol_remove_continue', 'local_tm_certification'), ['class' => 'btn btn-danger'])
        . ' '
        . html_writer::link($rosterurl, get_string('cancel'), ['class' => 'btn btn-secondary']),
        'tm-cert-head-actions mt-3'
    );
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

$roster = enrolment_manager::get_session_roster($sessionid);
$rollmap = rollcall_manager::get_map_for_session($sessionid);
$rollcounts = rollcall_manager::count_for_roster($sessionid, $roster);
$levels = session_manager::level_options();
$statuslabels = rollcall_manager::status_options();
$ended = session_manager::is_ended($session);
$count = count($roster);

$venue = '';
if (!empty($session->venuename) || !empty($session->venuecode)) {
    $venue = trim((string)($session->venuecode ?? '') . ' ' . (string)($session->venuename ?? ''));
} else if (!empty($session->venue)) {
    $venue = (string)$session->venue;
}

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash');

echo html_writer::start_div('tm-cert-head');
echo html_writer::start_div();
echo html_writer::tag('h2', format_string($session->name));
echo html_writer::start_div('tm-cert-facts');
echo dashboard_helper::render_pill([
    'label' => $levels[$session->certlevel] ?? $session->certlevel,
    'mod' => '',
]);
echo dashboard_helper::render_pill([
    'label' => userdate((int)$session->starttime, get_string('strftimedatetimeshort', 'langconfig')),
    'mod' => '',
]);
if ($venue !== '') {
    echo dashboard_helper::render_pill(['label' => $venue, 'mod' => '']);
}
echo dashboard_helper::render_pill([
    'label' => get_string('dash_session_people', 'local_tm_certification', $count),
    'mod' => '',
]);
echo dashboard_helper::session_status_pill($session);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('tm-cert-head-actions');
if ($canaddenrol) {
    echo html_writer::link($enrolurl, get_string('dash_action_temp_enrol', 'local_tm_certification'),
        ['class' => 'btn btn-primary']);
}
echo html_writer::link(
    new moodle_url('/local/tm_certification/admin/practical_scores.php', ['sessionid' => $sessionid]),
    get_string('dash_action_practical', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::link($qrurl, get_string('rollcall_qr_title', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']);
echo html_writer::link($certbatchurl, get_string('cert_batch_session_title', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']);
echo html_writer::link($dashurl, get_string('roster_back', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('tm-cert-panel');
echo html_writer::start_div('tm-cert-ph');
echo html_writer::tag('h3', get_string('session_roster', 'local_tm_certification'));
echo html_writer::start_div('tm-cert-ph-ops');
if (!empty($roster)) {
    echo dashboard_helper::render_pill([
        'label' => get_string('rollcall_count_present', 'local_tm_certification', $rollcounts['present']),
        'mod' => 'ok',
    ]);
    echo dashboard_helper::render_pill([
        'label' => get_string('rollcall_count_late', 'local_tm_certification', $rollcounts['late']),
        'mod' => 'late',
    ]);
    echo dashboard_helper::render_pill([
        'label' => get_string('rollcall_count_absent', 'local_tm_certification', $rollcounts['absent']),
        'mod' => 'bad',
    ]);
    echo dashboard_helper::render_pill([
        'label' => get_string('rollcall_count_unknown', 'local_tm_certification', $rollcounts['unknown']),
        'mod' => 'dim',
    ]);
    echo html_writer::link($qrurl, get_string('rollcall_qr_title', 'local_tm_certification'),
        ['class' => 'btn btn-sm btn-secondary']);
    echo html_writer::link(
        new moodle_url('/local/tm_certification/admin/roster.php', [
            'sessionid' => $sessionid,
            'action' => 'rollcall_all_present',
            'sesskey' => sesskey(),
        ]),
        get_string('rollcall_all_present', 'local_tm_certification'),
        ['class' => 'btn btn-sm btn-secondary']
    );
    echo html_writer::link(
        new moodle_url('/local/tm_certification/admin/roster_export.php', ['sessionid' => $sessionid]),
        get_string('roster_export_login_csv', 'local_tm_certification'),
        ['class' => 'btn btn-sm btn-secondary']
    );
    echo html_writer::link($certbatchurl, get_string('cert_batch_session_title', 'local_tm_certification'),
        ['class' => 'btn btn-sm btn-primary']);
}
echo html_writer::end_div();
echo html_writer::end_div();

if (empty($roster)) {
    echo html_writer::start_div('tm-cert-empty');
    echo html_writer::tag('strong', get_string('enrol_empty_title', 'local_tm_certification'));
    echo html_writer::div(get_string('enrol_empty', 'local_tm_certification'));
    if ($canaddenrol) {
        echo html_writer::div(
            html_writer::link($enrolurl, get_string('dash_action_temp_enrol', 'local_tm_certification'),
                ['class' => 'btn btn-primary']),
            'mt-3'
        );
    }
    echo html_writer::end_div();
} else {
    echo html_writer::start_tag('table', ['class' => 'tm-cert-data']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('fullname'))
        . html_writer::tag('th', get_string('candidate_id', 'local_tm_certification'))
        . html_writer::tag('th', get_string('email'))
        . html_writer::tag('th', get_string('rollcall_col', 'local_tm_certification'))
        . html_writer::tag('th', get_string('cert_col_pass', 'local_tm_certification'))
        . html_writer::tag('th', get_string('cert_batch_col_action', 'local_tm_certification'))
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($roster as $row) {
        $ev = assessment_manager::evaluate_overall((int)$row->id);
        if ($ev['overall'] === true) {
            $passpill = dashboard_helper::render_pill([
                'label' => get_string('cert_pass_ok', 'local_tm_certification'),
                'mod' => 'ok',
            ]);
        } else if ($ev['overall'] === false) {
            $passpill = dashboard_helper::render_pill([
                'label' => get_string('cert_pass_fail', 'local_tm_certification'),
                'mod' => 'bad',
            ]);
        } else {
            $passpill = dashboard_helper::render_pill([
                'label' => get_string('cert_pass_pending', 'local_tm_certification'),
                'mod' => 'dim',
            ]);
        }

        $eid = (int)$row->id;
        $rc = $rollmap[$eid] ?? null;
        $cur = $rc ? (string)$rc->status : '';
        $toggle = html_writer::start_div('tm-cert-rc-status');
        $toggle .= html_writer::start_div('tm-cert-rc-toggle', [
            'role' => 'group',
            'aria-label' => get_string('rollcall_col', 'local_tm_certification'),
        ]);
        foreach ([
            rollcall_manager::STATUS_PRESENT => 'on-present',
            rollcall_manager::STATUS_LATE => 'on-late',
            rollcall_manager::STATUS_ABSENT => 'on-absent',
        ] as $st => $onclass) {
            $markurl = new moodle_url('/local/tm_certification/admin/roster.php', [
                'sessionid' => $sessionid,
                'action' => 'rollcall',
                'enrolid' => $eid,
                'status' => $st,
                'sesskey' => sesskey(),
            ]);
            $cls = 'tm-cert-rc-btn' . ($cur === $st ? ' ' . $onclass : '');
            $toggle .= html_writer::link($markurl, $statuslabels[$st], ['class' => $cls]);
        }
        $toggle .= html_writer::end_div();
        $toggle .= html_writer::span(rollcall_manager::format_meta_line($rc), 'tm-cert-rc-meta');
        $toggle .= html_writer::end_div();

        $ops = [];
        if ($ev['overall'] === true) {
            $ops[] = html_writer::link(
                new moodle_url('/local/tm_certification/admin/cert_download.php', [
                    'sessionid' => $sessionid,
                    'enrolid' => $eid,
                    'sesskey' => sesskey(),
                ]),
                get_string('cert_download_one', 'local_tm_certification'),
                ['class' => 'btn btn-sm btn-primary']
            );
        }
        if ($canremove && !$ended) {
            $ops[] = html_writer::link(
                new moodle_url('/local/tm_certification/admin/roster.php', [
                    'sessionid' => $sessionid,
                    'cancelid' => $row->id,
                ]),
                get_string('enrol_cancel', 'local_tm_certification'),
                ['class' => 'btn btn-sm tm-cert-btn-danger']
            );
        }

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', fullname($row));
        echo html_writer::tag('td', s((string)($row->candidateid ?? '')), ['class' => 'tm-cert-id']);
        echo html_writer::tag('td', s($row->email), ['class' => 'tm-cert-muted']);
        echo html_writer::tag('td', $toggle, ['class' => 'tm-cert-rc-cell']);
        echo html_writer::tag('td', $passpill);
        echo html_writer::tag('td', html_writer::div($ops ? implode(' ', $ops) : '—', 'tm-cert-ops'));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
