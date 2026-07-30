<?php
/**
 * Session roster + quick add examinee (email provision).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/enrolment_manager.php');

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
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sessionid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('session_roster', 'local_tm_certification'));
$PAGE->set_heading(format_string($session->name));

$cancelid = optional_param('cancelid', 0, PARAM_INT);
if ($cancelid && confirm_sesskey() && $canmanage) {
    enrolment_manager::cancel_enrolment($cancelid, false);
    redirect($PAGE->url, get_string('enrol_cancelled', 'local_tm_certification'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

class local_tm_certification_add_examinee_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'sessionid');
        $mform->setType('sessionid', PARAM_INT);

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

        $this->add_action_buttons(false, get_string('enrol_add_examinee', 'local_tm_certification'));
    }
}

$mform = new local_tm_certification_add_examinee_form();
$mform->set_data(['sessionid' => $sessionid]);

if ($data = $mform->get_data()) {
    try {
        $result = enrolment_manager::provision_and_add(
            $sessionid,
            $data->email,
            $data->firstname,
            $data->lastname,
            (string)($data->institution ?? ''),
            (int)$USER->id
        );
        $msg = $result['created']
            ? get_string('enrol_added_created', 'local_tm_certification', $result['email'])
            : get_string('enrol_added_linked', 'local_tm_certification', $result['email']);
        if ($result['created'] && $result['initial_password'] !== '') {
            $msg .= ' ' . get_string('enrol_initial_password', 'local_tm_certification', $result['initial_password']);
        }
        redirect($PAGE->url, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
    } catch (moodle_exception $e) {
        \core\notification::error($e->getMessage());
    }
}

$roster = enrolment_manager::get_session_roster($sessionid);

echo $OUTPUT->header();
echo html_writer::tag('p',
    get_string('session_level', 'local_tm_certification') . ': '
    . (session_manager::level_options()[$session->certlevel] ?? $session->certlevel)
    . ' · '
    . userdate((int)$session->starttime)
);

echo html_writer::tag('h3', get_string('enrol_add_heading', 'local_tm_certification'));
if ((int)$session->courseid === 0) {
    echo html_writer::div(get_string('enrol_no_course_hint', 'local_tm_certification'), 'alert alert-warning');
}
$mform->display();

echo html_writer::tag('h3', get_string('session_roster', 'local_tm_certification'));
$table = new html_table();
$table->head = [
    get_string('fullname'),
    get_string('email'),
    get_string('institution'),
    get_string('actions'),
];
foreach ($roster as $row) {
    $actions = '';
    if ($canmanage) {
        $actions = html_writer::link(
            new moodle_url('/local/tm_certification/admin/roster.php', [
                'sessionid' => $sessionid,
                'cancelid' => $row->id,
                'sesskey' => sesskey(),
            ]),
            get_string('enrol_cancel', 'local_tm_certification'),
            ['class' => 'btn btn-sm btn-danger']
        );
    }
    $table->data[] = [
        fullname($row),
        s($row->email),
        s((string)$row->institution),
        $actions,
    ];
}
if (empty($roster)) {
    echo html_writer::div(get_string('enrol_empty', 'local_tm_certification'), 'alert alert-info');
} else {
    echo html_writer::table($table);
}

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/tm_certification/admin/sessions.php'),
        get_string('nav_sessions', 'local_tm_certification'),
        ['class' => 'btn btn-link']
    ),
    'mt-3'
);
echo $OUTPUT->footer();
