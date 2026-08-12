<?php
/**
 * List certification sessions (admin / approve).
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

$canmanage = permissions_manager::user_can_manage();
$canapprove = permissions_manager::user_can_approve();
if (!$canmanage && !$canapprove) {
    permissions_manager::require_view_access();
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$action = optional_param('action', '', PARAM_ALPHA);
$sessionid = optional_param('id', 0, PARAM_INT);
if ($sessionid && confirm_sesskey()) {
    if ($action === 'end' || $action === 'close') {
        session_manager::end_session($sessionid);
        redirect($PAGE->url, get_string('session_ended', 'local_tm_certification'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'reopen' && $canmanage) {
        session_manager::reopen_session($sessionid);
        redirect($PAGE->url, get_string('session_reopened', 'local_tm_certification'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'delete' && $canmanage) {
        session_manager::delete_session($sessionid);
        redirect($PAGE->url, get_string('session_deleted', 'local_tm_certification'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
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
echo html_writer::tag('p', get_string('session_list_hint', 'local_tm_certification'), ['class' => 'text-muted']);

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
    $ended = session_manager::is_ended($s);
    $statuslabel = session_manager::status_label($s);
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
    if (!$ended) {
        $actions .= ' ' . html_writer::link(
            new moodle_url('/local/tm_certification/admin/sessions.php', [
                'action' => 'end',
                'id' => $s->id,
                'sesskey' => sesskey(),
            ]),
            get_string('session_end', 'local_tm_certification'),
            ['class' => 'btn btn-sm btn-warning']
        );
    } else if ($canmanage) {
        $actions .= ' ' . html_writer::link(
            new moodle_url('/local/tm_certification/admin/sessions.php', [
                'action' => 'reopen',
                'id' => $s->id,
                'sesskey' => sesskey(),
            ]),
            get_string('session_reopen', 'local_tm_certification'),
            ['class' => 'btn btn-sm btn-primary']
        );
    }
    if ($canmanage) {
        $actions .= ' ' . html_writer::link(
            new moodle_url('/local/tm_certification/admin/sessions.php', [
                'action' => 'delete',
                'id' => $s->id,
                'sesskey' => sesskey(),
            ]),
            get_string('session_delete', 'local_tm_certification'),
            [
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'return confirm(' . json_encode(get_string('session_delete_confirm', 'local_tm_certification')) . ');',
            ]
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
