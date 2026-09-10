<?php
/**
 * Instructor level authorization + certificate validity settings.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/catalog_manager.php');
require_once(__DIR__ . '/../classes/course_access_helper.php');

use local_tm_certification\catalog_manager;
use local_tm_certification\course_access_helper;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
if (!permissions_manager::user_can_manage()) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/instructors.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('nav_instructor_levels', 'local_tm_certification'));
$PAGE->set_heading(get_string('nav_instructor_levels', 'local_tm_certification'));

$levels = session_manager::level_options();

if (optional_param('save_validity', 0, PARAM_INT) && confirm_sesskey()) {
    $extend = optional_param('instructor_extend_by_teaching', 0, PARAM_INT) ? 1 : 0;
    set_config('instructor_extend_by_teaching', $extend, 'local_tm_certification');
    foreach ($levels as $lv => $label) {
        $months = optional_param('validity_months_' . $lv, 12, PARAM_INT);
        if ($months < 1) {
            $months = 12;
        }
        set_config('instructor_validity_months_' . $lv, $months, 'local_tm_certification');
    }
    redirect($PAGE->url, get_string('instructor_validity_saved', 'local_tm_certification'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

if (data_submitted() && confirm_sesskey() && !optional_param('save_validity', 0, PARAM_INT)) {
    $userid = required_param('userid', PARAM_INT);
    $selected = optional_param_array('levels', [], PARAM_ALPHANUMEXT);
    $certifiedraw = trim(optional_param('certifiedat', '', PARAM_TEXT));
    $renewraw = trim(optional_param('renewedat', '', PARAM_TEXT));
    $certifiedat = $certifiedraw === '' ? null : strtotime($certifiedraw . ' 00:00:00');
    $renewedat = $renewraw === '' ? null : strtotime($renewraw . ' 00:00:00');
    if ($certifiedat === false || $renewedat === false) {
        throw new moodle_exception('error_instructor_cert_date_invalid', 'local_tm_certification');
    }
    foreach ($levels as $lv => $label) {
        $active = in_array($lv, $selected, true);
        catalog_manager::set_instructor_qualification($userid, $lv, $active);
    }
    catalog_manager::save_instructor_cert_profile($userid, $certifiedat, $renewedat);
    // Open all mapped level courses + instructor zone while instruct identity holds.
    course_access_helper::sync_instructor_access($userid);
    redirect($PAGE->url, get_string('instructor_levels_saved', 'local_tm_certification'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$users = catalog_manager::get_instructor_users_from_mapping(500);
$matrix = catalog_manager::instructor_level_matrix();
$profiles = catalog_manager::get_instructor_cert_profiles(array_map(static function(\stdClass $u): int {
    return (int)$u->id;
}, $users));

$extendteaching = catalog_manager::instructor_extend_by_teaching();

echo $OUTPUT->header();

echo html_writer::tag('h3', get_string('setting_instr_validity_heading', 'local_tm_certification'));
echo html_writer::div(get_string('setting_instr_validity_desc', 'local_tm_certification'), 'text-muted mb-3');
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $PAGE->url->out(false),
    'class' => 'mform mb-4',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'save_validity', 'value' => 1]);

echo html_writer::start_div('form-group form-check mb-3');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'class' => 'form-check-input',
    'name' => 'instructor_extend_by_teaching',
    'id' => 'id_instructor_extend_by_teaching',
    'value' => 1,
    'checked' => $extendteaching ? 'checked' : null,
]);
echo html_writer::tag('label', get_string('setting_instr_extend_teaching', 'local_tm_certification'), [
    'class' => 'form-check-label',
    'for' => 'id_instructor_extend_by_teaching',
]);
echo html_writer::div(get_string('setting_instr_extend_teaching_desc', 'local_tm_certification'), 'form-text text-muted');
echo html_writer::end_div();

foreach ($levels as $lv => $label) {
    $months = catalog_manager::instructor_validity_months($lv);
    echo html_writer::start_div('form-group row mb-2');
    echo html_writer::tag('label',
        get_string('setting_instr_validity_months', 'local_tm_certification', $label),
        ['class' => 'col-md-3 col-form-label', 'for' => 'id_validity_months_' . $lv]
    );
    echo html_writer::start_div('col-md-3');
    echo html_writer::empty_tag('input', [
        'type' => 'number',
        'min' => 1,
        'step' => 1,
        'name' => 'validity_months_' . $lv,
        'id' => 'id_validity_months_' . $lv,
        'value' => $months,
        'class' => 'form-control',
    ]);
    echo html_writer::div(get_string('setting_instr_validity_months_desc', 'local_tm_certification'), 'form-text text-muted');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::start_div('form-group row');
echo html_writer::start_div('col-md-9 offset-md-3');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('instructor_validity_save', 'local_tm_certification'),
    'class' => 'btn btn-primary',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::tag('hr', '');
echo html_writer::tag('h3', get_string('instructor_levels_section', 'local_tm_certification'));
echo html_writer::tag('p', get_string('instructor_levels_intro', 'local_tm_certification'), ['class' => 'text-muted']);
echo html_writer::tag('p', get_string('instructor_levels_validity_hint', 'local_tm_certification'), ['class' => 'text-muted']);
foreach ($levels as $lv => $label) {
    $months = catalog_manager::instructor_validity_months($lv);
    echo html_writer::tag('span',
        get_string('instructor_levels_validity_item', 'local_tm_certification', (object)[
            'level' => $label,
            'months' => $months,
        ]),
        ['class' => 'badge badge-secondary mr-1']
    );
}
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');
if (empty($users)) {
    echo $OUTPUT->notification(get_string('instructor_levels_empty', 'local_tm_certification'), 'warning');
}

foreach ($users as $u) {
    $current = $matrix[(int)$u->id] ?? [];
    $profile = $profiles[(int)$u->id] ?? null;
    $certdate = !empty($profile->certifiedat) ? date('Y-m-d', (int)$profile->certifiedat) : '';
    $renewdate = !empty($profile->renewedat) ? date('Y-m-d', (int)$profile->renewedat) : '';
    echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'tm-cert-card mb-3']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => (int)$u->id]);
    echo html_writer::tag('h4', fullname($u) . ' (' . s($u->email) . ')');
    foreach ($levels as $lv => $label) {
        $checked = !empty($current[$lv]) ? 'checked' : null;
        echo html_writer::start_div('form-check form-check-inline');
        echo '<label class="form-check-label">';
        echo '<input class="form-check-input" type="checkbox" name="levels[]" value="' . s($lv) . '" ' . $checked . '> ';
        echo s($label);
        echo '</label>';
        echo html_writer::end_div();
    }
    echo html_writer::start_div('mt-2 mb-2');
    echo html_writer::tag('label', get_string('instructor_certifiedat', 'local_tm_certification'), ['class' => 'mr-2']);
    echo html_writer::empty_tag('input', [
        'type' => 'date',
        'name' => 'certifiedat',
        'value' => $certdate,
        'class' => 'form-control d-inline-block mr-3',
        'style' => 'width: 180px;',
    ]);
    echo html_writer::tag('label', get_string('instructor_renewedat', 'local_tm_certification'), ['class' => 'mr-2']);
    echo html_writer::empty_tag('input', [
        'type' => 'date',
        'name' => 'renewedat',
        'value' => $renewdate,
        'class' => 'form-control d-inline-block',
        'style' => 'width: 180px;',
    ]);
    echo html_writer::end_div();
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-sm btn-primary ml-2',
        'value' => get_string('savechanges'),
    ]);
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();
