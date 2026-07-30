<?php
/**
 * Venue maintenance.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/catalog_manager.php');

use local_tm_certification\catalog_manager;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
if (!permissions_manager::user_can_manage()) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/venues.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('nav_venues', 'local_tm_certification'));
$PAGE->set_heading(get_string('nav_venues', 'local_tm_certification'));

$deleteid = optional_param('deleteid', 0, PARAM_INT);
if ($deleteid > 0 && confirm_sesskey()) {
    catalog_manager::delete_venue($deleteid);
    redirect($PAGE->url, get_string('venue_saved', 'local_tm_certification'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

class local_tm_certification_venue_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'venuecode', get_string('venue_code', 'local_tm_certification'), ['size' => 20]);
        $mform->setType('venuecode', PARAM_ALPHANUMEXT);
        $mform->addRule('venuecode', null, 'required', null, 'client');

        $mform->addElement('text', 'name', get_string('venue_name', 'local_tm_certification'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('select', 'certlevel', get_string('session_level', 'local_tm_certification'),
            session_manager::level_options());
        $mform->setType('certlevel', PARAM_ALPHANUMEXT);

        $mform->addElement('date_selector', 'passedat', get_string('venue_passedat', 'local_tm_certification'), [
            'optional' => true,
        ]);

        $mform->addElement('advcheckbox', 'active', get_string('venue_active', 'local_tm_certification'));

        $this->add_action_buttons(false, get_string('venue_save', 'local_tm_certification'));
    }
}

$editid = optional_param('id', 0, PARAM_INT);
$form = new local_tm_certification_venue_form();
if ($editid > 0) {
    $venue = catalog_manager::get_venue($editid);
    if ($venue) {
        $form->set_data($venue);
    }
} else {
    $form->set_data(['certlevel' => session_manager::LEVEL_BASIC, 'active' => 1]);
}

if ($data = $form->get_data()) {
    try {
        catalog_manager::save_venue([
            'venuecode' => $data->venuecode,
            'name' => $data->name,
            'certlevel' => $data->certlevel,
            'passedat' => !empty($data->passedat) ? (int)$data->passedat : null,
            'active' => !empty($data->active),
        ], (int)$data->id);
        redirect($PAGE->url, get_string('venue_saved', 'local_tm_certification'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (moodle_exception $e) {
        \core\notification::error($e->getMessage());
    }
}

$venues = catalog_manager::get_venues(null, false);
$levels = session_manager::level_options();

echo $OUTPUT->header();
echo html_writer::tag('h3', get_string('venue_add', 'local_tm_certification'));
$form->display();

echo html_writer::tag('h3', get_string('nav_venues', 'local_tm_certification'));
$table = new html_table();
$table->head = [
    get_string('venue_code', 'local_tm_certification'),
    get_string('venue_name', 'local_tm_certification'),
    get_string('session_level', 'local_tm_certification'),
    get_string('venue_passedat', 'local_tm_certification'),
    get_string('status'),
    get_string('actions'),
];
$table->data = [];
foreach ($venues as $v) {
    $table->data[] = [
        s((string)$v->venuecode),
        s($v->name),
        s($levels[$v->certlevel] ?? $v->certlevel),
        !empty($v->passedat) ? userdate((int)$v->passedat, get_string('strftimedate', 'langconfig')) : '-',
        (int)$v->active ? get_string('yes') : get_string('no'),
        html_writer::link(new moodle_url($PAGE->url, ['id' => $v->id]), get_string('edit')) . ' | '
            . html_writer::link(new moodle_url($PAGE->url, ['deleteid' => $v->id, 'sesskey' => sesskey()]), get_string('delete')),
    ];
}
if (empty($table->data)) {
    echo html_writer::div(get_string('venue_empty', 'local_tm_certification'), 'alert alert-info');
} else {
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
