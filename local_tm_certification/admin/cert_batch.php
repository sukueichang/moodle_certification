<?php
/**
 * Session batch certificate download (passed examinees → summary → zip).
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
require_once(__DIR__ . '/../classes/dashboard_helper.php');

use local_tm_certification\assessment_manager;
use local_tm_certification\candidate_manager;
use local_tm_certification\certificate_helper;
use local_tm_certification\dashboard_helper;
use local_tm_certification\enrolment_manager;
use local_tm_certification\permissions_manager;
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

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/cert_batch.php', ['sessionid' => $sessionid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cert_batch_session_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('cert_batch_session_title', 'local_tm_certification'));

$action = optional_param('action', '', PARAM_ALPHA);
$showsummary = optional_param('summary', 0, PARAM_INT);
$level = (string)$session->certlevel;
$levels = session_manager::level_options();
$levelname = $levels[$level] ?? $level;
$rosterurl = new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]);
$baseurl = new moodle_url('/local/tm_certification/admin/cert_batch.php', ['sessionid' => $sessionid]);

if ($action === 'download' && confirm_sesskey()) {
    $userids = optional_param_array('userids', [], PARAM_INT);
    $userids = array_values(array_unique(array_map('intval', $userids)));
    if (empty($userids)) {
        foreach (enrolment_manager::get_session_roster($sessionid) as $row) {
            $ev = assessment_manager::evaluate_overall((int)$row->id);
            if ($ev['overall'] === true) {
                $userids[] = (int)$row->userid;
            }
        }
    }
    $zipname = 'cert_session' . $sessionid . '_' . $level;
    $result = certificate_helper::build_zip_for_users($userids, $level, $zipname);
    if (empty($result['ok']) || empty($result['path']) || !is_readable($result['path'])) {
        redirect(
            new moodle_url($baseurl, ['summary' => 1]),
            get_string('cert_batch_zip_empty', 'local_tm_certification'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $bytes = file_get_contents($result['path']);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $bytes;
    @unlink($result['path']);
    die;
}

$load_users = static function(array $userids): array {
    global $DB;
    $userids = array_values(array_filter(array_map('intval', $userids)));
    if (empty($userids)) {
        return [];
    }
    [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
    $rows = $DB->get_records_select('user', "id $insql AND deleted = 0", $params,
        'lastname ASC, firstname ASC',
        'id,email,username,firstname,lastname,middlename,alternatename,firstnamephonetic,lastnamephonetic,institution');
    $cids = candidate_manager::get_candidate_ids_for_users($userids);
    foreach ($rows as $row) {
        $row->candidateid = $cids[(int)$row->id]
            ?? (candidate_manager::is_candidate_id_format((string)$row->username) ? (string)$row->username : '');
    }
    return $rows;
};

$passedrows = [];
foreach (enrolment_manager::get_session_roster($sessionid) as $row) {
    $ev = assessment_manager::evaluate_overall((int)$row->id);
    if ($ev['overall'] === true) {
        $passedrows[] = $row;
    }
}

$selectedsession = optional_param_array('userids', [], PARAM_INT);
if (empty($selectedsession)) {
    foreach ($passedrows as $row) {
        $selectedsession[] = (int)$row->userid;
    }
}
$selectedsession = array_values(array_unique(array_map('intval', $selectedsession)));

$summary = null;
if ($showsummary && !empty($selectedsession)) {
    $users = $load_users($selectedsession);
    $rows = [];
    $downloadable = 0;
    $missing = 0;
    foreach ($selectedsession as $userid) {
        if (!isset($users[$userid])) {
            continue;
        }
        $u = $users[$userid];
        $info = certificate_helper::get_level_certificate_availability($userid, $level);
        $avail = !empty($info['available']);
        if ($avail) {
            $downloadable++;
            $statuslabel = ((string)$info['status'] === certificate_helper::STATUS_ISSUED)
                ? get_string('cert_status_issued', 'local_tm_certification')
                : get_string('cert_status_receivable', 'local_tm_certification');
            $statusmod = 'ok';
            $result = get_string('cert_batch_result_include', 'local_tm_certification');
        } else {
            $missing++;
            $statuslabel = get_string('cert_status_none', 'local_tm_certification');
            $statusmod = 'bad';
            $result = get_string('cert_batch_result_skip', 'local_tm_certification');
        }
        $rows[] = (object)[
            'user' => $u,
            'available' => $avail,
            'statuslabel' => $statuslabel,
            'statusmod' => $statusmod,
            'result' => $result,
        ];
    }
    $summary = ['rows' => $rows, 'downloadable' => $downloadable, 'missing' => $missing];
}

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash');

echo html_writer::start_div('tm-cert-head');
echo html_writer::start_div();
echo html_writer::tag('h2', get_string('cert_batch_session_title', 'local_tm_certification'));
echo html_writer::start_div('tm-cert-facts');
echo dashboard_helper::render_pill([
    'label' => get_string('cert_batch_mode_session', 'local_tm_certification'),
    'mod' => '',
]);
echo dashboard_helper::render_pill(['label' => format_string($session->name), 'mod' => '']);
echo dashboard_helper::render_pill([
    'label' => get_string('cert_batch_level', 'local_tm_certification', $levelname),
    'mod' => '',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('tm-cert-head-actions');
echo html_writer::link($rosterurl, get_string('roster_back_list', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();

if (!certificate_helper::is_customcert_installed()) {
    echo $OUTPUT->notification(get_string('cert_download_customcert_missing', 'local_tm_certification'), 'error');
}

echo html_writer::start_div('tm-cert-panel');
echo html_writer::start_div('tm-cert-ph');
echo html_writer::tag('h3', get_string('cert_batch_session_passed', 'local_tm_certification'));
echo dashboard_helper::render_pill([
    'label' => get_string('cert_batch_count', 'local_tm_certification', count($passedrows)),
    'mod' => '',
]);
echo html_writer::end_div();

if (empty($passedrows)) {
    echo html_writer::div(get_string('cert_batch_no_passed', 'local_tm_certification'), 'tm-cert-empty');
} else {
    echo html_writer::start_tag('form', [
        'method' => 'get',
        'action' => (new moodle_url('/local/tm_certification/admin/cert_batch.php'))->out(false),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sessionid', 'value' => $sessionid]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'summary', 'value' => '1']);

    echo html_writer::start_tag('table', ['class' => 'tm-cert-data']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr',
        html_writer::tag('th', '')
        . html_writer::tag('th', get_string('fullname'))
        . html_writer::tag('th', get_string('candidate_id', 'local_tm_certification'))
        . html_writer::tag('th', get_string('email'))
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    $selectedmap = array_flip($selectedsession);
    foreach ($passedrows as $row) {
        $uid = (int)$row->userid;
        $checked = isset($selectedmap[$uid]);
        $cbattrs = [
            'type' => 'checkbox',
            'name' => 'userids[]',
            'value' => $uid,
        ];
        if ($checked) {
            $cbattrs['checked'] = 'checked';
        }
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', html_writer::empty_tag('input', $cbattrs));
        echo html_writer::tag('td', fullname($row));
        echo html_writer::tag('td', s((string)($row->candidateid ?? '')), ['class' => 'tm-cert-id']);
        echo html_writer::tag('td', s($row->email), ['class' => 'tm-cert-muted']);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::start_div('tm-cert-ph tm-cert-ph-foot');
    echo html_writer::tag('button', get_string('cert_batch_make_summary', 'local_tm_certification'), [
        'type' => 'submit',
        'class' => 'btn btn-primary btn-sm',
    ]);
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
}
echo html_writer::end_div();

if ($summary !== null) {
    echo html_writer::start_div('tm-cert-panel');
    echo html_writer::start_div('tm-cert-ph');
    echo html_writer::tag('h3', get_string('cert_batch_summary_heading', 'local_tm_certification'));
    echo html_writer::start_div('tm-cert-ph-ops');
    echo dashboard_helper::render_pill([
        'label' => get_string('cert_batch_summary_ok', 'local_tm_certification', $summary['downloadable']),
        'mod' => 'ok',
    ]);
    echo dashboard_helper::render_pill([
        'label' => get_string('cert_batch_summary_miss', 'local_tm_certification', $summary['missing']),
        'mod' => 'bad',
    ]);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::div(
        get_string('cert_batch_summary_hint', 'local_tm_certification'),
        'tm-cert-alert-slim'
    );

    echo html_writer::start_tag('table', ['class' => 'tm-cert-data']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('fullname'))
        . html_writer::tag('th', get_string('candidate_id', 'local_tm_certification'))
        . html_writer::tag('th', get_string('cert_batch_col_moodle', 'local_tm_certification', $levelname))
        . html_writer::tag('th', get_string('cert_batch_col_result', 'local_tm_certification'))
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($summary['rows'] as $srow) {
        $u = $srow->user;
        $trclass = $srow->available ? 'tm-cert-row-ok' : 'tm-cert-row-miss';
        echo html_writer::start_tag('tr', ['class' => $trclass]);
        echo html_writer::tag('td', fullname($u));
        echo html_writer::tag('td', s((string)($u->candidateid ?? '')), ['class' => 'tm-cert-id']);
        echo html_writer::tag('td', dashboard_helper::render_pill([
            'label' => $srow->statuslabel,
            'mod' => $srow->statusmod,
        ]));
        echo html_writer::tag('td', s($srow->result));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::start_div('tm-cert-ph tm-cert-ph-foot');
    if ($summary['downloadable'] > 0) {
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => (new moodle_url('/local/tm_certification/admin/cert_batch.php'))->out(false),
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sessionid', 'value' => $sessionid]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'download']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        foreach ($selectedsession as $uid) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'userids[]',
                'value' => (int)$uid,
            ]);
        }
        echo html_writer::tag('button',
            get_string('cert_batch_confirm_zip', 'local_tm_certification', $summary['downloadable']),
            ['type' => 'submit', 'class' => 'btn btn-success']
        );
        echo html_writer::end_tag('form');
    } else {
        echo html_writer::span(get_string('cert_batch_zip_empty', 'local_tm_certification'), 'tm-cert-muted');
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo $OUTPUT->footer();
