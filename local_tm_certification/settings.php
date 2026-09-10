<?php
/**
 * Admin settings for local_tm_certification.
 *
 * Visible menu order comes from admin_menu (keep in sync with left flat nav).
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

use local_tm_certification\admin_menu;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

if ($hassiteconfig) {
    require_once(__DIR__ . '/lib.php');
    require_once(__DIR__ . '/classes/permissions_manager.php');
    require_once(__DIR__ . '/classes/session_manager.php');
    require_once(__DIR__ . '/classes/course_access_helper.php');
    require_once(__DIR__ . '/classes/admin_menu.php');
    require_once(__DIR__ . '/classes/attendance_helper.php');

    if (!$ADMIN->locate('local_tm_certification_cat')) {
        $ADMIN->add('localplugins', new admin_category(
            'local_tm_certification_cat',
            get_string('pluginname', 'local_tm_certification')
        ));
    }

    $verinfo = local_tm_certification_plugin_version_info();

    // --- Setting page definitions (content) ---
    $settingpages = [];

    $about = new admin_settingpage('local_tm_certification_about', get_string('nav_plugin_about', 'local_tm_certification'));
    if ($ADMIN->fulltree) {
        $about->add(new admin_setting_heading(
            'local_tm_certification_about_heading',
            get_string('setting_plugin_version', 'local_tm_certification'),
            get_string('setting_plugin_version_desc', 'local_tm_certification', $verinfo['label'])
        ));
    }
    $settingpages['about'] = $about;

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
    $settingpages['dashboard'] = $dash;

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
        $map->add(new admin_setting_configselect(
            'local_tm_certification/' . \local_tm_certification\course_access_helper::CFG_MAPPING_INSTRUCTOR,
            get_string('setting_coursemap_instructor', 'local_tm_certification'),
            get_string('setting_coursemap_instructor_desc', 'local_tm_certification'),
            0,
            $courseopts
        ));
        $map->add(new admin_setting_heading(
            'local_tm_certification_coursemap_access_h',
            get_string('setting_coursemap_access_heading', 'local_tm_certification'),
            get_string('setting_coursemap_access_desc', 'local_tm_certification')
        ));
    }
    $settingpages['coursemap'] = $map;

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
        $autoroleopts = [0 => get_string('setting_auto_role_examinee_none', 'local_tm_certification')] + $roleoptions;
        $roles->add(new admin_setting_configselect(
            'local_tm_certification/auto_role_examinee',
            get_string('setting_auto_role_examinee', 'local_tm_certification'),
            get_string('setting_auto_role_examinee_desc', 'local_tm_certification'),
            0,
            $autoroleopts
        ));
    }
    $settingpages['roles'] = $roles;

    $notify = new admin_settingpage('local_tm_certification_notify', get_string('setting_notify_heading', 'local_tm_certification'));
    if ($ADMIN->fulltree) {
        require_once(__DIR__ . '/classes/notification_helper.php');
        $notify->add(new admin_setting_heading(
            'local_tm_certification_notify_h',
            get_string('setting_notify_heading', 'local_tm_certification'),
            get_string('setting_notify_desc', 'local_tm_certification')
        ));
        $notify->add(new admin_setting_configcheckbox(
            'local_tm_certification/' . \local_tm_certification\notification_helper::CFG_ENABLED,
            get_string('setting_notify_enabled', 'local_tm_certification'),
            get_string('setting_notify_enabled_desc', 'local_tm_certification'),
            1
        ));
        $notify->add(new admin_setting_configcheckbox(
            'local_tm_certification/' . \local_tm_certification\notification_helper::CFG_APP_REVIEW,
            get_string('setting_notify_app_review', 'local_tm_certification'),
            get_string('setting_notify_app_review_desc', 'local_tm_certification'),
            1
        ));
        $notify->add(new admin_setting_configcheckbox(
            'local_tm_certification/' . \local_tm_certification\notification_helper::CFG_NEW_ACCOUNT,
            get_string('setting_notify_new_account', 'local_tm_certification'),
            get_string('setting_notify_new_account_desc', 'local_tm_certification'),
            1
        ));
        $notify->add(new admin_setting_configcheckbox(
            'local_tm_certification/' . \local_tm_certification\notification_helper::CFG_ENROL_EXISTING,
            get_string('setting_notify_enrol_existing', 'local_tm_certification'),
            get_string('setting_notify_enrol_existing_desc', 'local_tm_certification'),
            0
        ));
    }
    $settingpages['notify'] = $notify;

    // --- Register visible items in admin_menu order ---
    foreach (admin_menu::items() as $item) {
        $section = $item['section'];
        if ($ADMIN->locate($section)) {
            continue;
        }
        if ($item['kind'] === 'external') {
            $ADMIN->add('local_tm_certification_cat', new admin_externalpage(
                $section,
                get_string($item['labelkey'], 'local_tm_certification'),
                $item['url'],
                admin_menu::moodle_capability($item)
            ));
            continue;
        }
        if ($item['kind'] === 'setting' && isset($settingpages[$item['key']])) {
            $ADMIN->add('local_tm_certification_cat', $settingpages[$item['key']]);
        }
    }

    // --- Hidden legacy redirects (not in visible menu) ---
    $assessurl = (new moodle_url('/local/tm_certification/admin/assess_components.php'))->out(false);

    // Old "評量規則" settings.php section → 筆試術科設定.
    $assessbridge = new admin_settingpage(
        'local_tm_certification_assess',
        get_string('setting_assess_heading', 'local_tm_certification'),
        'moodle/site:config',
        true
    );
    if ($ADMIN->fulltree) {
        $assessbridge->add(new admin_setting_heading(
            'local_tm_certification_assess_redirect',
            get_string('setting_assess_heading', 'local_tm_certification'),
            get_string('assess_redirect_hint', 'local_tm_certification')
                . '<br><br>'
                . html_writer::link($assessurl, get_string('setting_assess_heading', 'local_tm_certification'),
                    ['class' => 'btn btn-primary'])
                . html_writer::script('window.location.href = ' . json_encode($assessurl) . ';')
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_assess')) {
        $ADMIN->add('local_tm_certification_cat', $assessbridge);
    }

    // Old level→attendance map → 筆試術科設定 (attendance now per practical item).
    $attbridge = new admin_settingpage(
        'local_tm_certification_attmap',
        get_string('setting_attendance_map_heading', 'local_tm_certification'),
        'moodle/site:config',
        true
    );
    if ($ADMIN->fulltree) {
        $attbridge->add(new admin_setting_heading(
            'local_tm_certification_attmap_redirect',
            get_string('setting_attendance_map_heading', 'local_tm_certification'),
            get_string('attendance_map_moved_hint', 'local_tm_certification')
                . '<br><br>'
                . html_writer::link($assessurl, get_string('setting_assess_heading', 'local_tm_certification'),
                    ['class' => 'btn btn-primary'])
                . html_writer::script('window.location.href = ' . json_encode($assessurl) . ';')
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_attmap')) {
        $ADMIN->add('local_tm_certification_cat', $attbridge);
    }

    $instrurl = (new moodle_url('/local/tm_certification/admin/instructors.php'))->out(false);
    $instrvalidity = new admin_settingpage(
        'local_tm_certification_instr_validity',
        get_string('setting_instr_validity_heading', 'local_tm_certification'),
        'moodle/site:config',
        true
    );
    if ($ADMIN->fulltree) {
        $instrvalidity->add(new admin_setting_heading(
            'local_tm_certification_instr_validity_redirect',
            get_string('setting_instr_validity_heading', 'local_tm_certification'),
            get_string('instructor_validity_moved_hint', 'local_tm_certification')
                . '<br><br>'
                . html_writer::link($instrurl, get_string('nav_instructor_levels', 'local_tm_certification'),
                    ['class' => 'btn btn-primary'])
                . html_writer::script('window.location.href = ' . json_encode($instrurl) . ';')
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_instr_validity')) {
        $ADMIN->add('local_tm_certification_cat', $instrvalidity);
    }
}
