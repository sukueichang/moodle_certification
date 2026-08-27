<?php
/**
 * Examinee view: own scores for a rostered session.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/permissions_manager.php');
require_once(__DIR__ . '/classes/session_manager.php');
require_once(__DIR__ . '/classes/enrolment_manager.php');
require_once(__DIR__ . '/classes/assessment_manager.php');
require_once(__DIR__ . '/classes/dashboard_helper.php');
require_once(__DIR__ . '/classes/certificate_helper.php');

use local_tm_certification\assessment_manager;
use local_tm_certification\certificate_helper;
use local_tm_certification\dashboard_helper;
use local_tm_certification\enrolment_manager;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
permissions_manager::require_view_access();

$sessionid = required_param('sessionid', PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/my_result.php', ['sessionid' => $sessionid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('result_page_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('result_page_title', 'local_tm_certification'));

$session = session_manager::get_session($sessionid);
$enrol = $DB->get_record_select(
    'local_tm_cert_enrols',
    'sessionid = :sessionid AND userid = :userid AND status <> :cancelled',
    [
        'sessionid' => $sessionid,
        'userid' => (int)$USER->id,
        'cancelled' => enrolment_manager::ENROL_CANCELLED,
    ]
);
if (!$enrol) {
    throw new moodle_exception('error_result_not_enrolled', 'local_tm_certification');
}

if (!empty($session->venueid)) {
    $venuerow = $DB->get_record('local_tm_cert_venues', ['id' => (int)$session->venueid], 'name, venuecode');
    if ($venuerow) {
        $session->venuename = $venuerow->name;
        $session->venuecode = $venuerow->venuecode;
    }
}

$instructorname = '';
if (!empty($session->instructorid)) {
    $instructor = $DB->get_record('user', ['id' => (int)$session->instructorid, 'deleted' => 0]);
    if ($instructor) {
        $instructorname = fullname($instructor);
    }
}

$levels = session_manager::level_options();
$levellabel = $levels[$session->certlevel] ?? $session->certlevel;
$when = userdate((int)$session->starttime, get_string('strftimedatetimeshort', 'langconfig'));
$venue = '';
if (!empty($session->venuename) || !empty($session->venuecode)) {
    $venue = trim((string)($session->venuecode ?? '') . ' ' . (string)($session->venuename ?? ''));
} else if (!empty($session->venue)) {
    $venue = (string)$session->venue;
}
$statuslabel = session_manager::status_label($session);

$overall = assessment_manager::evaluate_overall((int)$enrol->id);
$written = $overall['written'] ?? [
    'required' => false,
    'passed' => null,
    'score' => null,
];

$writtenlabel = get_string('written_not_required', 'local_tm_certification');
$writtenmod = 'dim';
if (!empty($written['required'])) {
    if ($written['passed'] === null) {
        $writtenlabel = get_string('written_no_score', 'local_tm_certification');
        $writtenmod = 'dim';
    } else if (!empty($written['passed'])) {
        $scoretxt = isset($written['score']) && $written['score'] !== null
            ? (string)$written['score'] : '';
        $writtenlabel = $scoretxt !== ''
            ? get_string('written_pass_with_score', 'local_tm_certification', $scoretxt)
            : get_string('result_overall_pass', 'local_tm_certification');
        $writtenmod = 'ok';
    } else {
        $scoretxt = isset($written['score']) && $written['score'] !== null
            ? (string)$written['score'] : '';
        $writtenlabel = $scoretxt !== ''
            ? get_string('written_fail_with_score', 'local_tm_certification', $scoretxt)
            : get_string('result_overall_fail', 'local_tm_certification');
        $writtenmod = 'bad';
    }
}

$practicalpending = ($enrol->practicalscore === null || $enrol->practicalscore === '');
if ($practicalpending) {
    $practicallabel = get_string('result_practical_pending', 'local_tm_certification');
    $practicalmod = 'dim';
} else {
    $scorebit = get_string('result_practical_score', 'local_tm_certification',
        format_float((float)$enrol->practicalscore, 2));
    $passbit = !empty($overall['practical_pass'])
        ? get_string('result_overall_pass', 'local_tm_certification')
        : get_string('result_overall_fail', 'local_tm_certification');
    $practicallabel = $passbit . ' · ' . $scorebit;
    $practicalmod = !empty($overall['practical_pass']) ? 'ok' : 'bad';
}

if ($overall['overall'] === null) {
    $overalllabel = get_string('result_overall_pending', 'local_tm_certification');
    $overallmod = 'dim';
} else if (!empty($overall['overall'])) {
    $overalllabel = get_string('result_overall_pass', 'local_tm_certification');
    $overallmod = 'ok';
} else {
    $overalllabel = get_string('result_overall_fail', 'local_tm_certification');
    $overallmod = 'bad';
}

$passed = ($overall['overall'] === true);
$certdownloadurl = null;
if ($passed) {
    $certrow = certificate_helper::get_user_exam_certificate_for_level(
        (int)$USER->id,
        (string)$session->certlevel
    );
    if ($certrow && !empty($certrow->cmid)) {
        $certdownloadurl = certificate_helper::get_own_download_url((int)$certrow->cmid);
    }
}

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash tm-cert-result-page');

// --- Session summary card ---
echo html_writer::start_div('tm-cert-result-card');
echo html_writer::start_div('tm-cert-result-card-head');
echo html_writer::div(get_string('result_section_session', 'local_tm_certification'), 'tm-cert-result-eyebrow');
echo html_writer::start_div('tm-cert-result-title-row');
echo html_writer::tag('h2', format_string($session->name), ['class' => 'tm-cert-result-title']);
echo dashboard_helper::render_pill([
    'label' => $statuslabel,
    'mod' => session_manager::is_ended($session) ? 'dim' : 'ok',
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('tm-cert-result-meta');
$metaitems = [
    [get_string('result_meta_level', 'local_tm_certification'), s($levellabel)],
    [get_string('result_meta_when', 'local_tm_certification'), s($when)],
];
if ($venue !== '') {
    $metaitems[] = [get_string('result_meta_venue', 'local_tm_certification'), s($venue)];
}
if ($instructorname !== '') {
    $metaitems[] = [
        get_string('result_meta_instructor', 'local_tm_certification'),
        s($instructorname),
    ];
}
foreach ($metaitems as [$mlabel, $mvalue]) {
    echo html_writer::start_div('tm-cert-result-meta-item');
    echo html_writer::div($mlabel, 'tm-cert-result-meta-label');
    echo html_writer::div($mvalue, 'tm-cert-result-meta-value');
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

// --- Scores ---
echo html_writer::start_div('tm-cert-result-card');
echo html_writer::div(get_string('result_section_scores', 'local_tm_certification'), 'tm-cert-result-eyebrow');
echo html_writer::start_div('tm-cert-score-stack');
$scorerows = [
    [get_string('result_item_written', 'local_tm_certification'), $writtenlabel, $writtenmod],
    [get_string('result_item_practical', 'local_tm_certification'), $practicallabel, $practicalmod],
    [get_string('result_item_overall', 'local_tm_certification'), $overalllabel, $overallmod, true],
];
foreach ($scorerows as $row) {
    $item = $row[0];
    $detail = $row[1];
    $mod = $row[2];
    $emphasize = !empty($row[3]);
    $cls = 'tm-cert-score-row' . ($emphasize ? ' tm-cert-score-row-overall' : '');
    echo html_writer::start_div($cls);
    echo html_writer::div(s($item), 'tm-cert-score-label');
    echo html_writer::div(
        dashboard_helper::render_pill(['label' => $detail, 'mod' => $mod]),
        'tm-cert-score-value'
    );
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

// --- Actions: one primary (download when passed), secondary back ---
echo html_writer::start_div('tm-cert-result-actions');
if ($certdownloadurl) {
    echo html_writer::link(
        $certdownloadurl,
        get_string('result_download_cert', 'local_tm_certification'),
        ['class' => 'btn btn-primary']
    );
}
echo html_writer::link(
    new moodle_url('/local/tm_certification/index.php'),
    get_string('result_back', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
