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
$string['dashboard_home_title'] = 'AI Cobot Competency Certification';
$string['dashboard_home_title_manage'] = 'AI Cobot Competency Certification';
$string['dashboard_home_title_instruct'] = 'AI Cobot Competency Certification';
$string['dashboard_home_title_examinee'] = 'AI Cobot Competency Certification';
$string['dashboard_open_full'] = 'Open full page';
$string['dashboard_open_full_link'] = 'Full dashboard →';
$string['dashboard_hidden_admin_hint'] = 'The certification dashboard is currently hidden for regular users.';
$string['dash_view_manage'] = 'Management';
$string['dash_view_instruct'] = 'Instructor';
$string['dash_view_examinee'] = 'Examinee';

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
$string['setting_auto_role_examinee'] = 'Role to assign when adding examinees to a session';
$string['setting_auto_role_examinee_desc'] = 'When an instructor/manager adds an examinee to a session (new account, issue ID, or keep ID), automatically assign this Moodle role at system context. Existing roles are kept. Choose none to skip auto-assign.';
$string['setting_auto_role_examinee_none'] = '— Do not auto-assign —';

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
$string['dash_badge_kpi_pending'] = '{$a} pending';
$string['dash_badge_kpi_open'] = '{$a} in progress';
$string['dash_badge_status_valid'] = 'Valid';
$string['dash_badge_status_expired'] = 'Expired';
$string['dash_badge_status_pending'] = 'Pending dates';
$string['dash_kpi_pending'] = 'Pending';
$string['dash_kpi_open'] = 'In progress';
$string['dash_kpi_week'] = 'Examinees this week';
$string['dash_session_people'] = '{$a} people';
$string['dash_product_title'] = 'AI Cobot Competency Certification';
$string['dash_manage_title'] = 'AI Cobot Competency Certification';
$string['dash_instruct_title'] = 'AI Cobot Competency Certification';
$string['dash_examinee_title'] = 'AI Cobot Competency Certification';
$string['dash_manage_cover_hint'] = '';
$string['dash_instructor_cover_hint'] = '';
$string['dash_instructor_cover_certified'] = 'Certificate obtained: {$a}';
$string['dash_instructor_cover_renewed'] = 'Certificate renewed: {$a}';
$string['dash_examinee_cover_hint'] = '';
$string['dash_badge_instructor_level'] = '{$a}';
$string['dash_badge_instructor_level_none'] = 'Not recognised yet';
$string['dash_badge_instructor_level_placeholder'] = 'Instructor level: (pending data)';
$string['dash_badge_exam_level'] = '{$a}';
$string['dash_badge_exam_level_none'] = 'No certificate yet';
$string['dash_badge_exam_level_placeholder'] = 'Exam level: (pending data)';
$string['dash_badge_candidate_id'] = '{$a}';
$string['dash_badge_valid_placeholder'] = 'Status: Valid (placeholder)';

$string['dash_section_pending'] = 'Pending applications';
$string['dash_section_sessions_manage'] = 'Sessions';
$string['dash_section_my_sessions'] = 'My teaching';
$string['dash_section_my_applications'] = 'My applications';
$string['dash_section_my_exams'] = 'My sessions';
$string['dash_home_section_pending'] = 'Needs attention';
$string['dash_home_section_next_teach'] = 'Next teaching session';
$string['dash_home_section_my_apps'] = 'My applications';
$string['dash_home_section_next_exam'] = 'Next exam';
$string['dash_empty_placeholder'] = 'No sessions yet.';
$string['dash_empty_exams'] = 'You have not been added to any certification session yet.';
$string['dash_empty_sessions'] = 'No sessions yet.';
$string['examinee_start_written'] = 'Start written exam';
$string['examinee_start_written_wait'] = 'Available after the session starts';
$string['examinee_view_result'] = 'View results';
$string['examinee_session_instructor'] = 'Instructor {$a}';
$string['result_page_title'] = 'Exam results';
$string['result_section_session'] = 'Session';
$string['result_section_scores'] = 'Results';
$string['result_meta_level'] = 'Level';
$string['result_meta_when'] = 'When';
$string['result_meta_venue'] = 'Venue';
$string['result_meta_instructor'] = 'Instructor';
$string['result_col_item'] = 'Item';
$string['result_col_detail'] = 'Result';
$string['result_item_written'] = 'Written';
$string['result_item_practical'] = 'Practical';
$string['result_item_overall'] = 'Overall';
$string['result_practical_score'] = 'Score {$a}';
$string['result_practical_pending'] = 'Not entered yet';
$string['result_overall_pass'] = 'Pass';
$string['result_overall_fail'] = 'Fail';
$string['result_overall_pending'] = 'Pending';
$string['result_download_cert'] = 'Download certificate';
$string['result_back'] = 'Back to sessions';
$string['error_result_not_enrolled'] = 'You are not on this session roster, so results are unavailable.';
$string['dash_home_section_past_exams'] = 'Past sessions';
$string['dash_section_upcoming_exams'] = 'Upcoming / in progress';
$string['dash_section_past_exams'] = 'Past sessions';
$string['dash_empty_next_exam'] = 'No upcoming exams.';
$string['dash_empty_past_exams'] = 'No past sessions yet.';
$string['dash_pending_later'] = 'Course application review arrives in a later phase.';

$string['dash_action_create_session'] = 'New session';
$string['dash_action_manage_sessions'] = 'All sessions';
$string['dash_action_apply'] = 'Apply to teach';
$string['dash_action_review'] = 'All';
$string['dash_action_review_apps'] = 'Review applications';
$string['dash_action_temp_enrol'] = 'Add students';
$string['dash_action_practical'] = 'Enter scores';
$string['dash_action_roster'] = 'Roster';
$string['dash_action_all_exams'] = 'All sessions';
$string['dash_action_cert_batch'] = 'Batch download certificates';
$string['dash_pending_count'] = '{$a} pending';
$string['dash_empty_applications'] = 'No applications yet.';
$string['dash_app_line'] = '{$a->level} · {$a->when} · {$a->status}';

$string['cert_page_title'] = 'My certificates';
$string['cert_page_intro'] = '';
$string['cert_empty'] = 'No certificate records yet.';
$string['cert_section_instructor'] = 'Instructor recognition';
$string['cert_section_exam'] = 'Exam certificates';
$string['cert_instructor_none'] = 'You have no instructor level recognition yet. Ask an admin to set it under Instructor level authorisation.';
$string['cert_instructor_level_item'] = 'Instructor · {$a}';
$string['cert_instructor_status_line'] = 'Status: {$a}';
$string['cert_instr_status_valid'] = 'Valid';
$string['cert_instr_status_expired'] = 'Expired';
$string['cert_instr_status_pending'] = 'Pending dates';
$string['cert_exam_pending'] = 'Exam certificates will list mapped-course customcert activities (issued + receivable before View certificate).';
$string['cert_exam_no_plugin'] = 'mod_customcert is not installed; exam certificates cannot be listed.';
$string['cert_exam_empty'] = 'No exam certificates to show (check course mapping, enrolment, and activity availability).';
$string['cert_col_level'] = 'Level';
$string['cert_col_course'] = 'Course';
$string['cert_col_status'] = 'Status';
$string['cert_col_issued'] = 'Issued at';
$string['cert_col_action'] = 'Action';
$string['cert_status_issued'] = 'Issued';
$string['cert_status_receivable'] = 'Receivable (not viewed yet)';
$string['cert_action_view'] = 'View / download';
$string['cert_candidate_id_line'] = 'Candidate ID: {$a}';
$string['cert_instructor_expires'] = 'Expires {$a}';
$string['cert_instructor_validity_months'] = '{$a} months validity';
$string['setting_instr_validity_heading'] = 'Instructor certificate validity (per level)';
$string['setting_instr_validity_desc'] = 'Counted from the later of certificate obtained / renew date. Months may differ by level and apply immediately. If “extend by teaching” is on, last taught date is also included and all instructors are re-evaluated live.';
$string['setting_instr_extend_teaching'] = 'Extend validity when teaching';
$string['setting_instr_extend_teaching_desc'] = 'When enabled: validity starts from the later of obtained / renew / last taught session, and all instructor statuses are re-evaluated immediately. When off, only obtained / renew dates count.';
$string['cert_renewal_deadline_label'] = 'Renewal deadline: ';
$string['cert_renewal_deadline_urgent'] = 'Update immediately';
$string['cert_renewal_deadline_until'] = '{$a}';
$string['cert_renewal_deadline_pending'] = 'Add obtained / renew date';
$string['cert_extend_teaching_on'] = '“Extend by teaching” is enabled (last taught date is included).';
$string['setting_instr_validity_months'] = '{$a}: validity (months)';
$string['setting_instr_validity_months_desc'] = 'Positive integer months; default 12.';
$string['instructor_levels_validity_hint'] = 'Current per-level validity (change under Instructor certificate validity):';
$string['instructor_levels_validity_item'] = '{$a->level}: {$a->months} months';
$string['dash_badge_instructor_level_status'] = '{$a->level}';
$string['task_refresh_instructor_expiry'] = 'Certification: summarise instructor certificate expiry';
$string['error_app_instructor_expired'] = 'Your instructor validity has expired. Please renew your instructor qualification before applying or scheduling courses.';
$string['error_session_instructor_expired'] = 'Your instructor validity has expired. Please renew your instructor qualification before assigning or scheduling courses.';
$string['error_app_no_qualified_level'] = 'You have no instructor qualification yet. Ask an administrator to set it under Instructor level authorisation before applying.';
$string['roster_csv_username'] = 'Account';
$string['roster_csv_fullname'] = 'Name';
$string['roster_csv_password'] = 'Default password';
$string['roster_csv_institution'] = 'Institution';
$string['roster_export_login_hint'] = 'Includes Moodle login username, name, default password (Tmai + candidate ID), and institution.';
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
$string['session_status_open'] = 'In progress';
$string['session_status_ended'] = 'Ended';
$string['session_status_closed'] = 'Ended';
$string['session_status_hint'] = 'Becomes Ended automatically when end time passes. Only admins can reopen. Delete removes data permanently.';
$string['session_enrol_count'] = 'Examinees';
$string['session_roster'] = 'Roster';
$string['session_end'] = 'Mark as ended';
$string['session_ended'] = 'Session marked as ended.';
$string['session_reopen'] = 'Reopen';
$string['session_reopened'] = 'Session reopened (if end time had passed, it was extended by one day for adjustment).';
$string['session_delete'] = 'Delete session';
$string['session_deleted'] = 'Session and roster deleted.';
$string['session_delete_confirm'] = 'Delete this session? Roster and session data will be permanently removed and instructors will no longer see it.';
$string['session_close'] = 'Mark as ended';
$string['session_closed'] = 'Session marked as ended.';
$string['session_list_hint'] = 'After end time, status becomes Ended: instructors still see roster/scores but cannot add examinees. Delete removes data (admin only).';
$string['session_ended_no_enrol_hint'] = 'This session has ended. You cannot add examinees (manual or batch). Roster and scores remain available.';
$string['session_ended_no_enrol_hint_short'] = 'Session ended — cannot add examinees.';
$string['error_session_ended_no_enrol'] = 'This session has ended; examinees can no longer be added.';
$string['session_saved'] = 'Session saved.';
$string['session_empty'] = 'No sessions yet. Create one to get started.';

$string['enrol_add_heading'] = 'Add examinee';
$string['enrol_add_examinee'] = 'Add to session';
$string['enrol_empty'] = 'Use Add examinees for single or batch upload.';
$string['enrol_cancel'] = 'Remove';
$string['enrol_cancelled'] = 'Examinee removed from session.';
$string['enrol_remove_confirm_title'] = 'Confirm remove examinee';
$string['enrol_remove_user_line'] = 'Examinee: {$a->name}（account {$a->username} / {$a->email}）';
$string['enrol_remove_will_delete'] = 'This examinee has never logged in. Continuing will remove them from this session and permanently delete the Moodle account. Continue?';
$string['enrol_remove_keep_account'] = 'Continuing will remove them from this session roster only — the Moodle account will be kept.';
$string['enrol_remove_will_release_id'] = 'This is a first certification participation with no quiz/practical records (on-site QR/rollcall does not count). The candidate ID will be released and can be reissued later.';
$string['enrol_remove_will_restore_password'] = 'Their password will be restored to the value from before they were added to a certification roster.';
$string['enrol_remove_continue'] = 'Continue remove';
$string['enrol_removed_deleted_user'] = 'Removed {$a->name} from the session and deleted unused account {$a->username}.';
$string['enrol_removed_keep_user'] = 'Removed {$a->name} from the session. Account {$a->username} was kept.';
$string['enrol_removed_released_id'] = 'Candidate ID was released.';
$string['enrol_removed_restored_password'] = 'Password was restored.';
$string['enrol_added_created'] = 'Created Moodle account and added {$a} to the session.';
$string['enrol_added_linked'] = 'Linked existing account {$a} and added to the session (password reset).';
$string['enrol_added_linked_keep_auth'] = 'Linked existing account {$a} and added to the session (LDAP/external login — password unchanged; use company credentials).';
$string['enrol_initial_password'] = 'Login password: {$a}';
$string['enrol_password_hint'] = 'Adding an examinee resets the password to Tmai + candidate ID. The plaintext password is shown on success; you can also download the roster sheet.';
$string['enrol_no_course_hint'] = 'This session has no linked Moodle course — roster is stored in the plugin only (no course enrol sync). If you already mapped the level to a course in settings, refresh this page; the current mapping will be applied automatically.';

$string['nav_venues'] = 'Venue management';
$string['nav_instructor_levels'] = 'Instructor level authorization';
$string['setting_coursemap_heading'] = 'Level-to-course mapping';
$string['setting_coursemap_desc'] = 'Session creation no longer selects Moodle course manually. The system auto-links by certification level using this mapping.';
$string['setting_coursemap_one'] = '{$a} mapped Moodle course';
$string['setting_coursemap_instructor'] = 'Instructor zone Moodle course';
$string['setting_coursemap_instructor_desc'] = 'Only users with the instructor identity are enrolled here. Not used for examinee roster unlock.';
$string['setting_coursemap_access_heading'] = 'Course entry unlock rules';
$string['setting_coursemap_access_desc'] = 'Examinees unlock a level course when added to that level\'s session roster. Instructors unlock all mapped level courses plus the instructor zone. Existing Moodle enrolments are not bulk-removed (no retrospective purge). Leaving the last roster for a level may unenrol that level course for non-instructors only. Auto-hiding mapped courses is deferred (learners outside the plugin roster may still need access).';

$string['session_name_prefix'] = '[';
$string['session_name_suffix'] = ' Certification Course';
$string['session_name_auto_hint'] = 'Name is auto-generated from selected level.';
$string['session_name_fixed_rule'] = 'Session name is fixed as "[Level] Certification Course". Use venue and date fields below.';
$string['session_venue_none'] = '?X Select level first ?X';
$string['error_session_venue_required'] = 'Please select a venue.';
$string['error_session_venue_level_mismatch'] = 'Selected venue is not authorized for this level or is inactive.';
$string['error_session_instructor_required'] = 'Please select an instructor.';
$string['error_session_instructor_level_mismatch'] = 'This instructor is not qualified for this level yet. Complete level authorisation first.';
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

$string['instructor_levels_intro'] = 'Check levels each instructor may teach (basic / intermediate / advanced may all be held). Session forms only list instructors authorised for that level. Recognition appears on the instructor My certificates page.';
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
$string['app_apply_intro'] = 'Choose a level you are authorised to teach, then pick venue and schedule. Add examinees later from the session roster after approval.';
$string['app_submit'] = 'Submit application';
$string['app_submit_success'] = 'Application #{$a} submitted and waiting for review.';
$string['app_applicant_note'] = 'Note to reviewers (optional)';
$string['app_applicant_note_short'] = 'Applicant note';
$string['app_manager_note'] = 'Reviewer note (optional)';
$string['app_manager_note_short'] = 'Reviewer note';
$string['app_note_empty'] = 'No note from the applicant.';
$string['app_roster_hint_label'] = 'Examinee list';
$string['app_roster_hint'] = 'Optional CSV roster; you will preview it before submit. Accounts and candidate IDs are created when the application is approved.';
$string['app_submit_learners'] = 'Draft roster: {$a} examinee(s) attached.';
$string['app_preview_title'] = 'Confirm application';
$string['app_preview_session'] = 'Course details';
$string['app_preview_roster'] = 'Roster preview';
$string['app_preview_roster_count'] = '{$a} people';
$string['app_preview_confirm_hint'] = 'Check the count and names, then submit. The application will wait for review.';
$string['app_preview_confirm'] = 'Looks good — submit application';
$string['app_preview_back'] = 'Go back and edit';
$string['app_review_title'] = 'Review course applications';
$string['app_status'] = 'Status';
$string['app_status_pending'] = 'Pending';
$string['app_status_approved'] = 'Approved';
$string['app_status_rejected'] = 'Rejected';
$string['app_col_id'] = 'ID';
$string['app_col_applicant'] = 'Applicant';
$string['app_col_session'] = 'Session';
$string['app_col_learners'] = 'Roster';
$string['app_empty_list'] = 'No applications in this filter.';
$string['app_action_approve'] = 'Approve';
$string['app_action_reject'] = 'Reject';
$string['app_action_delete'] = 'Delete';
$string['app_status_deleted'] = 'Removed from applicant view';
$string['app_delete_confirm'] = 'Hide this application from the applicant? The session (if any) is not affected. Admins will still see this row.';
$string['app_deleted'] = 'Application hidden from the applicant.';
$string['app_confirm_approve'] = 'Confirm approve';
$string['app_confirm_reject'] = 'Confirm reject';
$string['app_approve_success'] = 'Approved. Session #{$a} created.';
$string['app_approve_attendance_skipped'] = 'Attendance slot was skipped because this level has no mapped attendance activity.';
$string['app_reject_success'] = 'Application rejected.';
$string['error_app_venue_required'] = 'Please select a venue.';
$string['error_app_venue_invalid'] = 'Selected venue does not match this level or is inactive. Check the venue’s certification level and that it is active; after changing level, re-select the venue.';
$string['error_app_instructor_not_qualified'] = 'Selected instructor is not authorised for this level.';
$string['error_app_applicant_not_qualified'] = 'You are not authorised to apply for this certification level.';
$string['error_app_course_unmapped'] = 'This level has no mapped Moodle course. Ask an administrator to set Level-to-course mapping.';
$string['error_app_not_pending'] = 'This application is not pending.';
$string['error_app_not_found'] = 'Application not found.';
$string['error_app_delete_pending'] = 'Pending applications cannot be deleted. Approve or reject first.';
$string['error_app_already_deleted'] = 'This application was already removed from the applicant view.';

$string['candidate_id'] = 'Candidate ID';
$string['enrol_candidate_id'] = 'Candidate ID: {$a}';
$string['batch_upload_heading'] = 'Batch upload examinees';
$string['batch_csv_lastname'] = 'lastname';
$string['batch_csv_firstname'] = 'firstname';
$string['batch_csv_email'] = 'email';
$string['batch_csv_institution'] = 'institution';
$string['batch_upload_intro'] = 'Upload the public CSV template (lastname, firstname, email, institution). New accounts use username = candidate ID. Preview classifies each row, then confirm to write.';
$string['batch_upload_intro_short'] = 'CSV columns: lastname, firstname, email, institution. Username = candidate ID; password resets to Tmai + ID on write. Confirm preview before writing.';
$string['batch_upload_file'] = 'Examinee CSV';
$string['batch_download_template'] = 'Download CSV template';
$string['batch_preview'] = 'Preview upload';
$string['batch_preview_title'] = 'Upload preview';
$string['batch_preview_summary'] = 'Total {$a->total}: new {$a->new} · issue ID {$a->issue} · keep ID {$a->keep}';
$string['batch_preview_total'] = '{$a} people';
$string['batch_preview_new'] = 'New {$a}';
$string['batch_preview_keep'] = 'Existing ID {$a}';
$string['batch_scenario'] = 'Scenario';
$string['batch_scenario_new'] = 'New examinee';
$string['batch_scenario_issue_id'] = 'Issue ID';
$string['batch_scenario_keep_id'] = 'Keep ID';
$string['batch_scenario_prereq_ok'] = 'Eligible (previous level passed)';
$string['batch_scenario_prereq_fail'] = 'Not eligible';
$string['batch_candidate_pending'] = 'Assign on confirm';
$string['batch_confirm_commit'] = 'Confirm write';
$string['batch_commit_result'] = 'Batch done: {$a->ok} ok, {$a->failed} failed.';
$string['batch_commit_skipped'] = 'Skipped {$a} not eligible.';
$string['batch_preview_eligible'] = 'Eligible {$a}';
$string['batch_preview_blocked'] = 'Blocked {$a}';
$string['batch_upload_intro_prereq'] = 'Intermediate/advanced never create accounts. Lookup by email and require previous-level pass (written if required + practical: plugin score or 〔參〕 on mapped attendance). Password resets to Tmai + ID on write. Only eligible rows are written after confirm.';
$string['batch_password_reset_hint'] = 'On confirm, eligible examinees have their password reset to Tmai + candidate ID. Download the roster sheet for full login credentials.';
$string['batch_commit_passwords_note'] = 'Passwords were reset to the default rule.';
$string['batch_commit_keep_auth_note'] = 'External-auth accounts (e.g. LDAP) were not password-reset; use company credentials for check-in.';
$string['batch_commit_password_more'] = '(plus {$a} more — download the roster sheet)';
$string['prereq_single_hint'] = 'For intermediate/advanced, enter the examinee email first. No new account is created; previous-level pass is required.';
$string['prereq_none_eligible'] = 'Nobody in this list meets the prerequisite. Fix the list and upload again.';
$string['prereq_col_reason'] = 'Reason';
$string['prereq_reason_account_not_found'] = 'Account not found';
$string['prereq_reason_written_fail'] = 'Previous level written exam not passed';
$string['prereq_reason_practical_fail'] = 'Previous level practical not passed';
$string['prereq_reason_no_candidate_id'] = 'No candidate ID (complete basic first)';
$string['prereq_reason_prev_level_not_passed'] = 'Previous level not passed';
$string['error_prereq_blocked'] = 'Prerequisite not met: {$a}';
$string['error_prereq_account_not_found'] = 'Account not found; intermediate/advanced cannot create accounts.';
$string['error_prereq_no_candidate_id'] = 'This examinee has no candidate ID.';
$string['error_batch_csv_empty'] = 'CSV file is empty.';
$string['error_batch_csv_header'] = 'CSV header must include lastname (family name), firstname (given name), and email columns.';
$string['error_batch_csv_row_email'] = 'Row {$a}: invalid email.';
$string['error_batch_csv_row_name'] = 'Row {$a}: family name (lastname) and given name (firstname) are required.';
$string['error_batch_csv_row_dup'] = 'Duplicate email in file: {$a}';
$string['error_batch_csv_has_errors'] = 'CSV has errors: {$a}';
$string['error_batch_preview_expired'] = 'Preview expired. Please upload the CSV again.';
$string['error_candidate_allocate_failed'] = 'Could not allocate a candidate ID. Please retry.';
$string['error_candidate_id_in_use'] = 'Candidate ID {$a} is already bound to another active account. Ask an administrator to check candidate bindings.';
$string['batch_commit_first_error'] = '({$a->email}: {$a->error})';
$string['error_candidate_year_full'] = 'Candidate ID serial for ROC year {$a} is exhausted.';

$string['roster_export_login_csv'] = 'Download login list';
$string['roster_export_login_hint'] = 'Includes Moodle login username, name, default password (Tmai + candidate ID), and institution.';
$string['roster_back'] = 'Back';
$string['roster_back_list'] = 'Back to roster';
$string['enrol_empty_title'] = 'No examinees yet';
$string['enrol_mode_single'] = 'Add one';
$string['enrol_mode_batch'] = 'Batch upload';
$string['enrol_single_preview_hint'] = 'Preview the scenario (new / existing ID, etc.) first, then confirm to write and reset the password.';
$string['enrol_no_course_hint_short'] = 'No mapped Moodle course; roster is stored in the plugin only.';

$string['setting_assess_heading'] = 'Assessment rules (written + practical)';
$string['setting_assess_desc'] = 'Per certification level: optional written quiz threshold, practical pass score, and optional attendance status id overrides.';
$string['setting_written_enabled'] = '{$a}: require written exam';
$string['setting_written_enabled_desc'] = 'When enabled, overall pass needs written + practical. When disabled, only practical applies.';
$string['setting_written_quiz'] = '{$a}: written quiz activity';
$string['setting_written_quiz_none'] = '— Not mapped —';
$string['setting_written_pass'] = '{$a}: written pass score';
$string['setting_written_pass_desc'] = 'Minimum Moodle quiz grade to pass (same scale as the quiz grade item).';
$string['setting_practical_pass'] = '{$a}: practical pass score';
$string['setting_practical_pass_desc'] = 'Practical score must be greater than or equal to this value to pass (default 90).';
$string['setting_attendance_status_heading'] = 'Attendance status overrides (optional)';
$string['setting_attendance_status_desc'] = 'Leave 0 to auto-detect 〔參〕/Present and 〔缺〕/Absent from the mapped attendance activity. Set a status id only if auto-detect fails.';
$string['setting_attendance_statusid_present'] = 'Override status id for Present 〔參〕';
$string['setting_attendance_statusid_present_desc'] = 'attendance_statuses.id (0 = auto)';
$string['setting_attendance_statusid_absent'] = 'Override status id for Absent 〔缺〕';
$string['setting_attendance_statusid_absent_desc'] = 'attendance_statuses.id (0 = auto)';

$string['practical_page_title'] = 'Enter scores';
$string['practical_section_title'] = 'Examinee scores';
$string['practical_threshold_label'] = 'Pass threshold: {$a}';
$string['practical_threshold_short'] = 'Pass {$a}';
$string['practical_threshold_rule'] = 'Practical ≥ {$a} to pass';
$string['practical_written_hint'] = 'This level also requires a written exam. Written results are read from the mapped Moodle quiz; enter practical scores below.';
$string['practical_written_hint_short'] = 'Written scores come from the Moodle quiz; enter practical scores then save.';
$string['practical_needs_written'] = 'Written required';
$string['practical_score'] = 'Practical score';
$string['practical_result'] = 'Practical result';
$string['practical_result_short'] = 'Practical';
$string['practical_pass'] = 'Pass';
$string['practical_fail'] = 'Fail';
$string['practical_pending'] = 'Empty';
$string['practical_save'] = 'Save scores';
$string['practical_save_result'] = 'Saved: {$a->ok} ok, {$a->fail} failed.';
$string['practical_attendance_partial'] = '{$a} row(s) saved in plugin but attendance sync was skipped (check attendance mapping).';
$string['practical_blank_skip'] = 'Blank rows are not saved';
$string['practical_empty_hint'] = 'Add examinees on the roster before entering scores.';
$string['practical_goto_roster'] = 'Go to roster';
$string['written_result'] = 'Written result';
$string['written_result_short'] = 'Written';
$string['written_not_required'] = 'No written exam';
$string['written_no_score'] = 'No quiz grade yet';
$string['written_no_score_short'] = 'No grade yet';
$string['written_pass_with_score'] = 'Pass ({$a})';
$string['written_fail_with_score'] = 'Fail ({$a})';
$string['written_pass_short'] = 'Pass {$a}';
$string['written_fail_short'] = 'Fail {$a}';
$string['error_enrol_cancelled'] = 'This examinee has been removed from the session.';

$string['setting_notify_heading'] = 'Notifications';
$string['setting_notify_desc'] = 'Sent via Moodle email. Delivery failures never block approve, enrol, or score save. No email is sent when a roster removal also deletes the account.';
$string['setting_notify_enabled'] = 'Enable certification notifications';
$string['setting_notify_enabled_desc'] = 'When off, none of the events below send email.';
$string['setting_notify_app_review'] = 'Course application review result';
$string['setting_notify_app_review_desc'] = 'Approve: notify the applicant; if the assigned instructor is different, also notify the instructor. Reject: notify the applicant only.';
$string['setting_notify_new_account'] = 'New examinee account';
$string['setting_notify_new_account_desc'] = 'When a new account is created, email username, default password, and session info.';
$string['setting_notify_enrol_existing'] = 'Existing examinee added to a session';
$string['setting_notify_enrol_existing_desc'] = 'Off by default. When on, also email when an existing account is added to a session.';

$string['notify_app_approved_subject'] = '[{$a->sitename}] Application approved: {$a->name}';
$string['notify_app_approved_body'] = 'Hello,

Your course-opening application has been approved.

Session: {$a->name}
Level: {$a->level}
Time: {$a->when} – {$a->endwhen}
Venue: {$a->venue}

Open the dashboard: {$a->siteurl}/local/tm_certification/index.php
';
$string['notify_instructor_assigned_subject'] = '[{$a->sitename}] You are assigned as instructor: {$a->name}';
$string['notify_instructor_assigned_body'] = 'Hello,

You have been assigned as instructor for the following certification session.

Session: {$a->name}
Level: {$a->level}
Time: {$a->when} – {$a->endwhen}
Venue: {$a->venue}

Open the dashboard: {$a->siteurl}/local/tm_certification/index.php
';
$string['notify_app_rejected_subject'] = '[{$a->sitename}] Application not approved ({$a->level})';
$string['notify_app_rejected_body'] = 'Hello,

Your course-opening application (level: {$a->level}, planned start: {$a->when}) was not approved.

Reviewer note: {$a->note}

Please contact an administrator if you have questions.
';
$string['notify_new_account_subject'] = '[{$a->sitename}] Certification account created: {$a->name}';
$string['notify_new_account_body'] = 'Hello {$a->fullname},

A certification login account was created for you and you were added to a session.

Session: {$a->name}
Time: {$a->when}
Venue: {$a->venue}
Username: {$a->username}
Candidate ID: {$a->candidateid}
Default password: {$a->password}

Please sign in and change the password if needed: {$a->siteurl}/login/index.php
';
$string['notify_enrol_existing_subject'] = '[{$a->sitename}] Added to session: {$a->name}';
$string['notify_enrol_existing_body'] = 'Hello {$a->fullname},

You have been added to the following certification session.

Session: {$a->name}
Time: {$a->when}
Venue: {$a->venue}
Username: {$a->username}
Candidate ID: {$a->candidateid}

Sign in: {$a->siteurl}/login/index.php
';
$string['notify_no_note'] = '(none)';
$string['notify_venue_none'] = '(not set)';
$string['notify_candidate_pending'] = '(pending)';
$string['notify_password_unchanged'] = '(existing / default password rule)';
$string['cert_col_pass'] = 'Pass';
$string['cert_pass_ok'] = 'Passed';
$string['cert_pass_fail'] = 'Not passed';
$string['cert_pass_pending'] = 'Pending';
$string['cert_download_one'] = 'Download certificate';
$string['cert_download_not_passed'] = 'Certificate download is only available after the examinee has passed this session.';
$string['cert_download_unavailable'] = 'No Moodle certificate is available for this examinee yet (not issued / not receivable).';
$string['cert_download_customcert_missing'] = 'mod_customcert is not installed.';
$string['cert_download_course_unmapped'] = 'No Moodle course is mapped for this level.';
$string['cert_batch_title'] = 'Batch certificate download';
$string['cert_batch_session_title'] = 'Session certificate download';
$string['cert_batch_site_title'] = 'Batch download certificates';
$string['cert_batch_pick_level'] = 'Certificate level';
$string['cert_batch_apply_level'] = 'Apply';
$string['cert_batch_level_hint'] = 'Download list is kept per level. Changing level switches to that level’s list.';
$string['cert_batch_mode_session'] = 'Session passed';
$string['cert_batch_mode_search'] = 'Site search';
$string['cert_batch_level'] = 'Level: {$a}';
$string['cert_batch_switch_search'] = 'Switch to site search';
$string['cert_batch_switch_session'] = 'Switch to session passed';
$string['cert_batch_session_passed'] = 'Passed in this session';
$string['cert_batch_count'] = '{$a} selected';
$string['cert_batch_no_passed'] = 'No examinees have passed this session yet.';
$string['cert_batch_make_summary'] = 'Build download summary';
$string['cert_batch_search_heading'] = 'Search and add';
$string['cert_batch_search_placeholder'] = 'Email, candidate ID, or name';
$string['cert_batch_search_hint'] = 'Search site-wide. If a person has certificates at multiple levels, each level is listed separately; Add only includes that level. Changing the query does not clear the list.';
$string['cert_batch_search_empty'] = 'No matching examinees.';
$string['cert_batch_col_action'] = 'Actions';
$string['cert_batch_col_level'] = 'Certificate level';
$string['cert_batch_add'] = 'Add';
$string['cert_batch_already_in_cart'] = 'Already in list';
$string['cert_batch_cart_heading'] = 'Download list';
$string['cert_batch_cart_empty'] = 'List is empty. Search and add by level first.';
$string['cert_batch_clear_cart'] = 'Clear';
$string['cert_batch_remove'] = 'Remove';
$string['cert_batch_summary_heading'] = 'Download summary';
$string['cert_batch_summary_ok'] = 'Downloadable {$a}';
$string['cert_batch_summary_miss'] = 'No certificate {$a}';
$string['cert_batch_summary_hint'] = '"Has certificate" means Moodle customcert is issued or receivable. Missing certificates are skipped; the zip still downloads the rest.';
$string['cert_batch_col_moodle'] = 'Moodle certificate ({$a})';
$string['cert_batch_col_moodle_short'] = 'Moodle certificate';
$string['cert_batch_col_result'] = 'Result';
$string['cert_batch_result_include'] = 'Included in zip';
$string['cert_batch_result_include_n'] = 'Included ({$a} PDF)';
$string['cert_batch_result_skip'] = 'Skipped (does not block batch)';
$string['cert_batch_all_levels'] = 'All levels in one zip';
$string['cert_batch_by_level'] = 'Pick by level';
$string['cert_batch_level_pick_hint'] = 'Search results list each certificate level separately. Only selected levels are packed; unselected levels are not included.';
$string['cert_batch_multilevel_hint'] = 'Each selected examinee is checked for every mapped level (basic / intermediate / advanced). Available certificates are packed together; missing levels are skipped.';
$string['cert_batch_summary_hint_multilevel'] = '"Has certificate" means Moodle customcert is issued or receivable for that level. Missing levels are skipped; the zip still downloads the rest.';
$string['cert_batch_summary_pdfs'] = '{$a} PDF';
$string['cert_batch_summary_users_ok'] = '{$a} with certificate';
$string['cert_batch_summary_users_miss'] = '{$a} with none';
$string['cert_batch_confirm_zip'] = 'Download zip ({$a} PDF)';
$string['cert_batch_zip_empty'] = 'No downloadable certificates in this selection.';
$string['cert_status_issued'] = 'Issued';
$string['cert_status_receivable'] = 'Receivable';
$string['cert_status_none'] = 'Not available';

$string['rollcall_col'] = 'On-site rollcall';
$string['rollcall_status_present'] = 'Present';
$string['rollcall_status_late'] = 'Late';
$string['rollcall_status_absent'] = 'Absent';
$string['rollcall_status_unknown'] = 'Not marked';
$string['rollcall_source_self'] = 'Self check-in';
$string['rollcall_source_staff'] = 'Staff';
$string['rollcall_count_present'] = 'Present {$a}';
$string['rollcall_count_late'] = 'Late {$a}';
$string['rollcall_count_absent'] = 'Absent {$a}';
$string['rollcall_count_unknown'] = 'Not marked {$a}';
$string['rollcall_all_present'] = 'Mark all present';
$string['rollcall_all_present_done'] = 'All rostered examinees marked present.';
$string['rollcall_roster_hint'] = 'On-site rollcall is attendance confirmation and does not write Moodle attendance. Students can scan the session QR; staff can still override here.';
$string['rollcall_qr_title'] = 'Session check-in QR';
$string['rollcall_qr_for_students'] = 'For students to scan';
$string['rollcall_qr_rules_hint'] = 'Rules: before start = present; after start until end = late; after end = self check-in closed. Moodle login required.';
$string['rollcall_qr_present_until'] = 'Present until {$a}';
$string['rollcall_qr_closes_at'] = 'Closes {$a}';
$string['rollcall_qr_scan_heading'] = 'Scan, sign in, then confirm';
$string['rollcall_qr_scan_hint'] = 'This QR is for this session only. Project it in class or print it at the entrance.';
$string['rollcall_qr_download'] = 'Download QR';
$string['rollcall_qr_open_link'] = 'Open check-in link';
$string['rollcall_qr_regen'] = 'Regenerate';
$string['rollcall_qr_regen_hint'] = 'Regenerate invalidates the old link (old QR stops working). Use if the link leaked.';
$string['rollcall_qr_regenerated'] = 'Session check-in QR regenerated; the previous link no longer works.';
$string['rollcall_window_open'] = 'Check-in open';
$string['rollcall_window_closed'] = 'Self check-in closed';
$string['rollcall_checkin_title'] = 'Session check-in';
$string['rollcall_checkin_will_be'] = 'Will record as: {$a}';
$string['rollcall_checkin_now'] = 'Now {$a}';
$string['rollcall_checkin_logged_in'] = 'Signed in';
$string['rollcall_checkin_confirm'] = 'Confirm check-in';
$string['rollcall_checkin_done'] = 'Checked in';
$string['rollcall_checkin_already'] = 'Already checked in';
$string['rollcall_checkin_done_hint'] = 'You can close this page. If the status is wrong, ask the instructor to change it on the roster.';
$string['rollcall_checkin_closed'] = 'Self check-in closed';
$string['rollcall_checkin_closed_hint'] = 'The session has ended. Ask the instructor to mark you on the roster if needed.';
$string['rollcall_checkin_not_roster'] = 'Not on this roster';
$string['rollcall_checkin_not_roster_hint'] = 'Your signed-in account is not on this session roster. Check the account or ask the instructor to add you.';
$string['rollcall_checkin_bad_token'] = 'Invalid check-in link';
$string['rollcall_checkin_bad_token_hint'] = 'The QR may have been regenerated. Ask the instructor for the latest code.';
$string['error_rollcall_status_invalid'] = 'Invalid rollcall status.';
$string['error_rollcall_enrol_cancelled'] = 'This examinee was removed from the roster and cannot be marked.';

