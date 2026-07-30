<?php
/**
 * List certification sessions.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');

use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/sessions.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('nav_sessions', 'local_tm_certification'));
$PAGE->set_heading(get_string('nav_sessions', 'local_tm_certification'));

if (!permissions_manager::user_can_manage() && !permissions_manager::user_can_approve()) {
    permissions_manager::require_view_access();
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$action = optional_param('action', '', PARAM_ALPHA);
$sessionid = optional_param('id', 0, PARAM_INT);
if ($action === 'close' && $sessionid && confirm_sesskey()) {
    session_manager::close_session($sessionid);
    redirect($PAGE->url, get_string('session_closed', 'local_tm_certification'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$sessions = session_manager::get_sessions();
$levels = session_manager::level_options();

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::tag('h2', get_string('nav_sessions', 'local_tm_certification'))
    . html_writer::link(
        new moodle_url('/local/tm_certification/admin/edit_session.php'),
        get_string('session_create', 'local_tm_certification'),
        ['class' => 'btn btn-primary']
    ),
    'tm-cert-page-header d-flex justify-content-between align-items-center flex-wrap mb-3'
);

$table = new html_table();
$table->head = [
    get_string('session_name', 'local_tm_certification'),
    get_string('session_level', 'local_tm_certification'),
    get_string('session_time', 'local_tm_certification'),
    get_string('session_venue', 'local_tm_certification'),
    get_string('session_enrol_count', 'local_tm_certification'),
    get_string('session_status', 'local_tm_certification'),
    get_string('actions'),
];
$table->data = [];

foreach ($sessions as $s) {
    $statuslabel = ((int)$s->status === session_manager::STATUS_OPEN)
        ? get_string('session_status_open', 'local_tm_certification')
        : get_string('session_status_closed', 'local_tm_certification');
    $time = userdate((int)$s->starttime, get_string('strftimedatetimeshort', 'langconfig'))
        . ' - '
        . userdate((int)$s->endtime, get_string('strftimetime', 'langconfig'));
    $actions = html_writer::link(
        new moodle_url('/local/tm_certification/admin/edit_session.php', ['id' => $s->id]),
        get_string('edit'),
        ['class' => 'btn btn-sm btn-secondary']
    );
    $actions .= ' ' . html_writer::link(
        new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $s->id]),
        get_string('session_roster', 'local_tm_certification'),
        ['class' => 'btn btn-sm btn-secondary']
    );
    if ((int)$s->status === session_manager::STATUS_OPEN) {
        $actions .= ' ' . html_writer::link(
            new moodle_url('/local/tm_certification/admin/sessions.php', [
                'action' => 'close',
                'id' => $s->id,
                'sesskey' => sesskey(),
            ]),
            get_string('session_close', 'local_tm_certification'),
            ['class' => 'btn btn-sm btn-warning']
        );
    }
    $venue = (string)$s->venue;
    if (!empty($s->venuename)) {
        $code = trim((string)($s->venuecode ?? ''));
        $venue = $code !== '' ? ($code . ' - ' . (string)$s->venuename) : (string)$s->venuename;
    }
    $table->data[] = [
        format_string($s->name),
        $levels[$s->certlevel] ?? s($s->certlevel),
        $time,
        s($venue),
        session_manager::count_enrols((int)$s->id),
        $statuslabel,
        $actions,
    ];
}

if (empty($sessions)) {
    echo html_writer::div(get_string('session_empty', 'local_tm_certification'), 'alert alert-info');
} else {
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
