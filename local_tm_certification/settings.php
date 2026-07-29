<?php
/**
 * Admin settings for local_tm_certification.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

use local_tm_certification\permissions_manager;

if ($hassiteconfig) {
    require_once(__DIR__ . '/lib.php');
    require_once(__DIR__ . '/classes/permissions_manager.php');

    if (!$ADMIN->locate('local_tm_certification_cat')) {
        $ADMIN->add('localplugins', new admin_category(
            'local_tm_certification_cat',
            get_string('pluginname', 'local_tm_certification')
        ));
    }

    $verinfo = local_tm_certification_plugin_version_info();
    $about = new admin_settingpage(
        'local_tm_certification_about',
        get_string('nav_plugin_about', 'local_tm_certification')
    );
    if ($ADMIN->fulltree) {
        $about->add(new admin_setting_heading(
            'local_tm_certification_about_heading',
            get_string('setting_plugin_version', 'local_tm_certification'),
            get_string('setting_plugin_version_desc', 'local_tm_certification', $verinfo['label'])
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_about')) {
        $ADMIN->add('local_tm_certification_cat', $about);
    }

    $dash = new admin_settingpage(
        'local_tm_certification_dashboard',
        get_string('setting_dashboard_heading', 'local_tm_certification')
    );
    if ($ADMIN->fulltree) {
        $dash->add(new admin_setting_heading(
            'local_tm_certification_dash_h',
            get_string('setting_dashboard_heading', 'local_tm_certification'),
            get_string('setting_dashboard_where', 'local_tm_certification')
            . '<p><a class="btn btn-secondary btn-sm" href="'
            . (new moodle_url('/local/tm_certification/index.php'))->out()
            . '">' . get_string('dashboard_open_full', 'local_tm_certification') . '</a></p>'
        ));
        $dash->add(new admin_setting_configcheckbox(
            'local_tm_certification/' . permissions_manager::CFG_DASHBOARD_ENABLED,
            get_string('setting_dashboard_enabled', 'local_tm_certification'),
            get_string('setting_dashboard_enabled_desc', 'local_tm_certification'),
            1
        ));
        $dash->add(new admin_setting_configcheckbox(
            'local_tm_certification/' . permissions_manager::CFG_DASHBOARD_SHOW_MANAGE,
            get_string('setting_dashboard_show_manage', 'local_tm_certification'),
            get_string('setting_dashboard_show_manage_desc', 'local_tm_certification'),
            1
        ));
        $dash->add(new admin_setting_configcheckbox(
            'local_tm_certification/' . permissions_manager::CFG_DASHBOARD_SHOW_INSTRUCT,
            get_string('setting_dashboard_show_instruct', 'local_tm_certification'),
            get_string('setting_dashboard_show_instruct_desc', 'local_tm_certification'),
            1
        ));
        $dash->add(new admin_setting_configcheckbox(
            'local_tm_certification/' . permissions_manager::CFG_DASHBOARD_SHOW_EXAMINEE,
            get_string('setting_dashboard_show_examinee', 'local_tm_certification'),
            get_string('setting_dashboard_show_examinee_desc', 'local_tm_certification'),
            1
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_dashboard')) {
        $ADMIN->add('local_tm_certification_cat', $dash);
    }

    $roles = new admin_settingpage(
        'local_tm_certification_roles',
        get_string('setting_roles_heading', 'local_tm_certification')
    );
    if ($ADMIN->fulltree) {
        $roleoptions = permissions_manager::role_options();
        $roles->add(new admin_setting_heading(
            'local_tm_certification_roles_h',
            get_string('setting_roles_heading', 'local_tm_certification'),
            get_string('setting_roles_intro', 'local_tm_certification')
        ));
        $roles->add(new admin_setting_configmultiselect(
            'local_tm_certification/' . permissions_manager::CFG_ROLES_MANAGE,
            get_string('setting_roles_manage', 'local_tm_certification'),
            get_string('setting_roles_manage_desc', 'local_tm_certification'),
            [],
            $roleoptions
        ));
        $roles->add(new admin_setting_configmultiselect(
            'local_tm_certification/' . permissions_manager::CFG_ROLES_APPROVE,
            get_string('setting_roles_approve', 'local_tm_certification'),
            get_string('setting_roles_approve_desc', 'local_tm_certification'),
            [],
            $roleoptions
        ));
        $roles->add(new admin_setting_configmultiselect(
            'local_tm_certification/' . permissions_manager::CFG_ROLES_INSTRUCT,
            get_string('setting_roles_instruct', 'local_tm_certification'),
            get_string('setting_roles_instruct_desc', 'local_tm_certification'),
            [],
            $roleoptions
        ));
        $roles->add(new admin_setting_configmultiselect(
            'local_tm_certification/' . permissions_manager::CFG_ROLES_EXAMINEE,
            get_string('setting_roles_examinee', 'local_tm_certification'),
            get_string('setting_roles_examinee_desc', 'local_tm_certification'),
            [],
            $roleoptions
        ));
        $roles->add(new admin_setting_configmultiselect(
            'local_tm_certification/' . permissions_manager::CFG_ROLES_VIEW_DASHBOARD,
            get_string('setting_roles_view_dashboard', 'local_tm_certification'),
            get_string('setting_roles_view_dashboard_desc', 'local_tm_certification'),
            [],
            $roleoptions
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_roles')) {
        $ADMIN->add('local_tm_certification_cat', $roles);
    }

    // External page shortcut (same mappings; useful if we expand UI later).
    if (!$ADMIN->locate('local_tm_certification_permissions_ext')) {
        $ADMIN->add('local_tm_certification_cat', new admin_externalpage(
            'local_tm_certification_permissions_ext',
            get_string('nav_permissions', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/settings/permissions.php'),
            'local/tm_certification:manage'
        ));
    }
}
