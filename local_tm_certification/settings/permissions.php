<?php
/**
 * Role mapping helper page (mirrors admin settings; convenient entry).
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');

use local_tm_certification\permissions_manager;

require_login();
$context = context_system::instance();
require_capability('local/tm_certification:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/settings/permissions.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('nav_permissions', 'local_tm_certification'));
$PAGE->set_heading(get_string('nav_permissions', 'local_tm_certification'));

$roleoptions = permissions_manager::role_options();
$fields = [
    permissions_manager::CFG_ROLES_MANAGE => 'setting_roles_manage',
    permissions_manager::CFG_ROLES_APPROVE => 'setting_roles_approve',
    permissions_manager::CFG_ROLES_INSTRUCT => 'setting_roles_instruct',
    permissions_manager::CFG_ROLES_EXAMINEE => 'setting_roles_examinee',
    permissions_manager::CFG_ROLES_VIEW_DASHBOARD => 'setting_roles_view_dashboard',
];

if (data_submitted() && confirm_sesskey()) {
    foreach ($fields as $cfgkey => $unused) {
        $selected = optional_param_array($cfgkey, [], PARAM_INT);
        $selected = array_values(array_unique(array_filter(array_map('intval', $selected), static function(int $id): bool {
            return $id > 0 && isset($roleoptions[$id]);
        })));
        set_config($cfgkey, implode(',', $selected), 'local_tm_certification');
    }
    redirect($PAGE->url, get_string('perm_saved', 'local_tm_certification'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::tag('h2', get_string('nav_permissions', 'local_tm_certification')),
    'tm-cert-page-header'
);
echo html_writer::tag('p', get_string('perm_page_intro', 'local_tm_certification'), ['class' => 'text-muted']);
echo html_writer::div(
    html_writer::link(
        new moodle_url('/admin/roles/assign.php', ['contextid' => $context->id]),
        get_string('perm_open_moodle_roles', 'local_tm_certification'),
        ['class' => 'btn btn-secondary btn-sm']
    ),
    'mb-3'
);

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false), 'class' => 'tm-cert-card']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

foreach ($fields as $cfgkey => $strkey) {
    $current = permissions_manager::get_configured_roleids($cfgkey);
    echo html_writer::tag('h4', get_string($strkey, 'local_tm_certification'));
    echo html_writer::tag('p', get_string($strkey . '_desc', 'local_tm_certification'), ['class' => 'text-muted small']);
    echo html_writer::start_tag('select', [
        'name' => $cfgkey . '[]',
        'multiple' => 'multiple',
        'size' => '8',
        'class' => 'form-control mb-3',
    ]);
    foreach ($roleoptions as $roleid => $rolename) {
        $attrs = ['value' => $roleid];
        if (in_array((int)$roleid, $current, true)) {
            $attrs['selected'] = 'selected';
        }
        echo html_writer::tag('option', s($rolename), $attrs);
    }
    echo html_writer::end_tag('select');
}

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => get_string('perm_save', 'local_tm_certification'),
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
