<?php
/**
 * Enter / view practical assessment for a certification session.
 * Manual components: score entry. Attendance components: read 〔參〕/〔缺〕 status.
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

$level = (string)$session->certlevel;
assessment_manager::ensure_blueprint($level);
$manuals = assessment_manager::get_components($level, assessment_manager::TYPE_MANUAL);
$attendances = assessment_manager::get_components($level, assessment_manager::TYPE_ATTENDANCE);
$practicals = array_merge($attendances, $manuals);
$haswritten = assessment_manager::has_written_exam($level);
$rosterurl = new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]);

if (optional_param('save', 0, PARAM_INT) && confirm_sesskey()) {
    $ok = 0;
    $fail = 0;
    $attwarn = 0;
    foreach ($manuals as $comp) {
        $scores = optional_param_array('score_' . (int)$comp->id, [], PARAM_RAW);
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
            $score = (float)$raw;
            if ($score < 0 || $score > 100) {
                $fail++;
                continue;
            }
            try {
                $result = assessment_manager::save_component_score(
                    $enrolid,
                    (int)$comp->id,
                    $score,
                    (int)$USER->id
                );
                $ok++;
                if (!empty($result['attendance']) && empty($result['attendance']['ok'])) {
                    $attwarn++;
                }
            } catch (Throwable $e) {
                $fail++;
            }
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

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash');

echo html_writer::start_div('tm-cert-head');
echo html_writer::start_div();
echo html_writer::tag('h2', get_string('practical_page_title', 'local_tm_certification'));
echo html_writer::start_div('tm-cert-facts');
echo dashboard_helper::render_pill(['label' => format_string($session->name), 'mod' => '']);
echo dashboard_helper::render_pill([
    'label' => get_string('dash_session_people', 'local_tm_certification', $count),
    'mod' => '',
]);
foreach ($attendances as $comp) {
    echo dashboard_helper::render_pill([
        'label' => $comp->label . ' · ' . get_string('assess_pass_via_present', 'local_tm_certification'),
        'mod' => 'info',
    ]);
}
foreach ($manuals as $comp) {
    $th = format_float((float)$comp->passscore, ((float)$comp->passscore == floor((float)$comp->passscore)) ? 0 : 1);
    echo dashboard_helper::render_pill([
        'label' => $comp->label . ' ≥ ' . $th,
        'mod' => '',
    ]);
}
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

if (!empty($attendances)) {
    echo html_writer::div(get_string('practical_attendance_readonly_hint', 'local_tm_certification'), 'tm-cert-hint-info');
}
if ($haswritten) {
    echo html_writer::div(get_string('practical_written_hint_short', 'local_tm_certification'), 'tm-cert-hint-info');
}

echo html_writer::start_div('tm-cert-panel');
echo html_writer::start_div('tm-cert-ph');
echo html_writer::tag('h3', get_string('practical_section_title', 'local_tm_certification'));
echo html_writer::end_div();

if (empty($practicals)) {
    echo html_writer::div(get_string('practical_no_manuals', 'local_tm_certification'), 'tm-cert-hint-info');
} else if (empty($roster)) {
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
    $hasmanual = !empty($manuals);
    if ($hasmanual) {
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $PAGE->url->out(false),
            'class' => 'tm-cert-score-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'save', 'value' => 1]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sessionid', 'value' => $sessionid]);
    }

    echo html_writer::start_tag('table', ['class' => 'tm-cert-data tm-cert-score-table']);
    echo html_writer::start_tag('thead');
    $head = html_writer::tag('th', get_string('fullname'))
        . html_writer::tag('th', get_string('candidate_id', 'local_tm_certification'));
    foreach ($practicals as $comp) {
        $head .= html_writer::tag('th', s($comp->label));
        $head .= html_writer::tag('th', get_string('practical_result_short', 'local_tm_certification'));
    }
    if ($haswritten) {
        $head .= html_writer::tag('th', get_string('written_result_short', 'local_tm_certification'));
    }
    echo html_writer::tag('tr', $head);
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($roster as $row) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', s(trim((string)$row->lastname . (string)$row->firstname)),
            ['class' => 'tm-cert-row-name']);
        echo html_writer::tag('td', s((string)($row->candidateid ?? $row->username)), ['class' => 'tm-cert-id']);

        foreach ($practicals as $comp) {
            if ((string)$comp->type === assessment_manager::TYPE_ATTENDANCE) {
                $r = assessment_manager::check_attendance_component((int)$row->userid, $comp, $session);
                $cell = html_writer::span(get_string('assess_type_attendance', 'local_tm_certification'), 'text-muted');
                if (!empty($r['pending'])) {
                    $passpill = dashboard_helper::render_pill([
                        'label' => get_string('practical_pending', 'local_tm_certification'),
                        'mod' => 'dim',
                    ]);
                } else if (!empty($r['passed'])) {
                    $passpill = dashboard_helper::render_pill([
                        'label' => get_string('practical_pass', 'local_tm_certification'),
                        'mod' => 'ok',
                    ]);
                } else {
                    $passpill = dashboard_helper::render_pill([
                        'label' => get_string('practical_fail', 'local_tm_certification'),
                        'mod' => 'bad',
                    ]);
                }
                echo html_writer::tag('td', $cell);
                echo html_writer::tag('td', $passpill);
                continue;
            }

            $r = assessment_manager::get_manual_result((int)$row->id, (int)$comp->id);
            if ($r['pending'] && (string)$comp->code === assessment_manager::CODE_PRACTICAL
                    && $row->practicalscore !== null && $row->practicalscore !== '') {
                $r = [
                    'score' => (float)$row->practicalscore,
                    'passed' => ((int)$row->practicalpass === 1),
                    'pending' => false,
                ];
            }
            $scoreval = !$r['pending']
                ? format_float((float)$r['score'], 2, true, true)
                : '';

            if (!$r['pending']) {
                $passpill = dashboard_helper::render_pill([
                    'label' => !empty($r['passed'])
                        ? get_string('practical_pass', 'local_tm_certification')
                        : get_string('practical_fail', 'local_tm_certification'),
                    'mod' => !empty($r['passed']) ? 'ok' : 'bad',
                ]);
            } else {
                $passpill = dashboard_helper::render_pill([
                    'label' => get_string('practical_pending', 'local_tm_certification'),
                    'mod' => 'dim',
                ]);
            }

            $input = html_writer::empty_tag('input', [
                'type' => 'number',
                'name' => 'score_' . (int)$comp->id . '[' . (int)$row->id . ']',
                'value' => $scoreval,
                'class' => 'tm-cert-score-input',
                'min' => '0',
                'max' => '100',
                'step' => 'any',
                'inputmode' => 'decimal',
                'autocomplete' => 'off',
            ]);
            echo html_writer::tag('td', $input, ['class' => 'tm-cert-num']);
            echo html_writer::tag('td', $passpill);
        }

        if ($haswritten) {
            $written = assessment_manager::check_written_result((int)$row->userid, $level);
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
            echo html_writer::tag('td', $writtenpill);
        }
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::start_div('tm-cert-panel-foot');
    if ($hasmanual) {
        echo html_writer::span(get_string('practical_blank_skip', 'local_tm_certification'), 'tm-cert-foot-note');
    }
    echo html_writer::start_div('tm-cert-ops');
    echo html_writer::link($rosterurl, get_string('roster_back_list', 'local_tm_certification'),
        ['class' => 'btn btn-secondary']);
    if ($hasmanual) {
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('practical_save', 'local_tm_certification'),
            'class' => 'btn btn-primary',
        ]);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    if ($hasmanual) {
        echo html_writer::end_tag('form');
    }
}

echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
