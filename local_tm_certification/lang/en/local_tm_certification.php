<?php
/**
 * English language strings for local_tm_certification.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'TM Certification';
$string['privacy:metadata'] = 'The TM Certification plugin stores certification session and certificate data for authorised users.';

$string['tm_certification:manage'] = 'Manage certification settings and sessions';
$string['tm_certification:approve'] = 'Approve certification course applications';
$string['tm_certification:instruct'] = 'Act as certification instructor';
$string['tm_certification:examinee'] = 'Act as certification examinee';
$string['tm_certification:viewdashboard'] = 'View certification dashboard';

$string['nav_main'] = 'Certification';
$string['nav_dashboard'] = 'Certification dashboard';
$string['nav_my_certificates'] = 'My certificates';
$string['nav_permissions'] = 'Role assignment';
$string['nav_plugin_about'] = 'Plugin version';

$string['setting_plugin_version'] = 'Installed version';
$string['setting_plugin_version_desc'] = 'Current local_tm_certification release: <strong>{$a}</strong>';

$string['setting_dashboard_heading'] = 'Dashboard visibility';
$string['setting_dashboard_where'] = 'The dashboard appears on: (1) Moodle <strong>site home</strong> / <strong>My home</strong>; (2) navigation “Certification”; (3) <code>/local/tm_certification/index.php</code>. If missing, map roles under Logical identity settings and purge caches.';
$string['setting_dashboard_enabled'] = 'Enable certification dashboard';
$string['setting_dashboard_enabled_desc'] = 'When disabled, the dashboard block/entry is hidden for most users (managers can still open admin settings).';
$string['dashboard_home_title'] = 'Certification dashboard';
$string['dashboard_open_full'] = 'Open full dashboard';
$string['dashboard_hidden_admin_hint'] = 'The certification dashboard is currently hidden for regular users.';
$string['dash_view_manage'] = 'View: Management';
$string['dash_view_instruct'] = 'View: Instructor';
$string['dash_view_examinee'] = 'View: Examinee';
$string['setting_dashboard_show_manage'] = 'Show management dashboard view';
$string['setting_dashboard_show_manage_desc'] = 'Allow the management/approval dashboard shell for users with manage or approve identity.';
$string['setting_dashboard_show_instruct'] = 'Show instructor dashboard view';
$string['setting_dashboard_show_instruct_desc'] = 'Allow the instructor dashboard shell.';
$string['setting_dashboard_show_examinee'] = 'Show examinee dashboard view';
$string['setting_dashboard_show_examinee_desc'] = 'Allow the examinee dashboard shell.';

$string['setting_roles_heading'] = 'Logical identity ↔ Moodle roles';
$string['setting_roles_intro'] = 'Map Moodle roles to logical identities. Do not hard-code role shortnames in code — change mappings here. Users also gain access if they already have the matching capability.';
$string['setting_roles_manage'] = 'Management roles';
$string['setting_roles_manage_desc'] = 'Roles treated as certification managers (settings, direct session create).';
$string['setting_roles_approve'] = 'Approval roles';
$string['setting_roles_approve_desc'] = 'Roles that may approve course applications (managers always may).';
$string['setting_roles_instruct'] = 'Instructor roles';
$string['setting_roles_instruct_desc'] = 'Roles treated as certification instructors.';
$string['setting_roles_examinee'] = 'Examinee roles';
$string['setting_roles_examinee_desc'] = 'Roles treated as certification examinees/students.';
$string['setting_roles_view_dashboard'] = 'Extra roles that may open the dashboard';
$string['setting_roles_view_dashboard_desc'] = 'Optional additional roles allowed to open the dashboard entry (in addition to the identities above).';

$string['error_view_access_denied'] = 'You do not have permission to use the certification plugin.';
$string['error_dashboard_disabled'] = 'The certification dashboard is currently disabled by an administrator.';
$string['error_dashboard_view_none'] = 'No dashboard view is available for your account. Ask an administrator to assign a certification identity or enable the matching dashboard view.';

$string['dash_hello'] = 'Hello, {$a}';
$string['dash_manage_title'] = 'Certification management centre';
$string['dash_manage_cover_hint'] = 'Focus on approvals and sessions. Personal certificate badges are hidden in this view.';
$string['dash_instructor_cover_hint'] = 'Cover shows the latest passed instructor-certificate level (wired in a later phase).';
$string['dash_examinee_cover_hint'] = 'Cover shows the latest passed exam-certificate level and candidate ID (wired in a later phase). No expiry for examinee certificates.';
$string['dash_badge_instructor_level_placeholder'] = 'Instructor level: (pending data)';
$string['dash_badge_exam_level_placeholder'] = 'Exam level: (pending data)';
$string['dash_badge_valid_placeholder'] = 'Status: Valid (placeholder)';

$string['dash_section_pending'] = 'Pending course applications';
$string['dash_section_sessions_manage'] = 'Open sessions';
$string['dash_section_my_sessions'] = 'My teaching sessions';
$string['dash_section_my_applications'] = 'My applications';
$string['dash_section_my_exams'] = 'Registered exam sessions';
$string['dash_empty_placeholder'] = 'No records yet. This section will fill in as later phases are implemented.';

$string['dash_action_create_session'] = 'Create session (coming soon)';
$string['dash_action_apply'] = 'Apply to open a course (coming soon)';
$string['dash_action_temp_enrol'] = 'Add examinees (coming soon)';
$string['dash_action_practical'] = 'Enter practical scores (coming soon)';
$string['dash_action_roster'] = 'Roster (coming soon)';

$string['cert_page_title'] = 'My certificates';
$string['cert_page_intro'] = 'Full certificate history will appear here. Instructors see exam + instructor certificates; examinees see exam certificates only.';
$string['cert_empty'] = 'No certificate records yet.';

$string['perm_page_intro'] = 'Assign Moodle roles to each logical identity. Changes are stored in plugin settings and take effect immediately. You can also grant the matching capabilities under Site administration → Users → Permissions → Define roles.';
$string['perm_saved'] = 'Role mappings saved.';
$string['perm_save'] = 'Save role mappings';
$string['perm_open_moodle_roles'] = 'Open Moodle role assignments';
