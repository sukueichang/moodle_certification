<?php
/**
 * My certificates page (shell — data in P6).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/permissions_manager.php');

use local_tm_certification\permissions_manager;

require_login();
permissions_manager::require_view_access();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/my_certificates.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cert_page_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('cert_page_title', 'local_tm_certification'));

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash');
echo html_writer::tag('h2', get_string('cert_page_title', 'local_tm_certification'));
echo html_writer::tag('p', get_string('cert_page_intro', 'local_tm_certification'), ['class' => 'text-muted']);
echo html_writer::div(get_string('cert_empty', 'local_tm_certification'), 'tm-cert-card');
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/tm_certification/index.php'),
        get_string('nav_dashboard', 'local_tm_certification'),
        ['class' => 'btn btn-secondary btn-sm']
    ),
    'mt-3'
);
echo html_writer::end_div();
echo $OUTPUT->footer();
