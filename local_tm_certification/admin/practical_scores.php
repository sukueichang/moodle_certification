<?php
/**
 * Enter practical scores for a certification session.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/enrolment_manager.php');
require_once(__DIR__ . '/../classes/assessment_manager.php');
require_once(__DIR__ . '/../classes/dashboard_helper.php');

use local_tm_certification\assessment_manager;
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
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/practical_scores.php', ['sessionid' => $sessionid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('practical_page_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('practical_page_title', 'local_tm_certification'));

$threshold = assessment_manager::practical_pass_score((string)$session->certlevel);
$haswritten = assessment_manager::has_written_exam((string)$session->certlevel);
$rosterurl = new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]);

if (optional_param('save', 0, PARAM_INT) && confirm_sesskey()) {
    $scores = optional_param_array('score', [], PARAM_RAW);
    $ok = 0;
    $fail = 0;
    $attwarn = 0;
    foreach ($scores as $enrolid => $raw) {
        $enrolid = (int)$enrolid;
        $raw = trim((string)$raw);
        if ($raw === '') {
            continue;
        }
        if (!is_numeric($raw)) {
            $fail++;
            continue;
        }
        try {
            $result = assessment_manager::save_practical_score($enrolid, (float)$raw, (int)$USER->id);
            $ok++;
            if (empty($result['attendance']['ok'])) {
                $attwarn++;
            }
        } catch (Throwable $e) {
            $fail++;
        }
    }
    $msg = get_string('practical_save_result', 'local_tm_certification', (object)[
        'ok' => $ok,
        'fail' => $fail,
    ]);
    if ($attwarn > 0) {
        $msg .= ' ' . get_string('practical_attendance_partial', 'local_tm_certification', $attwarn);
    }
    redirect($PAGE->url, $msg, null, $fail > 0
        ? \core\output\notification::NOTIFY_WARNING
        : \core\output\notification::NOTIFY_SUCCESS);
}

$roster = enrolment_manager::get_session_roster($sessionid);
$count = count($roster);
$thresholdlabel = format_float($threshold, $threshold == floor($threshold) ? 0 : 1);

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash');

echo html_writer::start_div('tm-cert-head');
echo html_writer::start_div();
echo html_writer::tag('h2', get_string('practical_page_title', 'local_tm_certification'));
echo html_writer::start_div('tm-cert-facts');
echo dashboard_helper::render_pill(['label' => format_string($session->name), 'mod' => '']);
echo dashboard_helper::render_pill([
    'label' => get_string('practical_threshold_short', 'local_tm_certification', $thresholdlabel),
    'mod' => '',
]);
echo dashboard_helper::render_pill([
    'label' => get_string('dash_session_people', 'local_tm_certification', $count),
    'mod' => '',
]);
if ($haswritten) {
    echo dashboard_helper::render_pill([
        'label' => get_string('practical_needs_written', 'local_tm_certification'),
        'mod' => 'info',
    ]);
} else {
    echo dashboard_helper::render_pill([
        'label' => get_string('written_not_required', 'local_tm_certification'),
        'mod' => 'dim',
    ]);
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('tm-cert-head-actions');
echo html_writer::link($rosterurl, get_string('roster_back_list', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();

if ($haswritten) {
    echo html_writer::div(get_string('practical_written_hint_short', 'local_tm_certification'), 'tm-cert-hint-info');
}

echo html_writer::start_div('tm-cert-panel');
echo html_writer::start_div('tm-cert-ph');
echo html_writer::tag('h3', get_string('practical_section_title', 'local_tm_certification'));
echo html_writer::span(
    get_string('practical_threshold_rule', 'local_tm_certification', $thresholdlabel),
    'tm-cert-ph-sub'
);
echo html_writer::end_div();

if (empty($roster)) {
    echo html_writer::start_div('tm-cert-empty');
    echo html_writer::tag('strong', get_string('enrol_empty_title', 'local_tm_certification'));
    echo html_writer::div(get_string('practical_empty_hint', 'local_tm_certification'));
    echo html_writer::div(
        html_writer::link($rosterurl, get_string('practical_goto_roster', 'local_tm_certification'),
            ['class' => 'btn btn-primary']),
        'mt-3'
    );
    echo html_writer::end_div();
} else {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $PAGE->url->out(false),
        'class' => 'tm-cert-score-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'save', 'value' => 1]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sessionid', 'value' => $sessionid]);

    echo html_writer::start_tag('table', ['class' => 'tm-cert-data tm-cert-score-table']);
    echo html_writer::start_tag('thead');
    $head = html_writer::tag('th', get_string('fullname'))
        . html_writer::tag('th', get_string('candidate_id', 'local_tm_certification'))
        . html_writer::tag('th', get_string('practical_score', 'local_tm_certification'), ['class' => 'tm-cert-num'])
        . html_writer::tag('th', get_string('practical_result_short', 'local_tm_certification'));
    if ($haswritten) {
        $head .= html_writer::tag('th', get_string('written_result_short', 'local_tm_certification'));
    }
    echo html_writer::tag('tr', $head);
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($roster as $row) {
        $scoreval = ($row->practicalscore !== null && $row->practicalscore !== '')
            ? format_float((float)$row->practicalscore, 2, true, true)
            : '';

        if ($row->practicalscore !== null && $row->practicalscore !== '') {
            $passpill = dashboard_helper::render_pill([
                'label' => ((int)$row->practicalpass === 1)
                    ? get_string('practical_pass', 'local_tm_certification')
                    : get_string('practical_fail', 'local_tm_certification'),
                'mod' => ((int)$row->practicalpass === 1) ? 'ok' : 'bad',
            ]);
        } else {
            $passpill = dashboard_helper::render_pill([
                'label' => get_string('practical_pending', 'local_tm_certification'),
                'mod' => 'dim',
            ]);
        }

        $writtenpill = '';
        if ($haswritten) {
            $written = assessment_manager::check_written_result((int)$row->userid, (string)$session->certlevel);
            if ($written['score'] === null) {
                $writtenpill = dashboard_helper::render_pill([
                    'label' => get_string('written_no_score_short', 'local_tm_certification'),
                    'mod' => 'dim',
                ]);
            } else if (!empty($written['passed'])) {
                $writtenpill = dashboard_helper::render_pill([
                    'label' => get_string('written_pass_short', 'local_tm_certification',
                        format_float($written['score'], 1)),
                    'mod' => 'ok',
                ]);
            } else {
                $writtenpill = dashboard_helper::render_pill([
                    'label' => get_string('written_fail_short', 'local_tm_certification',
                        format_float($written['score'], 1)),
                    'mod' => 'bad',
                ]);
            }
        }

        $input = html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'score[' . (int)$row->id . ']',
            'value' => $scoreval,
            'class' => 'tm-cert-score-input',
            'inputmode' => 'decimal',
            'autocomplete' => 'off',
        ]);

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', s(trim((string)$row->lastname . (string)$row->firstname)),
            ['class' => 'tm-cert-row-name']);
        echo html_writer::tag('td', s((string)($row->candidateid ?? $row->username)), ['class' => 'tm-cert-id']);
        echo html_writer::tag('td', $input, ['class' => 'tm-cert-num']);
        echo html_writer::tag('td', $passpill);
        if ($haswritten) {
            echo html_writer::tag('td', $writtenpill);
        }
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::start_div('tm-cert-panel-foot');
    echo html_writer::span(get_string('practical_blank_skip', 'local_tm_certification'), 'tm-cert-foot-note');
    echo html_writer::start_div('tm-cert-ops');
    echo html_writer::link($rosterurl, get_string('roster_back_list', 'local_tm_certification'),
        ['class' => 'btn btn-secondary']);
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('practical_save', 'local_tm_certification'),
        'class' => 'btn btn-primary',
    ]);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_tag('form');
}

echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
