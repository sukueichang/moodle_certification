<?php
/**
 * Written & practical setup: components with per-item attendance.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/assessment_manager.php');
require_once(__DIR__ . '/../classes/dashboard_helper.php');
require_once(__DIR__ . '/../classes/attendance_helper.php');

use local_tm_certification\assessment_manager;
use local_tm_certification\dashboard_helper;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
if (!permissions_manager::user_can_manage()) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/assess_components.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('setting_assess_heading', 'local_tm_certification'));
$PAGE->set_heading(get_string('setting_assess_heading', 'local_tm_certification'));

assessment_manager::seed_blueprints_from_legacy_config();
assessment_manager::migrate_level_attendance_into_manuals();

$deactivateid = optional_param('deactivateid', 0, PARAM_INT);
if ($deactivateid > 0 && confirm_sesskey()) {
    assessment_manager::deactivate_component($deactivateid);
    redirect($PAGE->url, get_string('assess_component_deactivated', 'local_tm_certification'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$reseedsingle = optional_param('reseed', '', PARAM_ALPHANUMEXT);
if ($reseedsingle !== '' && confirm_sesskey()) {
    // Only seeds if that level has ZERO components — never wipes existing.
    if (array_key_exists($reseedsingle, session_manager::level_options())) {
        assessment_manager::ensure_blueprint($reseedsingle);
        assessment_manager::migrate_level_attendance_into_manuals();
        redirect($PAGE->url, get_string('assess_reseed_ok', 'local_tm_certification'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
}

class local_tm_certification_assess_comp_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('select', 'certlevel', get_string('session_level', 'local_tm_certification'),
            session_manager::level_options());
        $mform->setType('certlevel', PARAM_ALPHANUMEXT);

        $mform->addElement('text', 'code', get_string('assess_code', 'local_tm_certification'), ['size' => 20]);
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', null, 'required', null, 'client');
        $mform->addHelpButton('code', 'assess_code', 'local_tm_certification');

        $mform->addElement('select', 'type', get_string('assess_type', 'local_tm_certification'),
            assessment_manager::type_options());
        $mform->setType('type', PARAM_ALPHANUMEXT);

        $mform->addElement('text', 'label', get_string('assess_label', 'local_tm_certification'), ['size' => 40]);
        $mform->setType('label', PARAM_TEXT);
        $mform->addRule('label', null, 'required', null, 'client');

        $mform->addElement('text', 'passscore', get_string('assess_passscore', 'local_tm_certification'), ['size' => 10]);
        $mform->setType('passscore', PARAM_FLOAT);
        $mform->hideIf('passscore', 'type', 'eq', assessment_manager::TYPE_ATTENDANCE);
        $mform->addHelpButton('passscore', 'assess_passscore', 'local_tm_certification');

        $mform->addElement('text', 'sortorder', get_string('assess_sortorder', 'local_tm_certification'), ['size' => 5]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        $quizopts = [0 => get_string('setting_written_quiz_none', 'local_tm_certification')];
        $attopts = [0 => get_string('setting_attendance_none', 'local_tm_certification')];
        foreach (session_manager::level_options() as $level => $lab) {
            $courseid = (int)get_config('local_tm_certification', 'mapping_course_' . $level);
            if ($courseid > 0) {
                foreach (assessment_manager::quiz_options_for_course($courseid) as $cmid => $qlabel) {
                    $quizopts[$cmid] = $lab . ' — ' . $qlabel;
                }
                foreach (\local_tm_certification\attendance_helper::attendance_options_for_course($courseid) as $cmid => $alabel) {
                    $attopts[$cmid] = $lab . ' — ' . $alabel;
                }
            }
        }
        $mform->addElement('select', 'quizcmid', get_string('assess_quizcmid', 'local_tm_certification'), $quizopts);
        $mform->setType('quizcmid', PARAM_INT);
        $mform->hideIf('quizcmid', 'type', 'neq', assessment_manager::TYPE_QUIZ);

        $mform->addElement('select', 'attendancecmid', get_string('assess_attendancecmid', 'local_tm_certification'), $attopts);
        $mform->setType('attendancecmid', PARAM_INT);
        // Manual practical + rare read-only attendance type both link an attendance activity.
        $mform->hideIf('attendancecmid', 'type', 'eq', assessment_manager::TYPE_QUIZ);
        $mform->addHelpButton('attendancecmid', 'assess_attendancecmid', 'local_tm_certification');

        $mform->addElement('advcheckbox', 'active', get_string('assess_active', 'local_tm_certification'));
        $mform->setDefault('active', 1);

        $this->add_action_buttons(false, get_string('assess_save', 'local_tm_certification'));
    }
}

$editid = optional_param('id', 0, PARAM_INT);
$form = new local_tm_certification_assess_comp_form();
if ($editid > 0) {
    $comp = assessment_manager::get_component($editid);
    if ($comp) {
        $data = clone $comp;
        if ((string)$comp->type === assessment_manager::TYPE_ATTENDANCE
                || (string)$comp->type === assessment_manager::TYPE_MANUAL) {
            $data->attendancecmid = (int)$comp->quizcmid;
        }
        $form->set_data($data);
    }
} else {
    $form->set_data([
        'certlevel' => session_manager::LEVEL_BASIC,
        'type' => assessment_manager::TYPE_MANUAL,
        'active' => 1,
        'passscore' => 90,
        'sortorder' => 10,
    ]);
}

if ($data = $form->get_data()) {
    try {
        $cmid = 0;
        if ((string)$data->type === assessment_manager::TYPE_QUIZ) {
            $cmid = (int)$data->quizcmid;
        } else if ((string)$data->type === assessment_manager::TYPE_ATTENDANCE
                || (string)$data->type === assessment_manager::TYPE_MANUAL) {
            $cmid = (int)$data->attendancecmid;
        }
        assessment_manager::save_component([
            'certlevel' => $data->certlevel,
            'code' => $data->code,
            'type' => $data->type,
            'label' => $data->label,
            'passscore' => $data->passscore ?? 0,
            'sortorder' => (int)$data->sortorder,
            'quizcmid' => $cmid,
            'active' => !empty($data->active),
        ], (int)$data->id);
        redirect($PAGE->url, get_string('assess_component_saved', 'local_tm_certification'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (moodle_exception $e) {
        \core\notification::error($e->getMessage());
    }
}

$levels = session_manager::level_options();

echo $OUTPUT->header();
echo html_writer::div(get_string('assess_components_intro', 'local_tm_certification'), 'tm-cert-hint-info');
echo html_writer::div(get_string('assess_att_per_comp_hint', 'local_tm_certification'), 'tm-cert-hint-info mb-3');

echo html_writer::tag('h3', get_string('assess_section_components', 'local_tm_certification'));
echo html_writer::tag('h4', get_string('assess_add_edit', 'local_tm_certification'));
$form->display();

foreach ($levels as $level => $levellabel) {
    $rows = assessment_manager::get_components($level, null, false);
    echo html_writer::tag('h4', $levellabel);
    if (empty($rows)) {
        echo html_writer::div(get_string('assess_empty_level', 'local_tm_certification'));
        $seedurl = new moodle_url($PAGE->url, ['reseed' => $level, 'sesskey' => sesskey()]);
        echo html_writer::div(
            html_writer::link($seedurl, get_string('assess_reseed_level', 'local_tm_certification'),
                ['class' => 'btn btn-secondary btn-sm']),
            'mb-3'
        );
        continue;
    }
    $table = new html_table();
    $table->head = [
        get_string('assess_code', 'local_tm_certification'),
        get_string('assess_label', 'local_tm_certification'),
        get_string('assess_type', 'local_tm_certification'),
        get_string('assess_passscore', 'local_tm_certification'),
        get_string('assess_linked_activity', 'local_tm_certification'),
        get_string('assess_sortorder', 'local_tm_certification'),
        get_string('assess_active', 'local_tm_certification'),
        get_string('actions'),
    ];
    foreach ($rows as $row) {
        $typelabel = assessment_manager::type_options()[$row->type]
            ?? s($row->type);
        $ops = html_writer::link(
            new moodle_url($PAGE->url, ['id' => $row->id]),
            get_string('edit')
        );
        if ((int)$row->active === 1) {
            $ops .= ' | ' . html_writer::link(
                new moodle_url($PAGE->url, ['deactivateid' => $row->id, 'sesskey' => sesskey()]),
                get_string('assess_deactivate', 'local_tm_certification')
            );
        }
        $linkdisplay = '—';
        if ((int)$row->quizcmid > 0) {
            if ((string)$row->type === assessment_manager::TYPE_QUIZ) {
                $linkdisplay = get_string('assess_type_quiz', 'local_tm_certification')
                    . ' cmid ' . (int)$row->quizcmid;
            } else {
                $linkdisplay = get_string('assess_linked_attendance', 'local_tm_certification',
                    (int)$row->quizcmid);
            }
        } else if ((string)$row->type === assessment_manager::TYPE_MANUAL
                || (string)$row->type === assessment_manager::TYPE_ATTENDANCE) {
            $linkdisplay = get_string('setting_attendance_none', 'local_tm_certification');
        }
        $passdisplay = ($row->type === assessment_manager::TYPE_ATTENDANCE)
            ? get_string('assess_pass_via_present', 'local_tm_certification')
            : format_float((float)$row->passscore, 2);
        $table->data[] = [
            s($row->code),
            s($row->label),
            $typelabel,
            $passdisplay,
            $linkdisplay,
            (int)$row->sortorder,
            !empty($row->active)
                ? dashboard_helper::render_pill(['label' => get_string('assess_active', 'local_tm_certification'), 'mod' => 'ok'])
                : dashboard_helper::render_pill(['label' => get_string('assess_inactive', 'local_tm_certification'), 'mod' => 'dim']),
            $ops,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
