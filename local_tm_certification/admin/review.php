<?php
/**
 * Review course-opening applications.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/application_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/catalog_manager.php');

use local_tm_certification\application_manager;
use local_tm_certification\catalog_manager;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
if (!permissions_manager::user_can_approve() && !permissions_manager::user_can_manage()) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$canmanage = permissions_manager::user_can_manage();
$action = optional_param('action', '', PARAM_ALPHA);
$appid = optional_param('id', 0, PARAM_INT);
$note = optional_param('managernote', '', PARAM_TEXT);
$filterstatus = optional_param('status', application_manager::STATUS_PENDING, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/review.php', ['status' => $filterstatus]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('app_review_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('app_review_title', 'local_tm_certification'));

if ($action === 'approve' && $appid && $confirm) {
    require_sesskey();
    try {
        $sessionid = application_manager::approve($appid, $note);
        require_once(__DIR__ . '/../classes/assessment_manager.php');
        $attcmids = \local_tm_certification\assessment_manager::attendance_cmids_for_level(
            (string)application_manager::get_application($appid)->certlevel
        );
        $msg = get_string('app_approve_success', 'local_tm_certification', $sessionid);
        if (empty($attcmids)) {
            $msg .= ' ' . get_string('app_approve_attendance_skipped', 'local_tm_certification');
        }
        redirect(
            new moodle_url('/local/tm_certification/admin/review.php', ['status' => application_manager::STATUS_PENDING]),
            $msg,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (moodle_exception $e) {
        redirect(
            new moodle_url('/local/tm_certification/admin/review.php'),
            $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

if ($action === 'reject' && $appid && $confirm) {
    require_sesskey();
    try {
        application_manager::reject($appid, $note);
        redirect(
            new moodle_url('/local/tm_certification/admin/review.php', ['status' => application_manager::STATUS_PENDING]),
            get_string('app_reject_success', 'local_tm_certification'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (moodle_exception $e) {
        redirect(
            new moodle_url('/local/tm_certification/admin/review.php'),
            $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

if ($action === 'delete' && $appid && $canmanage) {
    require_sesskey();
    try {
        application_manager::soft_delete($appid);
        redirect(
            new moodle_url('/local/tm_certification/admin/review.php', ['status' => $filterstatus]),
            get_string('app_deleted', 'local_tm_certification'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (moodle_exception $e) {
        redirect(
            new moodle_url('/local/tm_certification/admin/review.php', ['status' => $filterstatus]),
            $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

$levels = session_manager::level_options();
$statusopts = application_manager::status_options();
$apps = application_manager::get_applications([
    'status' => $filterstatus,
    'include_deleted' => true,
]);

echo $OUTPUT->header();

echo html_writer::start_div('mb-3');
foreach ($statusopts as $st => $label) {
    $url = new moodle_url('/local/tm_certification/admin/review.php', ['status' => $st]);
    $class = ((int)$filterstatus === (int)$st) ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm';
    echo html_writer::link($url, $label, ['class' => $class]) . ' ';
}
echo html_writer::end_div();

if ($action === 'approve' || $action === 'reject') {
    if (!$appid) {
        echo $OUTPUT->notification(get_string('error_app_not_found', 'local_tm_certification'), 'error');
    } else {
        $app = application_manager::get_application($appid);
        $venue = '';
        if (!empty($app->venuecode) || !empty($app->venuename)) {
            $venue = trim((string)($app->venuecode ?? '') . ' ' . (string)($app->venuename ?? ''));
        }
        $confirmurl = new moodle_url('/local/tm_certification/admin/review.php', [
            'action' => $action,
            'id' => $appid,
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::tag('h3', get_string('app_confirm_' . $action, 'local_tm_certification'));
        echo html_writer::tag('p',
            ($levels[$app->certlevel] ?? $app->certlevel) . ' · '
            . userdate((int)$app->starttime, get_string('strftimedatetimeshort', 'langconfig'))
            . ($venue !== '' ? ' · ' . s($venue) : '')
        );
        echo html_writer::tag('p',
            get_string('app_col_applicant', 'local_tm_certification') . '：' . fullname($app->applicant)
            . ' · '
            . get_string('session_instructor', 'local_tm_certification') . '：' . fullname($app->instructor)
        );
        $applicantnote = trim((string)($app->applicantnote ?? ''));
        if ($applicantnote !== '') {
            echo html_writer::div(
                html_writer::tag('strong', get_string('app_applicant_note', 'local_tm_certification') . '：')
                . ' ' . format_text($applicantnote, FORMAT_PLAIN),
                'alert alert-info'
            );
        } else {
            echo html_writer::div(get_string('app_note_empty', 'local_tm_certification'), 'text-muted mb-2');
        }

        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $confirmurl->out(false)]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => $action]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $appid]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'confirm', 'value' => 1]);
        echo html_writer::tag('label', get_string('app_manager_note', 'local_tm_certification'));
        echo html_writer::tag('textarea', s($note), ['name' => 'managernote', 'rows' => 3, 'class' => 'form-control mb-2']);
        $btnclass = $action === 'approve' ? 'btn btn-success' : 'btn btn-danger';
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('app_confirm_' . $action, 'local_tm_certification'),
            'class' => $btnclass,
        ]);
        echo ' ';
        echo html_writer::link(
            new moodle_url('/local/tm_certification/admin/review.php', ['status' => $filterstatus]),
            get_string('cancel'),
            ['class' => 'btn btn-secondary']
        );
        echo html_writer::end_tag('form');
        echo $OUTPUT->footer();
        exit;
    }
}

if (empty($apps)) {
    echo html_writer::tag('p', get_string('app_empty_list', 'local_tm_certification'), ['class' => 'text-muted']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('app_col_id', 'local_tm_certification'),
        get_string('app_col_applicant', 'local_tm_certification'),
        get_string('session_instructor', 'local_tm_certification'),
        get_string('session_level', 'local_tm_certification'),
        get_string('session_venue', 'local_tm_certification'),
        get_string('session_start', 'local_tm_certification'),
        get_string('app_applicant_note', 'local_tm_certification'),
        get_string('app_manager_note', 'local_tm_certification'),
        get_string('app_status', 'local_tm_certification'),
        get_string('actions'),
    ];
    foreach ($apps as $app) {
        $venue = '';
        if (!empty($app->venuecode) || !empty($app->venuename)) {
            $venue = trim((string)($app->venuecode ?? '') . ' ' . (string)($app->venuename ?? ''));
        }
        $applicantnote = trim((string)($app->applicantnote ?? ''));
        $managernote = trim((string)($app->managernote ?? ''));
        $deleted = application_manager::is_deleted($app);
        $actions = '';
        if ((int)$app->status === application_manager::STATUS_PENDING) {
            $actions .= html_writer::link(
                new moodle_url('/local/tm_certification/admin/review.php', [
                    'action' => 'approve',
                    'id' => $app->id,
                    'status' => $filterstatus,
                ]),
                get_string('app_action_approve', 'local_tm_certification'),
                ['class' => 'btn btn-sm btn-success']
            ) . ' ';
            $actions .= html_writer::link(
                new moodle_url('/local/tm_certification/admin/review.php', [
                    'action' => 'reject',
                    'id' => $app->id,
                    'status' => $filterstatus,
                ]),
                get_string('app_action_reject', 'local_tm_certification'),
                ['class' => 'btn btn-sm btn-danger']
            );
        } else {
            if (!empty($app->sessionid)) {
                $actions .= html_writer::link(
                    new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $app->sessionid]),
                    get_string('dash_action_roster', 'local_tm_certification'),
                    ['class' => 'btn btn-sm btn-secondary']
                ) . ' ';
            }
            if ($canmanage && !$deleted) {
                $actions .= html_writer::link(
                    new moodle_url('/local/tm_certification/admin/review.php', [
                        'action' => 'delete',
                        'id' => $app->id,
                        'status' => $filterstatus,
                        'sesskey' => sesskey(),
                    ]),
                    get_string('app_action_delete', 'local_tm_certification'),
                    [
                        'class' => 'btn btn-sm btn-danger',
                        'onclick' => 'return confirm(' . json_encode(get_string('app_delete_confirm', 'local_tm_certification')) . ');',
                    ]
                );
            }
        }
        $statuslabel = $statusopts[(int)$app->status] ?? (string)$app->status;
        if ($deleted) {
            $statuslabel .= ' · ' . get_string('app_status_deleted', 'local_tm_certification');
        }
        $table->data[] = [
            (int)$app->id,
            fullname($app->applicant),
            fullname($app->instructor),
            $levels[$app->certlevel] ?? $app->certlevel,
            s($venue),
            userdate((int)$app->starttime, get_string('strftimedatetimeshort', 'langconfig')),
            $applicantnote !== '' ? format_text($applicantnote, FORMAT_PLAIN) : '—',
            $managernote !== '' ? format_text($managernote, FORMAT_PLAIN) : '—',
            $statuslabel,
            $actions,
        ];
    }
    echo html_writer::table($table);
}

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/tm_certification/index.php'),
        get_string('nav_dashboard', 'local_tm_certification'),
        ['class' => 'btn btn-secondary']
    ),
    'mt-3'
);

echo $OUTPUT->footer();
