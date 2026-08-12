<?php
/**
 * Add examinees to a session (single with preview + CSV batch).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/enrolment_manager.php');
require_once(__DIR__ . '/../classes/batch_helper.php');
require_once(__DIR__ . '/../classes/candidate_manager.php');
require_once(__DIR__ . '/../classes/dashboard_helper.php');
require_once(__DIR__ . '/../classes/prerequisite_helper.php');

use local_tm_certification\batch_helper;
use local_tm_certification\candidate_manager;
use local_tm_certification\dashboard_helper;
use local_tm_certification\permissions_manager;
use local_tm_certification\prerequisite_helper;
use local_tm_certification\session_manager;

require_login();
$sessionid = required_param('sessionid', PARAM_INT);
$session = session_manager::get_session($sessionid);
$effectivecourseid = session_manager::resolve_courseid($session, true);
$canaddenrol = session_manager::can_add_examinees($session);

$canmanage = permissions_manager::user_can_manage() || permissions_manager::user_can_approve();
$caninstruct = permissions_manager::user_can_instruct()
    && ((int)$session->instructorid === (int)$USER->id || (int)$session->instructorid === 0);
if (!$canmanage && !$caninstruct) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$rosterurl = new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]);
$enrolurl = new moodle_url('/local/tm_certification/admin/enrol.php', ['sessionid' => $sessionid]);

if (!$canaddenrol) {
    redirect($rosterurl, get_string('error_session_ended_no_enrol', 'local_tm_certification'),
        null, \core\output\notification::NOTIFY_ERROR);
}

$mode = optional_param('mode', 'single', PARAM_ALPHA);
if ($mode !== 'batch') {
    $mode = 'single';
}
$batchaction = optional_param('batchaction', '', PARAM_ALPHA);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/enrol.php', [
    'sessionid' => $sessionid,
    'mode' => $mode,
]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('enrol_add_heading', 'local_tm_certification'));
$PAGE->set_heading(get_string('enrol_add_heading', 'local_tm_certification'));

/**
 * Store preview rows and return token.
 *
 * @param array $preview from batch_helper::preview_rows
 */
$store_batch_preview = static function(array $preview, string $sourcemode) use ($sessionid): string {
    global $SESSION;
    $batchtoken = random_string(16);
    $SESSION->{'tm_cert_batch_' . $batchtoken} = [
        'sessionid' => $sessionid,
        'items' => $preview['items'],
        'mode' => $sourcemode,
        'time' => time(),
    ];
    return $batchtoken;
};

// Confirm commit of previewed single / CSV batch.
if ($batchaction === 'confirm' && confirm_sesskey()) {
    $token = required_param('batchtoken', PARAM_ALPHANUMEXT);
    $key = 'tm_cert_batch_' . $token;
    if (empty($SESSION->$key) || (int)($SESSION->$key['sessionid'] ?? 0) !== $sessionid) {
        redirect($enrolurl, get_string('error_batch_preview_expired', 'local_tm_certification'), null,
            \core\output\notification::NOTIFY_ERROR);
    }
    $items = $SESSION->$key['items'];
    $sourcemode = (string)($SESSION->$key['mode'] ?? 'batch');
    unset($SESSION->$key);
    $result = batch_helper::commit_to_session($sessionid, $items, (int)$USER->id);

    if ($sourcemode === 'single' && (int)$result['ok'] === 1) {
        $detail = null;
        foreach ($result['details'] as $d) {
            if (!empty($d['ok'])) {
                $detail = $d;
                break;
            }
        }
        $email = (string)($detail['email'] ?? '');
        $msg = !empty($detail['created'])
            ? get_string('enrol_added_created', 'local_tm_certification', $email)
            : get_string('enrol_added_linked', 'local_tm_certification', $email);
        if (!empty($detail['candidateid'])) {
            $msg .= ' ' . get_string('enrol_candidate_id', 'local_tm_certification', $detail['candidateid']);
        }
        if (!empty($detail['initial_password'])) {
            $msg .= ' ' . get_string('enrol_initial_password', 'local_tm_certification', $detail['initial_password']);
        }
        redirect($rosterurl, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
    }

    $msg = get_string('batch_commit_result', 'local_tm_certification', (object)[
        'ok' => $result['ok'],
        'failed' => $result['failed'],
    ]);
    if (!empty($result['skipped'])) {
        $msg .= ' ' . get_string('batch_commit_skipped', 'local_tm_certification', $result['skipped']);
    }
    if ((int)$result['ok'] > 0) {
        $msg .= ' ' . get_string('batch_commit_passwords_note', 'local_tm_certification');
        foreach ($result['details'] as $detail) {
            if (!empty($detail['ok']) && !empty($detail['initial_password'])) {
                $msg .= ' ' . get_string('enrol_initial_password', 'local_tm_certification', $detail['initial_password']);
                if ((int)$result['ok'] > 1) {
                    $msg .= ' ' . get_string('batch_commit_password_more', 'local_tm_certification',
                        (int)$result['ok'] - 1);
                }
                break;
            }
        }
    }
    if (!empty($result['details'])) {
        foreach ($result['details'] as $detail) {
            if (empty($detail['ok']) && !empty($detail['error'])) {
                $msg .= ' ' . get_string('batch_commit_first_error', 'local_tm_certification', (object)[
                    'email' => (string)($detail['email'] ?? ''),
                    'error' => (string)$detail['error'],
                ]);
                break;
            }
        }
    }
    redirect($rosterurl, $msg, null, ($result['failed'] > 0 || $result['ok'] === 0)
        ? \core\output\notification::NOTIFY_WARNING
        : \core\output\notification::NOTIFY_SUCCESS);
}

if ($batchaction === 'cancelpreview' && confirm_sesskey()) {
    $token = required_param('batchtoken', PARAM_ALPHANUMEXT);
    $key = 'tm_cert_batch_' . $token;
    $backmode = 'batch';
    if (!empty($SESSION->$key['mode']) && in_array($SESSION->$key['mode'], ['single', 'batch'], true)) {
        $backmode = $SESSION->$key['mode'];
    }
    unset($SESSION->$key);
    redirect(new moodle_url('/local/tm_certification/admin/enrol.php', [
        'sessionid' => $sessionid,
        'mode' => $backmode,
    ]));
}

class local_tm_certification_add_examinee_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'sessionid');
        $mform->setType('sessionid', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'single');
        $mform->setType('mode', PARAM_ALPHA);
        $mform->addElement('hidden', 'batchaction', 'preview');
        $mform->setType('batchaction', PARAM_ALPHA);

        $mform->addElement('text', 'email', get_string('email'), ['size' => 40]);
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required', null, 'client');

        $mform->addElement('text', 'firstname', get_string('firstname'), ['size' => 20]);
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', null, 'required', null, 'client');

        $mform->addElement('text', 'lastname', get_string('lastname'), ['size' => 20]);
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', null, 'required', null, 'client');

        $mform->addElement('text', 'institution', get_string('institution'), ['size' => 40]);
        $mform->setType('institution', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('batch_preview', 'local_tm_certification'));
    }
}

class local_tm_certification_batch_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'sessionid');
        $mform->setType('sessionid', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'batch');
        $mform->setType('mode', PARAM_ALPHA);
        $mform->addElement('hidden', 'batchaction', 'preview');
        $mform->setType('batchaction', PARAM_ALPHA);

        $mform->addElement('filepicker', 'batchcsv', get_string('batch_upload_file', 'local_tm_certification'), null,
            ['accepted_types' => ['.csv', '.txt']]);
        $mform->addRule('batchcsv', null, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('batch_preview', 'local_tm_certification'));
    }
}

$mform = new local_tm_certification_add_examinee_form($enrolurl);
$mform->set_data(['sessionid' => $sessionid, 'mode' => 'single', 'batchaction' => 'preview']);

$batchform = new local_tm_certification_batch_form(new moodle_url('/local/tm_certification/admin/enrol.php', [
    'sessionid' => $sessionid,
    'mode' => 'batch',
]));
$batchform->set_data(['sessionid' => $sessionid, 'mode' => 'batch', 'batchaction' => 'preview']);

$preview = null;
$batchtoken = '';
$previewmode = $mode;

if ($data = $mform->get_data()) {
    $mode = 'single';
    $previewmode = 'single';
    $row = [
        'email' => \core_text::strtolower(trim((string)$data->email)),
        'firstname' => trim((string)$data->firstname),
        'lastname' => trim((string)$data->lastname),
        'institution' => trim((string)($data->institution ?? '')),
    ];
    if ($row['email'] === '' || !validate_email($row['email'])) {
        \core\notification::error(get_string('error_batch_email_invalid', 'local_tm_certification'));
    } else if ($row['firstname'] === '' || $row['lastname'] === '') {
        \core\notification::error(get_string('error_batch_name_required', 'local_tm_certification'));
    } else {
        $preview = batch_helper::preview_rows([$row], (string)$session->certlevel);
        $batchtoken = $store_batch_preview($preview, 'single');
        $mform->set_data([
            'sessionid' => $sessionid,
            'mode' => 'single',
            'batchaction' => 'preview',
            'email' => $row['email'],
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'institution' => $row['institution'],
        ]);
    }
}

if ($batchdata = $batchform->get_data()) {
    $mode = 'batch';
    $previewmode = 'batch';
    $content = $batchform->get_file_content('batchcsv');
    if ($content === false || $content === '') {
        \core\notification::error(get_string('error_batch_csv_empty', 'local_tm_certification'));
    } else {
        $parsed = batch_helper::parse_csv_string($content);
        foreach ($parsed['errors'] as $err) {
            \core\notification::warning($err);
        }
        if (!empty($parsed['rows'])) {
            $preview = batch_helper::preview_rows($parsed['rows'], (string)$session->certlevel);
            $batchtoken = $store_batch_preview($preview, 'batch');
        } else if (empty($parsed['errors'])) {
            \core\notification::error(get_string('error_batch_csv_empty', 'local_tm_certification'));
        }
    }
}

$scenariolabels = [
    candidate_manager::SCENARIO_NEW => get_string('batch_scenario_new', 'local_tm_certification'),
    candidate_manager::SCENARIO_ISSUE_ID => get_string('batch_scenario_issue_id', 'local_tm_certification'),
    candidate_manager::SCENARIO_KEEP_ID => get_string('batch_scenario_keep_id', 'local_tm_certification'),
    prerequisite_helper::SCENARIO_PREREQ_OK => get_string('batch_scenario_prereq_ok', 'local_tm_certification'),
    prerequisite_helper::SCENARIO_PREREQ_FAIL => get_string('batch_scenario_prereq_fail', 'local_tm_certification'),
];

$needsprereq = prerequisite_helper::requires_prerequisite((string)$session->certlevel);
$count = session_manager::count_enrols($sessionid);

/**
 * Render shared preview summary + table for single / CSV batch.
 *
 * @param array $preview
 */
$render_batch_preview = static function(array $preview, string $batchtoken, string $previewmode) use (
    $sessionid,
    $needsprereq,
    $scenariolabels
): void {
    $eligiblecount = (int)($preview['eligible_count'] ?? $preview['counts']['total']);
    $blockedcount = (int)($preview['blocked_count'] ?? 0);
    echo html_writer::start_div('tm-cert-preview-box');
    echo html_writer::start_div('tm-cert-ph');
    echo html_writer::tag('h3', get_string('batch_preview_title', 'local_tm_certification'));
    echo html_writer::start_div('tm-cert-ops');
    if ($eligiblecount > 0) {
        echo html_writer::link(
            new moodle_url('/local/tm_certification/admin/enrol.php', [
                'sessionid' => $sessionid,
                'mode' => $previewmode,
                'batchaction' => 'confirm',
                'batchtoken' => $batchtoken,
                'sesskey' => sesskey(),
            ]),
            get_string('batch_confirm_commit', 'local_tm_certification'),
            ['class' => 'btn btn-sm tm-cert-btn-accent']
        );
    }
    echo html_writer::link(
        new moodle_url('/local/tm_certification/admin/enrol.php', [
            'sessionid' => $sessionid,
            'mode' => $previewmode,
            'batchaction' => 'cancelpreview',
            'batchtoken' => $batchtoken,
            'sesskey' => sesskey(),
        ]),
        get_string('cancel'),
        ['class' => 'btn btn-sm btn-secondary']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::div(get_string('batch_password_reset_hint', 'local_tm_certification'), 'tm-cert-hint-line');

    if ($needsprereq && $eligiblecount === 0) {
        echo html_writer::div(get_string('prereq_none_eligible', 'local_tm_certification'), 'tm-cert-alert-slim');
    }

    echo html_writer::start_div('tm-cert-preview-sum');
    echo dashboard_helper::render_pill([
        'label' => get_string('batch_preview_total', 'local_tm_certification', $preview['counts']['total']),
        'mod' => '',
    ]);
    if ($needsprereq) {
        echo dashboard_helper::render_pill([
            'label' => get_string('batch_preview_eligible', 'local_tm_certification', $eligiblecount),
            'mod' => '',
        ]);
        echo dashboard_helper::render_pill([
            'label' => get_string('batch_preview_blocked', 'local_tm_certification', $blockedcount),
            'mod' => 'dim',
        ]);
    } else {
        echo dashboard_helper::render_pill([
            'label' => get_string('batch_preview_new', 'local_tm_certification',
                $preview['counts'][candidate_manager::SCENARIO_NEW]),
            'mod' => '',
        ]);
        echo dashboard_helper::render_pill([
            'label' => get_string('batch_preview_keep', 'local_tm_certification',
                $preview['counts'][candidate_manager::SCENARIO_KEEP_ID]),
            'mod' => '',
        ]);
    }
    echo html_writer::end_div();

    echo html_writer::start_tag('table', ['class' => 'tm-cert-data']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('fullname'))
        . html_writer::tag('th', get_string('email'))
        . html_writer::tag('th', get_string('batch_scenario', 'local_tm_certification'))
        . html_writer::tag('th', get_string('candidate_id', 'local_tm_certification'))
        . ($needsprereq ? html_writer::tag('th', get_string('prereq_col_reason', 'local_tm_certification')) : '')
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($preview['items'] as $item) {
        $rowclass = empty($item['eligible']) && $needsprereq ? 'tm-cert-row-blocked' : '';
        echo html_writer::start_tag('tr', $rowclass !== '' ? ['class' => $rowclass] : []);
        echo html_writer::tag('td', s($item['lastname'] . $item['firstname']));
        echo html_writer::tag('td', s($item['email']), ['class' => 'tm-cert-muted']);
        echo html_writer::tag('td', $scenariolabels[$item['scenario']] ?? $item['scenario']);
        echo html_writer::tag('td',
            s((string)($item['candidateid'] ?? ($needsprereq
                ? '—'
                : get_string('batch_candidate_pending', 'local_tm_certification')))),
            ['class' => empty($item['candidateid']) ? 'tm-cert-muted' : 'tm-cert-id']
        );
        if ($needsprereq) {
            $reason = !empty($item['reasons'])
                ? prerequisite_helper::format_reasons($item['reasons'])
                : '—';
            echo html_writer::tag('td', s($reason), ['class' => 'tm-cert-muted']);
        }
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
};

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash');

echo html_writer::start_div('tm-cert-head');
echo html_writer::start_div();
echo html_writer::tag('h2', get_string('enrol_add_heading', 'local_tm_certification'));
echo html_writer::start_div('tm-cert-facts');
echo dashboard_helper::render_pill(['label' => format_string($session->name), 'mod' => '']);
echo dashboard_helper::render_pill([
    'label' => get_string('dash_session_people', 'local_tm_certification', $count),
    'mod' => '',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('tm-cert-head-actions');
echo html_writer::link($rosterurl, get_string('roster_back_list', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();

if ($effectivecourseid === 0) {
    echo html_writer::div(get_string('enrol_no_course_hint_short', 'local_tm_certification'), 'tm-cert-alert-slim');
}

echo html_writer::start_div('tm-cert-mode-tabs');
echo html_writer::link(
    new moodle_url('/local/tm_certification/admin/enrol.php', ['sessionid' => $sessionid, 'mode' => 'single']),
    get_string('enrol_mode_single', 'local_tm_certification'),
    ['class' => 'tm-cert-mode-tab' . ($mode === 'single' ? ' active' : '')]
);
echo html_writer::link(
    new moodle_url('/local/tm_certification/admin/enrol.php', ['sessionid' => $sessionid, 'mode' => 'batch']),
    get_string('enrol_mode_batch', 'local_tm_certification'),
    ['class' => 'tm-cert-mode-tab' . ($mode === 'batch' ? ' active' : '')]
);
echo html_writer::end_div();

echo html_writer::start_div('tm-cert-add-panel');
if ($mode === 'single') {
    if ($needsprereq) {
        echo html_writer::tag('p', get_string('prereq_single_hint', 'local_tm_certification'),
            ['class' => 'tm-cert-hint-line']);
    }
    echo html_writer::tag('p', get_string('enrol_password_hint', 'local_tm_certification'),
        ['class' => 'tm-cert-hint-line']);
    echo html_writer::tag('p', get_string('enrol_single_preview_hint', 'local_tm_certification'),
        ['class' => 'tm-cert-hint-line']);
    $mform->display();
    if ($preview && $previewmode === 'single') {
        $render_batch_preview($preview, $batchtoken, 'single');
    }
} else {
    echo html_writer::tag('p', $needsprereq
        ? get_string('batch_upload_intro_prereq', 'local_tm_certification')
        : get_string('batch_upload_intro_short', 'local_tm_certification'),
        ['class' => 'tm-cert-hint-line']);
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/tm_certification/batch_template.php'),
            get_string('batch_download_template', 'local_tm_certification'),
            ['class' => 'btn btn-sm btn-secondary']
        ),
        'mb-3'
    );
    $batchform->display();
    if ($preview && $previewmode === 'batch') {
        $render_batch_preview($preview, $batchtoken, 'batch');
    }
}
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
