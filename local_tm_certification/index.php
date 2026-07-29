<?php
/**
 * Certification dashboard (role-dynamic shell).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/permissions_manager.php');
require_once(__DIR__ . '/classes/dashboard_helper.php');

use local_tm_certification\dashboard_helper;
use local_tm_certification\permissions_manager;

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
    echo html_writer::div(
        html_writer::span(get_string('dash_action_create_session', 'local_tm_certification'), 'btn btn-primary disabled'),
        'tm-cert-toolbar'
    );
    echo html_writer::start_div('tm-cert-grid');
    echo html_writer::start_div('tm-cert-card');
    echo html_writer::tag('h3', get_string('dash_section_pending', 'local_tm_certification'));
    echo html_writer::tag('p', get_string('dash_empty_placeholder', 'local_tm_certification'), ['class' => 'text-muted']);
    echo html_writer::end_div();
    echo html_writer::start_div('tm-cert-card');
    echo html_writer::tag('h3', get_string('dash_section_sessions_manage', 'local_tm_certification'));
    echo html_writer::tag('p', get_string('dash_empty_placeholder', 'local_tm_certification'), ['class' => 'text-muted']);
    echo html_writer::tag('p',
        html_writer::span(get_string('dash_action_temp_enrol', 'local_tm_certification'), 'btn btn-sm btn-warning disabled')
        . ' '
        . html_writer::span(get_string('dash_action_roster', 'local_tm_certification'), 'btn btn-sm btn-secondary disabled')
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
} else if ($view === 'instruct') {
    echo html_writer::div(
        html_writer::span(get_string('dash_action_apply', 'local_tm_certification'), 'btn btn-primary disabled'),
        'tm-cert-toolbar'
    );
    echo html_writer::start_div('tm-cert-grid');
    echo html_writer::start_div('tm-cert-card');
    echo html_writer::tag('h3', get_string('dash_section_my_sessions', 'local_tm_certification'));
    echo html_writer::tag('p', get_string('dash_empty_placeholder', 'local_tm_certification'), ['class' => 'text-muted']);
    echo html_writer::tag('p',
        html_writer::span(get_string('dash_action_temp_enrol', 'local_tm_certification'), 'btn btn-sm btn-warning disabled')
        . ' '
        . html_writer::span(get_string('dash_action_practical', 'local_tm_certification'), 'btn btn-sm btn-success disabled')
        . ' '
        . html_writer::span(get_string('dash_action_roster', 'local_tm_certification'), 'btn btn-sm btn-secondary disabled')
    );
    echo html_writer::end_div();
    echo html_writer::start_div('tm-cert-card');
    echo html_writer::tag('h3', get_string('dash_section_my_applications', 'local_tm_certification'));
    echo html_writer::tag('p', get_string('dash_empty_placeholder', 'local_tm_certification'), ['class' => 'text-muted']);
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
