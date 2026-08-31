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
    require_once(__DIR__ . '/classes/course_access_helper.php');

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

    require_once(__DIR__ . '/classes/assessment_manager.php');
    $assess = new admin_settingpage('local_tm_certification_assess', get_string('setting_assess_heading', 'local_tm_certification'));
    if ($ADMIN->fulltree) {
        $assess->add(new admin_setting_heading(
            'local_tm_certification_assess_h',
            get_string('setting_assess_heading', 'local_tm_certification'),
            get_string('setting_assess_desc', 'local_tm_certification')
        ));
        foreach (session_manager::level_options() as $level => $label) {
            $assess->add(new admin_setting_heading(
                'local_tm_certification_assess_' . $level,
                $label,
                ''
            ));
            $assess->add(new admin_setting_configcheckbox(
                'local_tm_certification/written_enabled_' . $level,
                get_string('setting_written_enabled', 'local_tm_certification', $label),
                get_string('setting_written_enabled_desc', 'local_tm_certification'),
                0
            ));
            $courseid = (int)get_config('local_tm_certification', 'mapping_course_' . $level);
            $quizopts = [0 => get_string('setting_written_quiz_none', 'local_tm_certification')];
            if ($courseid > 0) {
                $quizopts += \local_tm_certification\assessment_manager::quiz_options_for_course($courseid);
            }
            $assess->add(new admin_setting_configselect(
                'local_tm_certification/written_quiz_cmid_' . $level,
                get_string('setting_written_quiz', 'local_tm_certification', $label),
                '',
                0,
                $quizopts
            ));
            $assess->add(new admin_setting_configtext(
                'local_tm_certification/written_pass_' . $level,
                get_string('setting_written_pass', 'local_tm_certification', $label),
                get_string('setting_written_pass_desc', 'local_tm_certification'),
                '60',
                PARAM_FLOAT
            ));
            $assess->add(new admin_setting_configtext(
                'local_tm_certification/practical_pass_' . $level,
                get_string('setting_practical_pass', 'local_tm_certification', $label),
                get_string('setting_practical_pass_desc', 'local_tm_certification'),
                '90',
                PARAM_FLOAT
            ));
        }
        $assess->add(new admin_setting_heading(
            'local_tm_certification_assess_status_h',
            get_string('setting_attendance_status_heading', 'local_tm_certification'),
            get_string('setting_attendance_status_desc', 'local_tm_certification')
        ));
        $assess->add(new admin_setting_configtext(
            'local_tm_certification/attendance_statusid_present',
            get_string('setting_attendance_statusid_present', 'local_tm_certification'),
            get_string('setting_attendance_statusid_present_desc', 'local_tm_certification'),
            '0',
            PARAM_INT
        ));
        $assess->add(new admin_setting_configtext(
            'local_tm_certification/attendance_statusid_absent',
            get_string('setting_attendance_statusid_absent', 'local_tm_certification'),
            get_string('setting_attendance_statusid_absent_desc', 'local_tm_certification'),
            '0',
            PARAM_INT
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_assess')) {
        $ADMIN->add('local_tm_certification_cat', $assess);
    }

    $instrvalidity = new admin_settingpage(
        'local_tm_certification_instr_validity',
        get_string('setting_instr_validity_heading', 'local_tm_certification')
    );
    if ($ADMIN->fulltree) {
        $instrvalidity->add(new admin_setting_heading(
            'local_tm_certification_instr_validity_h',
            get_string('setting_instr_validity_heading', 'local_tm_certification'),
            get_string('setting_instr_validity_desc', 'local_tm_certification')
        ));
        $instrvalidity->add(new admin_setting_configcheckbox(
            'local_tm_certification/instructor_extend_by_teaching',
            get_string('setting_instr_extend_teaching', 'local_tm_certification'),
            get_string('setting_instr_extend_teaching_desc', 'local_tm_certification'),
            0
        ));
        foreach (session_manager::level_options() as $level => $label) {
            $instrvalidity->add(new admin_setting_configtext(
                'local_tm_certification/instructor_validity_months_' . $level,
                get_string('setting_instr_validity_months', 'local_tm_certification', $label),
                get_string('setting_instr_validity_months_desc', 'local_tm_certification'),
                '12',
                PARAM_INT
            ));
        }
    }
    if (!$ADMIN->locate('local_tm_certification_instr_validity')) {
        $ADMIN->add('local_tm_certification_cat', $instrvalidity);
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
        $autoroleopts = [0 => get_string('setting_auto_role_examinee_none', 'local_tm_certification')] + $roleoptions;
        $roles->add(new admin_setting_configselect(
            'local_tm_certification/auto_role_examinee',
            get_string('setting_auto_role_examinee', 'local_tm_certification'),
            get_string('setting_auto_role_examinee_desc', 'local_tm_certification'),
            0,
            $autoroleopts
        ));
    }
    if (!$ADMIN->locate('local_tm_certification_roles')) {
        $ADMIN->add('local_tm_certification_cat', $roles);
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
    if (!$ADMIN->locate('local_tm_certification_legacy_migrate_ext')) {
        $ADMIN->add('local_tm_certification_cat', new admin_externalpage(
            'local_tm_certification_legacy_migrate_ext',
            get_string('nav_legacy_migrate', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/admin/legacy_name_migrate.php'),
            'local/tm_certification:manage'
        ));
    }

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
    if (!$ADMIN->locate('local_tm_certification_notify')) {
        $ADMIN->add('local_tm_certification_cat', $notify);
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
