<?php
/**
 * Admin settings for local_tm_certification.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

if ($hassiteconfig) {
    require_once(__DIR__ . '/lib.php');
    require_once(__DIR__ . '/classes/permissions_manager.php');
    require_once(__DIR__ . '/classes/session_manager.php');

    if (!$ADMIN->locate('local_tm_certification_cat')) {
        $ADMIN->add('localplugins', new admin_category(
            'local_tm_certification_cat',
            get_string('pluginname', 'local_tm_certification')
        ));
    }

    $verinfo = local_tm_certification_plugin_version_info();
    $about = new admin_settingpage('local_tm_certification_about', get_string('nav_plugin_about', 'local_tm_certification'));
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

    $dash = new admin_settingpage('local_tm_certification_dashboard', get_string('setting_dashboard_heading', 'local_tm_certification'));
    if ($ADMIN->fulltree) {
        $dash->add(new admin_setting_heading(
            'local_tm_certification_dash_h',
            get_string('setting_dashboard_heading', 'local_tm_certification'),
            get_string('setting_dashboard_where', 'local_tm_certification')
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

    $map = new admin_settingpage('local_tm_certification_coursemap', get_string('setting_coursemap_heading', 'local_tm_certification'));
    if ($ADMIN->fulltree) {
        $courses = $DB->get_records_menu('course', null, 'fullname ASC', 'id, fullname', 0, 1000);
        unset($courses[SITEID]);
        $courseopts = [0 => get_string('session_course_none', 'local_tm_certification')] + $courses;
        $map->add(new admin_setting_heading(
            'local_tm_certification_coursemap_h',
            get_string('setting_coursemap_heading', 'local_tm_certification'),
            get_string('setting_coursemap_desc', 'local_tm_certification')
        ));
        foreach (session_manager::level_options() as $level => $label) {
            $map->add(new admin_setting_configselect(
                'local_tm_certification/mapping_course_' . $level,
                get_string('setting_coursemap_one', 'local_tm_certification', $label),
                '',
                0,
                $courseopts
            ));
        }
    }
    if (!$ADMIN->locate('local_tm_certification_coursemap')) {
        $ADMIN->add('local_tm_certification_cat', $map);
    }

    require_once(__DIR__ . '/classes/attendance_helper.php');
    $attmap = new admin_settingpage('local_tm_certification_attmap', get_string('setting_attendance_map_heading', 'local_tm_certification'));
    if ($ADMIN->fulltree) {
        $attmap->add(new admin_setting_heading(
            'local_tm_certification_attmap_h',
            get_string('setting_attendance_map_heading', 'local_tm_certification'),
            get_string('setting_attendance_map_desc', 'local_tm_certification')
        ));
        foreach (session_manager::level_options() as $level => $label) {
            $courseid = (int)get_config('local_tm_certification', 'mapping_course_' . $level);
            $attopts = [0 => get_string('setting_attendance_none', 'local_tm_certification')];
            if ($courseid > 0) {
                $attopts += \local_tm_certification\attendance_helper::attendance_options_for_course($courseid);
            }
            $attmap->add(new admin_setting_configselect(
                'local_tm_certification/mapping_attendance_cmid_' . $level,
                get_string('setting_attendance_map_one', 'local_tm_certification', $label),
                '',
                0,
                $attopts
            ));
        }
    }
    if (!$ADMIN->locate('local_tm_certification_attmap')) {
        $ADMIN->add('local_tm_certification_cat', $attmap);
    }

    $roles = new admin_settingpage('local_tm_certification_roles', get_string('setting_roles_heading', 'local_tm_certification'));
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

    if (!$ADMIN->locate('local_tm_certification_permissions_ext')) {
        $ADMIN->add('local_tm_certification_cat', new admin_externalpage(
            'local_tm_certification_permissions_ext',
            get_string('nav_permissions', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/settings/permissions.php'),
            'local/tm_certification:manage'
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_venues_ext')) {
        $ADMIN->add('local_tm_certification_cat', new admin_externalpage(
            'local_tm_certification_venues_ext',
            get_string('nav_venues', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/admin/venues.php'),
            'local/tm_certification:manage'
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_instructors_ext')) {
        $ADMIN->add('local_tm_certification_cat', new admin_externalpage(
            'local_tm_certification_instructors_ext',
            get_string('nav_instructor_levels', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/admin/instructors.php'),
            'local/tm_certification:manage'
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_review_ext')) {
        $ADMIN->add('local_tm_certification_cat', new admin_externalpage(
            'local_tm_certification_review_ext',
            get_string('nav_applications', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/admin/review.php'),
            'local/tm_certification:approve'
        ));
    }
}
