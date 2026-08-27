<?php
/**
 * Certification dashboard (role-dynamic shell).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/permissions_manager.php');
require_once(__DIR__ . '/classes/dashboard_helper.php');
require_once(__DIR__ . '/classes/session_manager.php');
require_once(__DIR__ . '/classes/application_manager.php');
require_once(__DIR__ . '/classes/enrolment_manager.php');
require_once(__DIR__ . '/classes/assessment_manager.php');
require_once(__DIR__ . '/classes/course_access_helper.php');

use local_tm_certification\application_manager;
use local_tm_certification\assessment_manager;
use local_tm_certification\course_access_helper;
use local_tm_certification\dashboard_helper;
use local_tm_certification\enrolment_manager;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
permissions_manager::require_view_access();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/index.php'));
$PAGE->set_pagelayout('standard');

if (!permissions_manager::is_dashboard_globally_enabled() && !permissions_manager::user_can_manage()) {
    redirect(new moodle_url('/'), get_string('error_dashboard_disabled', 'local_tm_certification'),
        null, \core\output\notification::NOTIFY_WARNING);
}

$view = permissions_manager::resolve_dashboard_view();
if ($view === 'instruct') {
    course_access_helper::sync_instructor_access((int)$USER->id);
}
if ($view === 'none') {
    $PAGE->set_title(get_string('nav_dashboard', 'local_tm_certification'));
    $PAGE->set_heading(get_string('nav_dashboard', 'local_tm_certification'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error_dashboard_view_none', 'local_tm_certification'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

$PAGE->set_title(dashboard_helper::title_for_view($view));
$PAGE->set_heading(dashboard_helper::title_for_view($view));

$cover = dashboard_helper::cover_for_view($view);
$levels = session_manager::level_options();
$statusopts = application_manager::status_options();

/**
 * Render session action ops for manage / instruct rows.
 */
$render_session_ops = static function(\stdClass $s) use ($view): string {
    $ended = session_manager::is_ended($s);
    $ops = html_writer::start_div('tm-cert-ops');
    if (!$ended) {
        $ops .= html_writer::link(
            new moodle_url('/local/tm_certification/admin/enrol.php', ['sessionid' => $s->id]),
            get_string('dash_action_temp_enrol', 'local_tm_certification'),
            ['class' => 'btn btn-sm tm-cert-btn-warn']
        );
    }
    $ops .= html_writer::link(
        new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $s->id]),
        get_string('dash_action_roster', 'local_tm_certification'),
        ['class' => 'btn btn-sm btn-secondary']
    );
    $scoreclass = ($view === 'instruct') ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary';
    $ops .= html_writer::link(
        new moodle_url('/local/tm_certification/admin/practical_scores.php', ['sessionid' => $s->id]),
        get_string('dash_action_practical', 'local_tm_certification'),
        ['class' => $scoreclass]
    );
    $ops .= html_writer::end_div();
    return $ops;
};

/**
 * Session list row HTML.
 */
$render_session_row = static function(\stdClass $s) use ($levels, $render_session_ops): string {
    $when = userdate((int)$s->starttime, get_string('strftimedatetimeshort', 'langconfig'));
    $venue = '';
    if (!empty($s->venuename) || !empty($s->venuecode)) {
        $venue = trim((string)($s->venuecode ?? '') . ' ' . (string)($s->venuename ?? ''));
    } else if (!empty($s->venue)) {
        $venue = (string)$s->venue;
    }
    $people = get_string('dash_session_people', 'local_tm_certification', session_manager::count_enrols((int)$s->id));
    $subparts = array_filter([$when, $venue !== '' ? s($venue) : null, $people]);
    $sub = implode(' · ', $subparts) . ' · ' . dashboard_helper::session_status_pill($s);

    $html = html_writer::start_div('tm-cert-row');
    $html .= html_writer::start_div();
    $html .= html_writer::div(format_string($s->name), 'tm-cert-row-name');
    $html .= html_writer::div($sub, 'tm-cert-row-sub');
    $html .= html_writer::end_div();
    $html .= $render_session_ops($s);
    $html .= html_writer::end_div();
    return $html;
};

echo $OUTPUT->header();

echo html_writer::start_div('tm-cert-dash');

// Cover / head.
echo html_writer::start_div('tm-cert-head');
echo html_writer::start_div();
echo html_writer::tag('h2', s($cover['title']));
if (!empty($cover['subtitle'])) {
    echo html_writer::div(s($cover['subtitle']), 'tm-cert-head-subtitle');
}
if (!empty($cover['badges'])) {
    echo html_writer::start_div('tm-cert-facts');
    foreach ($cover['badges'] as $badge) {
        echo dashboard_helper::render_pill($badge);
    }
    echo html_writer::end_div();
}
if (!empty($cover['meta'])) {
    $metacls = 'tm-cert-deadline';
    if (!empty($cover['meta_urgent'])) {
        $metacls .= ' urgent';
    }
    echo html_writer::div(s($cover['meta']), $metacls);
}
echo html_writer::end_div();

echo html_writer::start_div('tm-cert-head-actions');
if ($view === 'manage') {
    echo html_writer::link(
        new moodle_url('/local/tm_certification/admin/edit_session.php'),
        get_string('dash_action_create_session', 'local_tm_certification'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::link(
        new moodle_url('/local/tm_certification/admin/sessions.php'),
        get_string('dash_action_manage_sessions', 'local_tm_certification'),
        ['class' => 'btn btn-secondary']
    );
    echo html_writer::link(
        new moodle_url('/local/tm_certification/admin/review.php'),
        get_string('dash_action_review_apps', 'local_tm_certification'),
        ['class' => 'btn btn-secondary']
    );
} else if ($view === 'instruct') {
    echo html_writer::link(
        new moodle_url('/local/tm_certification/apply.php'),
        get_string('dash_action_apply', 'local_tm_certification'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::link(
        new moodle_url('/local/tm_certification/my_certificates.php'),
        get_string('nav_my_certificates', 'local_tm_certification'),
        ['class' => 'btn btn-secondary']
    );
} else {
    echo html_writer::link(
        new moodle_url('/local/tm_certification/my_certificates.php'),
        get_string('nav_my_certificates', 'local_tm_certification'),
        ['class' => 'btn btn-secondary']
    );
}
echo html_writer::end_div();
echo html_writer::end_div();

if ($view === 'manage') {
    $pending = application_manager::get_applications(['status' => application_manager::STATUS_PENDING]);
    $opensessions = session_manager::get_sessions(); // Open + ended.
    $openonly = session_manager::get_sessions(['status' => session_manager::STATUS_OPEN]);
    $weekcount = dashboard_helper::count_enrols_since(time() - (7 * DAYSECS));

    echo html_writer::start_div('tm-cert-kpis');
    echo html_writer::div(
        html_writer::div((string)count($pending), 'n')
        . html_writer::div(get_string('dash_kpi_pending', 'local_tm_certification'), 'l'),
        'tm-cert-kpi'
    );
    echo html_writer::div(
        html_writer::div((string)count($openonly), 'n')
        . html_writer::div(get_string('dash_kpi_open', 'local_tm_certification'), 'l'),
        'tm-cert-kpi'
    );
    echo html_writer::div(
        html_writer::div((string)$weekcount, 'n')
        . html_writer::div(get_string('dash_kpi_week', 'local_tm_certification'), 'l'),
        'tm-cert-kpi'
    );
    echo html_writer::end_div();

    // Pending applications.
    echo html_writer::start_div('tm-cert-panel');
    echo html_writer::start_div('tm-cert-ph');
    echo html_writer::tag('h3', get_string('dash_section_pending', 'local_tm_certification'));
    echo html_writer::link(
        new moodle_url('/local/tm_certification/admin/review.php'),
        get_string('dash_action_review', 'local_tm_certification'),
        ['class' => 'btn btn-sm btn-secondary']
    );
    echo html_writer::end_div();
    if (empty($pending)) {
        echo html_writer::div(get_string('app_empty_list', 'local_tm_certification'), 'tm-cert-empty');
    } else {
        echo html_writer::start_tag('table', ['class' => 'tm-cert-data']);
        echo html_writer::start_tag('thead');
        echo html_writer::tag('tr',
            html_writer::tag('th', get_string('app_col_applicant', 'local_tm_certification'))
            . html_writer::tag('th', get_string('app_col_session', 'local_tm_certification'))
            . html_writer::tag('th', '')
        );
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');
        foreach (array_slice($pending, 0, 8) as $app) {
            $venue = '';
            if (!empty($app->venuecode) || !empty($app->venuename)) {
                $venue = trim((string)($app->venuecode ?? '') . ' ' . (string)($app->venuename ?? ''));
            }
            $level = $levels[$app->certlevel] ?? $app->certlevel;
            $sessionlabel = '[' . $level . ']' . get_string('session_name_suffix', 'local_tm_certification');
            // Prefer stored display if present; otherwise level + venue/time.
            $when = userdate((int)$app->starttime, get_string('strftimedatetimeshort', 'langconfig'));
            $sub = $when . ($venue !== '' ? ' · ' . s($venue) : '');
            $ops = html_writer::start_div('tm-cert-ops');
            $ops .= html_writer::link(
                new moodle_url('/local/tm_certification/admin/review.php', [
                    'action' => 'approve',
                    'id' => $app->id,
                ]),
                get_string('app_action_approve', 'local_tm_certification'),
                ['class' => 'btn btn-sm tm-cert-btn-accent']
            );
            $ops .= html_writer::link(
                new moodle_url('/local/tm_certification/admin/review.php', [
                    'action' => 'reject',
                    'id' => $app->id,
                ]),
                get_string('app_action_reject', 'local_tm_certification'),
                ['class' => 'btn btn-sm btn-secondary']
            );
            $ops .= html_writer::end_div();

            $anote = trim((string)($app->applicantnote ?? ''));
            $sessioncell = html_writer::div(s($sessionlabel), 'tm-cert-row-name')
                . html_writer::div($sub, 'tm-cert-row-sub');
            if ($anote !== '') {
                $sessioncell .= html_writer::div(
                    get_string('app_applicant_note_short', 'local_tm_certification') . '：' . s($anote),
                    'tm-cert-row-sub'
                );
            }
            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', fullname($app->applicant));
            echo html_writer::tag('td', $sessioncell);
            echo html_writer::tag('td', $ops);
            echo html_writer::end_tag('tr');
        }
        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    }
    echo html_writer::end_div();

    // Sessions.
    echo html_writer::start_div('tm-cert-panel');
    echo html_writer::start_div('tm-cert-ph');
    echo html_writer::tag('h3', get_string('dash_section_sessions_manage', 'local_tm_certification'));
    echo html_writer::end_div();
    if (empty($opensessions)) {
        echo html_writer::div(get_string('dash_empty_sessions', 'local_tm_certification'), 'tm-cert-empty');
    } else {
        echo html_writer::start_div('tm-cert-pb');
        foreach (array_slice($opensessions, 0, 12) as $s) {
            echo $render_session_row($s);
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();

} else if ($view === 'instruct') {
    $mysessions = session_manager::get_sessions([
        'instructorid' => (int)$USER->id,
    ]);
    $myapps = application_manager::get_applications(['applicantid' => (int)$USER->id]);

    echo html_writer::start_div('tm-cert-panel');
    echo html_writer::start_div('tm-cert-ph');
    echo html_writer::tag('h3', get_string('dash_section_my_sessions', 'local_tm_certification'));
    echo html_writer::end_div();
    if (empty($mysessions)) {
        echo html_writer::div(get_string('dash_empty_sessions', 'local_tm_certification'), 'tm-cert-empty');
    } else {
        echo html_writer::start_div('tm-cert-pb');
        foreach ($mysessions as $s) {
            echo $render_session_row($s);
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();

    echo html_writer::start_div('tm-cert-panel');
    echo html_writer::start_div('tm-cert-ph');
    echo html_writer::tag('h3', get_string('dash_section_my_applications', 'local_tm_certification'));
    echo html_writer::end_div();
    if (empty($myapps)) {
        echo html_writer::div(get_string('dash_empty_applications', 'local_tm_certification'), 'tm-cert-empty');
    } else {
        echo html_writer::start_div('tm-cert-pb');
        foreach (array_slice($myapps, 0, 10) as $app) {
            $venue = '';
            if (!empty($app->venuecode) || !empty($app->venuename)) {
                $venue = trim((string)($app->venuecode ?? '') . ' ' . (string)($app->venuename ?? ''));
            }
            $level = $levels[$app->certlevel] ?? $app->certlevel;
            $name = '[' . $level . ']' . get_string('session_name_suffix', 'local_tm_certification');
            $when = userdate((int)$app->starttime, get_string('strftimedatetimeshort', 'langconfig'));
            $statuslabel = $statusopts[(int)$app->status] ?? '';
            $subparts = array_filter([
                $when,
                $venue !== '' ? s($venue) : null,
            ]);
            $sub = implode(' · ', $subparts) . ' · ' . dashboard_helper::render_pill([
                'label' => $statuslabel,
                'mod' => ((int)$app->status === application_manager::STATUS_PENDING) ? '' : 'dim',
            ]);
            $anote = trim((string)($app->applicantnote ?? ''));
            $mnote = trim((string)($app->managernote ?? ''));

            echo html_writer::start_div('tm-cert-row');
            echo html_writer::start_div();
            echo html_writer::div(s($name), 'tm-cert-row-name');
            echo html_writer::div($sub, 'tm-cert-row-sub');
            if ($anote !== '') {
                echo html_writer::div(
                    get_string('app_applicant_note_short', 'local_tm_certification') . '：' . s($anote),
                    'tm-cert-row-sub'
                );
            }
            if ($mnote !== '') {
                echo html_writer::div(
                    get_string('app_manager_note_short', 'local_tm_certification') . '：' . s($mnote),
                    'tm-cert-row-sub'
                );
            }
            echo html_writer::end_div();
            echo html_writer::end_div();
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();

} else {
    $mysessions = enrolment_manager::get_examinee_sessions((int)$USER->id);

    echo html_writer::start_div('tm-cert-panel');
    echo html_writer::start_div('tm-cert-ph');
    echo html_writer::tag('h3', get_string('dash_section_my_exams', 'local_tm_certification'));
    echo html_writer::end_div();
    if (empty($mysessions)) {
        echo html_writer::div(get_string('dash_empty_exams', 'local_tm_certification'), 'tm-cert-empty');
    } else {
        echo html_writer::start_div('tm-cert-pb');
        foreach ($mysessions as $s) {
            $when = userdate((int)$s->starttime, get_string('strftimedatetimeshort', 'langconfig'));
            $venue = '';
            if (!empty($s->venuename) || !empty($s->venuecode)) {
                $venue = trim((string)($s->venuecode ?? '') . ' ' . (string)($s->venuename ?? ''));
            } else if (!empty($s->venue)) {
                $venue = (string)$s->venue;
            }
            $instructorlabel = '';
            if (!empty($s->instructor)) {
                $instructorlabel = get_string('examinee_session_instructor', 'local_tm_certification',
                    fullname($s->instructor));
            }
            $subparts = array_filter([
                $when,
                $venue !== '' ? s($venue) : null,
                $instructorlabel !== '' ? s($instructorlabel) : null,
            ]);
            $sub = implode(' · ', $subparts) . ' · ' . dashboard_helper::session_status_pill($s);

            $btn = assessment_manager::examinee_written_button($s);
            $ops = html_writer::start_div('tm-cert-ops');
            if (!empty($btn['show'])) {
                if (!empty($btn['enabled']) && !empty($btn['url'])) {
                    $ops .= html_writer::link(
                        $btn['url'],
                        $btn['label'],
                        ['class' => 'btn btn-sm btn-primary']
                    );
                } else {
                    $ops .= html_writer::span(
                        $btn['label'],
                        'btn btn-sm btn-secondary disabled',
                        [
                            'aria-disabled' => 'true',
                            'title' => (string)($btn['hint'] ?? ''),
                        ]
                    );
                }
            }
            $ops .= html_writer::end_div();

            echo html_writer::start_div('tm-cert-row');
            echo html_writer::start_div();
            echo html_writer::div(format_string($s->name), 'tm-cert-row-name');
            echo html_writer::div($sub, 'tm-cert-row-sub');
            if (!empty($btn['show']) && empty($btn['enabled']) && !empty($btn['hint'])) {
                echo html_writer::div(s($btn['hint']), 'tm-cert-row-sub tm-cert-muted');
            }
            echo html_writer::end_div();
            echo $ops;
            echo html_writer::end_div();
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo $OUTPUT->footer();
