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

use local_tm_certification\application_manager;
use local_tm_certification\dashboard_helper;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
permissions_manager::require_view_access();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('nav_dashboard', 'local_tm_certification'));
$PAGE->set_heading(get_string('nav_dashboard', 'local_tm_certification'));

if (!permissions_manager::is_dashboard_globally_enabled() && !permissions_manager::user_can_manage()) {
    redirect(new moodle_url('/'), get_string('error_dashboard_disabled', 'local_tm_certification'),
        null, \core\output\notification::NOTIFY_WARNING);
}

$view = permissions_manager::resolve_dashboard_view();
if ($view === 'none') {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error_dashboard_view_none', 'local_tm_certification'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

$cover = dashboard_helper::cover_for_view($view);
$levels = session_manager::level_options();
$statusopts = application_manager::status_options();

echo $OUTPUT->header();

echo html_writer::start_div('tm-cert-dash');
echo html_writer::start_div('tm-cert-cover');
echo html_writer::tag('h2', s($cover['title']));
if (!empty($cover['badges'])) {
    echo html_writer::start_div('tm-cert-badges');
    foreach ($cover['badges'] as $badge) {
        echo html_writer::span(s($badge), 'tm-cert-badge');
    }
    echo html_writer::end_div();
}
echo html_writer::tag('p', s($cover['meta']), ['class' => 'tm-cert-meta']);
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/tm_certification/my_certificates.php'),
        get_string('nav_my_certificates', 'local_tm_certification'),
        ['class' => 'btn btn-secondary btn-sm']
    ),
    'tm-cert-cover-actions'
);
echo html_writer::end_div();

if ($view === 'manage') {
    $pending = application_manager::get_applications(['status' => application_manager::STATUS_PENDING]);
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/tm_certification/admin/edit_session.php'),
            get_string('dash_action_create_session', 'local_tm_certification'),
            ['class' => 'btn btn-primary']
        )
        . ' '
        . html_writer::link(
            new moodle_url('/local/tm_certification/admin/sessions.php'),
            get_string('dash_action_manage_sessions', 'local_tm_certification'),
            ['class' => 'btn btn-secondary']
        )
        . ' '
        . html_writer::link(
            new moodle_url('/local/tm_certification/admin/review.php'),
            get_string('dash_action_review', 'local_tm_certification')
                . ' (' . get_string('dash_pending_count', 'local_tm_certification', count($pending)) . ')',
            ['class' => 'btn btn-warning']
        ),
        'tm-cert-toolbar'
    );

    $opensessions = session_manager::get_sessions(['status' => session_manager::STATUS_OPEN]);

    echo html_writer::start_div('tm-cert-grid');
    echo html_writer::start_div('tm-cert-card');
    echo html_writer::tag('h3', get_string('dash_section_pending', 'local_tm_certification'));
    if (empty($pending)) {
        echo html_writer::tag('p', get_string('app_empty_list', 'local_tm_certification'), ['class' => 'text-muted']);
    } else {
        foreach (array_slice($pending, 0, 8) as $app) {
            $a = (object)[
                'id' => (int)$app->id,
                'level' => $levels[$app->certlevel] ?? $app->certlevel,
                'when' => userdate((int)$app->starttime, get_string('strftimedatetimeshort', 'langconfig')),
                'status' => $statusopts[(int)$app->status] ?? '',
            ];
            echo html_writer::div(get_string('dash_app_line', 'local_tm_certification', $a), 'mb-1');
        }
        echo html_writer::link(
            new moodle_url('/local/tm_certification/admin/review.php'),
            get_string('dash_action_review', 'local_tm_certification'),
            ['class' => 'btn btn-sm btn-primary mt-2']
        );
    }
    echo html_writer::end_div();

    echo html_writer::start_div('tm-cert-card');
    echo html_writer::tag('h3', get_string('dash_section_sessions_manage', 'local_tm_certification'));
    if (empty($opensessions)) {
        echo html_writer::tag('p', get_string('dash_empty_sessions', 'local_tm_certification'), ['class' => 'text-muted']);
    } else {
        foreach (array_slice($opensessions, 0, 8) as $s) {
            $line = format_string($s->name) . ' · '
                . ($levels[$s->certlevel] ?? $s->certlevel) . ' · '
                . userdate((int)$s->starttime, get_string('strftimedatetimeshort', 'langconfig'))
                . ' · ' . session_manager::count_enrols((int)$s->id) . ' '
                . get_string('session_enrol_count', 'local_tm_certification');
            echo html_writer::start_div('mb-2');
            echo html_writer::div($line);
            echo html_writer::link(
                new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $s->id]),
                get_string('dash_action_temp_enrol', 'local_tm_certification'),
                ['class' => 'btn btn-sm btn-warning']
            );
            echo ' ';
            echo html_writer::link(
                new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $s->id]),
                get_string('dash_action_roster', 'local_tm_certification'),
                ['class' => 'btn btn-sm btn-secondary']
            );
            echo html_writer::end_div();
        }
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
} else if ($view === 'instruct') {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/tm_certification/apply.php'),
            get_string('dash_action_apply', 'local_tm_certification'),
            ['class' => 'btn btn-primary']
        ),
        'tm-cert-toolbar'
    );
    $mysessions = session_manager::get_sessions([
        'status' => session_manager::STATUS_OPEN,
        'instructorid' => (int)$USER->id,
    ]);
    $myapps = application_manager::get_applications(['applicantid' => (int)$USER->id]);
    echo html_writer::start_div('tm-cert-grid');
    echo html_writer::start_div('tm-cert-card');
    echo html_writer::tag('h3', get_string('dash_section_my_sessions', 'local_tm_certification'));
    if (empty($mysessions)) {
        echo html_writer::tag('p', get_string('dash_empty_sessions', 'local_tm_certification'), ['class' => 'text-muted']);
    } else {
        foreach ($mysessions as $s) {
            echo html_writer::start_div('mb-2');
            echo html_writer::div(format_string($s->name) . ' · ' . userdate((int)$s->starttime));
            echo html_writer::link(
                new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $s->id]),
                get_string('dash_action_temp_enrol', 'local_tm_certification'),
                ['class' => 'btn btn-sm btn-warning']
            );
            echo html_writer::end_div();
        }
    }
    echo html_writer::end_div();
    echo html_writer::start_div('tm-cert-card');
    echo html_writer::tag('h3', get_string('dash_section_my_applications', 'local_tm_certification'));
    if (empty($myapps)) {
        echo html_writer::tag('p', get_string('dash_empty_applications', 'local_tm_certification'), ['class' => 'text-muted']);
    } else {
        foreach (array_slice($myapps, 0, 10) as $app) {
            $a = (object)[
                'id' => (int)$app->id,
                'level' => $levels[$app->certlevel] ?? $app->certlevel,
                'when' => userdate((int)$app->starttime, get_string('strftimedatetimeshort', 'langconfig')),
                'status' => $statusopts[(int)$app->status] ?? '',
            ];
            echo html_writer::div(get_string('dash_app_line', 'local_tm_certification', $a), 'mb-1');
        }
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('tm-cert-card');
    echo html_writer::tag('h3', get_string('dash_section_my_exams', 'local_tm_certification'));
    echo html_writer::tag('p', get_string('dash_empty_placeholder', 'local_tm_certification'), ['class' => 'text-muted']);
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo $OUTPUT->footer();
