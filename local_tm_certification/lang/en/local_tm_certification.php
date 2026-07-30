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
$string['nav_sessions'] = 'Certification sessions';
$string['nav_quickentry'] = 'Certification quick access';

$string['setting_plugin_version'] = 'Installed version';
$string['setting_plugin_version_desc'] = 'Current local_tm_certification release: <strong>{$a}</strong>';

$string['setting_dashboard_heading'] = 'Dashboard visibility';
$string['setting_dashboard_where'] = 'The dashboard appears on: (1) Moodle <strong>site home</strong> / <strong>My home</strong>; (2) navigation ?Certification?; (3) <code>/local/tm_certification/index.php</code>. If missing, map roles under Logical identity settings and purge caches.';
$string['setting_dashboard_enabled'] = 'Enable certification dashboard';
$string['setting_dashboard_enabled_desc'] = 'When disabled, the dashboard block/entry is hidden for most users (managers can still open admin settings).';
$string['setting_dashboard_show_manage'] = 'Show management dashboard view';
$string['setting_dashboard_show_manage_desc'] = 'Allow the management/approval dashboard shell for users with manage or approve identity.';
$string['setting_dashboard_show_instruct'] = 'Show instructor dashboard view';
$string['setting_dashboard_show_instruct_desc'] = 'Allow the instructor dashboard shell.';
$string['setting_dashboard_show_examinee'] = 'Show examinee dashboard view';
$string['setting_dashboard_show_examinee_desc'] = 'Allow the examinee dashboard shell.';
$string['dashboard_home_title'] = 'Certification dashboard';
$string['dashboard_open_full'] = 'Open full dashboard';
$string['dashboard_hidden_admin_hint'] = 'The certification dashboard is currently hidden for regular users.';
$string['dash_view_manage'] = 'View: Management';
$string['dash_view_instruct'] = 'View: Instructor';
$string['dash_view_examinee'] = 'View: Examinee';

$string['setting_roles_heading'] = 'Logical identity ? Moodle roles';
$string['setting_roles_intro'] = 'Map Moodle roles to logical identities. Do not hard-code role shortnames in code ? change mappings here. Users also gain access if they already have the matching capability.';
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
$string['error_session_name_required'] = 'Session name is required.';
$string['error_session_level_invalid'] = 'Invalid certification level.';
$string['error_session_time_invalid'] = 'End time must be after start time.';
$string['error_session_course_invalid'] = 'Linked Moodle course was not found.';
$string['error_batch_email_invalid'] = 'A valid email address is required.';
$string['error_batch_name_required'] = 'First name and last name are required.';
$string['error_batch_user_invalid'] = 'Failed to create the Moodle user account.';
$string['error_moodle_manual_enrol_disabled'] = 'Manual enrolment is disabled on this site.';
$string['error_moodle_manual_instance_missing'] = 'Could not create a manual enrolment instance for the course.';
$string['error_moodle_manual_role_not_configured'] = 'No role is configured for manual enrolment.';
$string['error_moodle_enrol_verify_failed'] = 'Moodle course enrolment could not be verified.';

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
$string['dash_empty_sessions'] = 'No open sessions yet.';
$string['dash_pending_later'] = 'Course application review arrives in a later phase.';

$string['dash_action_create_session'] = 'Create session';
$string['dash_action_manage_sessions'] = 'Manage sessions';
$string['dash_action_apply'] = 'Apply to open a course';
$string['dash_action_review'] = 'Review applications';
$string['dash_action_temp_enrol'] = 'Add examinees';
$string['dash_action_practical'] = 'Enter practical scores (coming soon)';
$string['dash_action_roster'] = 'Roster';
$string['dash_pending_count'] = '{$a} pending';
$string['dash_empty_applications'] = 'No applications yet.';
$string['dash_app_line'] = '#{$a->id} ? {$a->level} ? {$a->when} ? {$a->status}';

$string['cert_page_title'] = 'My certificates';
$string['cert_page_intro'] = 'Full certificate history will appear here. Instructors see exam + instructor certificates; examinees see exam certificates only.';
$string['cert_empty'] = 'No certificate records yet.';

$string['perm_page_intro'] = 'Assign Moodle roles to each logical identity. Changes are stored in plugin settings and take effect immediately. You can also grant the matching capabilities under Site administration ? Users ? Permissions ? Define roles.';
$string['perm_saved'] = 'Role mappings saved.';
$string['perm_save'] = 'Save role mappings';
$string['perm_open_moodle_roles'] = 'Open Moodle role assignments';

$string['level_basic'] = 'Basic';
$string['level_intermediate'] = 'Intermediate';
$string['level_advanced'] = 'Advanced';

$string['session_create'] = 'Create session';
$string['session_edit'] = 'Edit session';
$string['session_name'] = 'Session name';
$string['session_level'] = 'Certification level';
$string['session_venue'] = 'Venue';
$string['session_time'] = 'Date / time';
$string['session_start'] = 'Start';
$string['session_end'] = 'End';
$string['session_course'] = 'Linked Moodle course';
$string['session_course_help'] = 'Examinees added to this session will be enrolled into this course (manual enrol). Leave empty to skip Moodle enrol for now.';
$string['session_course_none'] = '? Not linked ?';
$string['session_instructor'] = 'Assigned instructor';
$string['session_instructor_none'] = '? Not assigned ?';
$string['session_status'] = 'Status';
$string['session_status_open'] = 'Open';
$string['session_status_closed'] = 'Closed';
$string['session_enrol_count'] = 'Examinees';
$string['session_roster'] = 'Roster';
$string['session_close'] = 'Close';
$string['session_closed'] = 'Session closed.';
$string['session_saved'] = 'Session saved.';
$string['session_empty'] = 'No sessions yet. Create one to get started.';

$string['enrol_add_heading'] = 'Add examinee';
$string['enrol_add_examinee'] = 'Add to session';
$string['enrol_empty'] = 'No examinees on this session yet.';
$string['enrol_cancel'] = 'Remove';
$string['enrol_cancelled'] = 'Examinee removed from session.';
$string['enrol_added_created'] = 'Created Moodle account and added {$a} to the session.';
$string['enrol_added_linked'] = 'Linked existing account {$a} and added to the session.';
$string['enrol_initial_password'] = 'Initial password: {$a}';
$string['enrol_no_course_hint'] = 'This session has no linked Moodle course ? roster is stored in the plugin only (no course enrol sync).';

$string['nav_venues'] = 'Venue management';
$string['nav_instructor_levels'] = 'Instructor level authorization';
$string['setting_coursemap_heading'] = 'Level-to-course mapping';
$string['setting_coursemap_desc'] = 'Session creation no longer selects Moodle course manually. The system auto-links by certification level using this mapping.';
$string['setting_coursemap_one'] = '{$a} mapped Moodle course';

$string['session_name_prefix'] = '[';
$string['session_name_suffix'] = ' Certification Course';
$string['session_name_auto_hint'] = 'Name is auto-generated from selected level.';
$string['session_name_fixed_rule'] = 'Session name is fixed as "[Level] Certification Course". Use venue and date fields below.';
$string['session_venue_none'] = '?X Select level first ?X';
$string['error_session_venue_required'] = 'Please select a venue.';
$string['error_session_venue_level_mismatch'] = 'Selected venue is not authorized for this level or is inactive.';
$string['error_session_instructor_required'] = 'Please select an instructor.';
$string['error_session_instructor_level_mismatch'] = 'Selected instructor is not authorized for this level.';
$string['error_venue_code_required'] = 'Venue code is required.';
$string['error_venue_code_duplicated'] = 'Venue code must be unique within the same certification level.';
$string['error_instructor_cert_date_invalid'] = 'Invalid certificate date format.';

$string['venue_name'] = 'Venue name';
$string['venue_code'] = 'Venue code';
$string['venue_active'] = 'Active';
$string['venue_passedat'] = 'Passed date';
$string['venue_add'] = 'Add / edit venue';
$string['venue_save'] = 'Save venue';
$string['venue_saved'] = 'Venue settings saved.';
$string['venue_empty'] = 'No venues yet.';
$string['error_venue_name_required'] = 'Venue name is required.';

$string['instructor_levels_intro'] = 'Check certification levels each instructor is authorized to teach. Session form will only show matching instructors.';
$string['instructor_levels_saved'] = 'Instructor level authorizations updated.';
$string['instructor_levels_empty'] = 'No instructor list found. Assign Instructor roles in plugin settings and ensure those roles are assigned at system context.';
$string['instructor_certifiedat'] = 'Certificate obtained date';
$string['instructor_renewedat'] = 'Certificate renew date';

$string['nav_applications'] = 'Course applications';
$string['setting_attendance_map_heading'] = 'Level-to-attendance mapping';
$string['setting_attendance_map_desc'] = 'Select an existing mod_attendance activity (by course module). The plugin never creates a new attendance activity. On approve it only adds a session slot inside the selected activity.';
$string['setting_attendance_map_one'] = '{$a} mapped attendance activity';
$string['setting_attendance_none'] = '? Not mapped ?';

$string['app_apply_title'] = 'Apply to open a certification course';
$string['app_apply_intro'] = 'Choose a level you are authorised to teach, then pick venue and schedule. Batch roster upload arrives in the next phase; after approval you can add examinees from the session roster.';
$string['app_submit'] = 'Submit application';
$string['app_submit_success'] = 'Application #{$a} submitted and waiting for review.';
$string['app_applicant_note'] = 'Note to reviewers (optional)';
$string['app_manager_note'] = 'Reviewer note (optional)';
$string['app_roster_hint_label'] = 'Examinee list';
$string['app_roster_hint'] = 'Batch upload on apply is coming in P4. For now, add examinees after the session is approved.';
$string['app_review_title'] = 'Review course applications';
$string['app_status'] = 'Status';
$string['app_status_pending'] = 'Pending';
$string['app_status_approved'] = 'Approved';
$string['app_status_rejected'] = 'Rejected';
$string['app_col_id'] = 'ID';
$string['app_col_applicant'] = 'Applicant';
$string['app_empty_list'] = 'No applications in this filter.';
$string['app_action_approve'] = 'Approve';
$string['app_action_reject'] = 'Reject';
$string['app_confirm_approve'] = 'Confirm approve';
$string['app_confirm_reject'] = 'Confirm reject';
$string['app_approve_success'] = 'Approved. Session #{$a} created.';
$string['app_approve_attendance_skipped'] = 'Attendance slot was skipped because this level has no mapped attendance activity.';
$string['app_reject_success'] = 'Application rejected.';
$string['error_app_venue_required'] = 'Please select a venue.';
$string['error_app_venue_invalid'] = 'Selected venue is invalid for this level.';
$string['error_app_instructor_not_qualified'] = 'Selected instructor is not authorised for this level.';
$string['error_app_applicant_not_qualified'] = 'You are not authorised to apply for this certification level.';
$string['error_app_course_unmapped'] = 'This level has no mapped Moodle course. Ask an administrator to set Level-to-course mapping.';
$string['error_app_not_pending'] = 'This application is not pending.';
$string['error_app_not_found'] = 'Application not found.';
$string['error_app_no_qualified_level'] = 'You have no authorised instructor levels. Ask an administrator to grant instructor level authorisation first.';
