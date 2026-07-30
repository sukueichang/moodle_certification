<?php
/**
 * Traditional Chinese language strings for local_tm_certification.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'TM 檢定管理';
$string['privacy:metadata'] = 'TM 檢定管理外掛會為授權使用者儲存檢定場次與證書相關資料。';

$string['tm_certification:manage'] = '管理檢定設定與場次';
$string['tm_certification:approve'] = '審核檢定開課申請';
$string['tm_certification:instruct'] = '作為檢定講師';
$string['tm_certification:examinee'] = '作為檢定學員';
$string['tm_certification:viewdashboard'] = '檢視檢定儀表板';

$string['nav_main'] = '檢定管理';
$string['nav_dashboard'] = '檢定儀表板';
$string['nav_my_certificates'] = '我的證書';
$string['nav_permissions'] = '角色指派';
$string['nav_plugin_about'] = '外掛版本';
$string['nav_sessions'] = '檢定場次';
$string['nav_venues'] = '考場維護';
$string['nav_instructor_levels'] = '講師等級授權';
$string['nav_quickentry'] = '檢定快捷入口';

$string['setting_plugin_version'] = '已安裝版本';
$string['setting_plugin_version_desc'] = '目前 local_tm_certification 版本：<strong>{$a}</strong>';

$string['setting_dashboard_heading'] = 'Dashboard 顯示';
$string['setting_dashboard_where'] = '儀表板會出現在：① Moodle<strong>網站首頁</strong>／<strong>我的首頁</strong>區塊；② 左側導覽「檢定管理」；③ 直接開啟 <code>/local/tm_certification/index.php</code>。若仍看不到，請至「邏輯身分 ↔ Moodle 角色」指派角色，並清除快取。';
$string['setting_dashboard_enabled'] = '啟用檢定儀表板';
$string['setting_dashboard_enabled_desc'] = '關閉後，多數使用者看不到儀表板入口（管理者仍可進入外掛設定）。';
$string['setting_dashboard_show_manage'] = '顯示管理儀表板視圖';
$string['setting_dashboard_show_manage_desc'] = '允許具備管理／審核身分者看到管理儀表板。';
$string['setting_dashboard_show_instruct'] = '顯示講師儀表板視圖';
$string['setting_dashboard_show_instruct_desc'] = '允許講師儀表板。';
$string['setting_dashboard_show_examinee'] = '顯示學員儀表板視圖';
$string['setting_dashboard_show_examinee_desc'] = '允許學員儀表板。';
$string['setting_coursemap_heading'] = '檢定等級對應課程';
$string['setting_coursemap_desc'] = '建立場次時不再手動選 Moodle 課程，系統依檢定等級自動帶入此處對應。';
$string['setting_coursemap_one'] = '{$a} 對應 Moodle 課程';

$string['dashboard_home_title'] = '檢定儀表板';
$string['dashboard_open_full'] = '開啟完整儀表板';
$string['dashboard_hidden_admin_hint'] = '檢定儀表板目前對一般使用者關閉。';
$string['dash_view_manage'] = '視圖：管理';
$string['dash_view_instruct'] = '視圖：講師';
$string['dash_view_examinee'] = '視圖：學員';

$string['setting_roles_heading'] = '邏輯身分 ↔ Moodle 角色';
$string['setting_roles_intro'] = '將站台上的 Moodle 角色對應到邏輯身分。請勿在程式寫死角色短名——在此調整對應即可。使用者若已具備對應 capability，亦視為擁有該身分。';
$string['setting_roles_manage'] = '管理身分角色';
$string['setting_roles_manage_desc'] = '視為檢定管理者的角色（設定、手動開課等）。';
$string['setting_roles_approve'] = '審核身分角色';
$string['setting_roles_approve_desc'] = '可審核開課申請的角色（管理者永遠可審核）。';
$string['setting_roles_instruct'] = '講師身分角色';
$string['setting_roles_instruct_desc'] = '視為檢定講師的角色。';
$string['setting_roles_examinee'] = '學員身分角色';
$string['setting_roles_examinee_desc'] = '視為檢定學員的角色。';
$string['setting_roles_view_dashboard'] = '額外可開啟儀表板的角色';
$string['setting_roles_view_dashboard_desc'] = '除上述身分外，額外允許開啟儀表板入口的角色（選用）。';

$string['error_view_access_denied'] = '您沒有使用檢定外掛的權限。';
$string['error_dashboard_disabled'] = '管理員已關閉檢定儀表板。';
$string['error_dashboard_view_none'] = '您的帳號目前沒有可用的儀表板視圖。請管理員指派檢定身分，或開啟對應的儀表板顯示。';
$string['error_session_name_required'] = '請填寫場次名稱。';
$string['error_session_level_invalid'] = '檢定等級無效。';
$string['error_session_time_invalid'] = '結束時間必須晚於開始時間。';
$string['error_session_course_invalid'] = '找不到對應的 Moodle 課程。';
$string['error_batch_email_invalid'] = '請輸入有效的 Email。';
$string['error_batch_name_required'] = '請填寫名字與姓氏。';
$string['error_batch_user_invalid'] = '建立 Moodle 帳號失敗。';
$string['error_moodle_manual_enrol_disabled'] = '本站未啟用手動選課（manual enrol）。';
$string['error_moodle_manual_instance_missing'] = '無法為此課程建立手動選課實體。';
$string['error_moodle_manual_role_not_configured'] = '手動選課尚未設定預設角色。';
$string['error_moodle_enrol_verify_failed'] = '無法確認 Moodle 課程選課結果。';
$string['error_session_venue_required'] = '請選擇考場地點。';
$string['error_session_venue_level_mismatch'] = '所選考場不符合此檢定等級，或已停用。';
$string['error_session_instructor_required'] = '請指定授課講師。';
$string['error_session_instructor_level_mismatch'] = '所選講師尚未授權此檢定等級。';
$string['error_venue_code_required'] = '請填寫考場代碼。';
$string['error_venue_code_duplicated'] = '同一檢定等級下，考場代碼不可重複。';
$string['error_venue_name_required'] = '請填寫考場名稱。';
$string['error_instructor_cert_date_invalid'] = '證書日期格式錯誤，請使用有效日期。';

$string['dash_hello'] = '您好，{$a}';
$string['dash_manage_title'] = '檢定管理中心';
$string['dash_manage_cover_hint'] = '專注於審核與場次管理。此視圖不強調個人證書封面。';
$string['dash_instructor_cover_hint'] = '封面將顯示認證講師證書的「最後通過」等級（後續階段接資料）。';
$string['dash_examinee_cover_hint'] = '封面將顯示檢定證書的「最後通過」等級與考生編號（後續階段接資料）。學員證書無期限。';
$string['dash_badge_instructor_level_placeholder'] = '認證講師等級：（資料待接）';
$string['dash_badge_exam_level_placeholder'] = '檢定等級：（資料待接）';
$string['dash_badge_valid_placeholder'] = '狀態：有效（佔位）';

$string['dash_section_pending'] = '開課審核專區';
$string['dash_section_sessions_manage'] = '已開場次';
$string['dash_section_my_sessions'] = '我的授課場次';
$string['dash_section_my_applications'] = '我的開課申請';
$string['dash_section_my_exams'] = '已報名檢定場次';
$string['dash_empty_placeholder'] = '尚無資料。後續階段實作業務功能後會顯示於此。';
$string['dash_empty_sessions'] = '尚無開放中的場次。';
$string['dash_pending_later'] = '開課申請審核將於後續階段提供。';

$string['dash_action_create_session'] = '手動建立場次';
$string['dash_action_manage_sessions'] = '場次管理';
$string['dash_action_apply'] = '申請開課';
$string['dash_action_review'] = '審核開課申請';
$string['dash_action_temp_enrol'] = '安插／新增學員';
$string['dash_action_practical'] = '輸入術科成績（即將推出）';
$string['dash_action_roster'] = '學員名單';
$string['dash_pending_count'] = '待審 {$a} 筆';
$string['dash_empty_applications'] = '尚無申請紀錄。';
$string['dash_app_line'] = '#{$a->id} · {$a->level} · {$a->when} · {$a->status}';

$string['cert_page_title'] = '我的證書';
$string['cert_page_intro'] = '此處將列出完整證書歷史。講師可見檢定與認證講師證書；學員僅見檢定證書。';
$string['cert_empty'] = '尚無證書紀錄。';

$string['perm_page_intro'] = '將 Moodle 角色指派給各邏輯身分。設定立即生效。亦可至「網站管理 → 使用者 → 權限 → 定義角色」直接授予對應 capability。';
$string['perm_saved'] = '角色對應已儲存。';
$string['perm_save'] = '儲存角色對應';
$string['perm_open_moodle_roles'] = '開啟 Moodle 角色指派';

$string['level_basic'] = '初級';
$string['level_intermediate'] = '中級';
$string['level_advanced'] = '高級';

$string['session_create'] = '建立場次';
$string['session_edit'] = '編輯場次';
$string['session_name'] = '場次名稱';
$string['session_name_prefix'] = '[';
$string['session_name_suffix'] = '檢定課程';
$string['session_name_auto_hint'] = '系統自動命名：依檢定等級產生。';
$string['session_name_fixed_rule'] = '場次名稱固定為「[等級]檢定課程」。請改由下方選擇考場與日期。';
$string['session_level'] = '檢定等級';
$string['session_venue'] = '考場地點';
$string['session_venue_none'] = '— 請先選擇等級後再選考場 —';
$string['session_time'] = '日期／時間';
$string['session_start'] = '開始';
$string['session_end'] = '結束';
$string['session_course'] = '對應 Moodle 課程';
$string['session_course_help'] = '加入此場次的學員會以手動選課方式報名此課程。若先不選，僅寫入外掛名冊。';
$string['session_course_none'] = '— 尚未對應 —';
$string['session_instructor'] = '指定授課講師';
$string['session_instructor_none'] = '— 尚未指定 —';
$string['session_status'] = '狀態';
$string['session_status_open'] = '開放';
$string['session_status_closed'] = '關閉';
$string['session_enrol_count'] = '學員數';
$string['session_roster'] = '學員名單';
$string['session_close'] = '關閉場次';
$string['session_closed'] = '場次已關閉。';
$string['session_saved'] = '場次已儲存。';
$string['session_empty'] = '尚無場次，請先建立一筆。';

$string['venue_name'] = '考場名稱';
$string['venue_code'] = '考場代碼';
$string['venue_active'] = '啟用';
$string['venue_passedat'] = '通過日期';
$string['venue_add'] = '新增／編輯考場';
$string['venue_save'] = '儲存考場';
$string['venue_saved'] = '考場設定已儲存。';
$string['venue_empty'] = '尚未建立考場。';

$string['instructor_levels_intro'] = '請為講師勾選可授課的檢定等級。建立場次時只會顯示符合該等級的講師。';
$string['instructor_levels_saved'] = '講師等級授權已更新。';
$string['instructor_levels_empty'] = '目前沒有可用的講師名單。請先在設定頁指派「講師身分角色」，並確認該角色已在系統層級指派給使用者。';
$string['instructor_certifiedat'] = '取得證書日';
$string['instructor_renewedat'] = 'Renew 證書日';

$string['enrol_add_heading'] = '新增學員';
$string['enrol_add_examinee'] = '加入場次';
$string['enrol_empty'] = '此場次尚無學員。';
$string['enrol_cancel'] = '移除';
$string['enrol_cancelled'] = '已自場次移除該學員。';
$string['enrol_added_created'] = '已建立 Moodle 帳號並將 {$a} 加入場次。';
$string['enrol_added_linked'] = '已連結既有帳號 {$a} 並加入場次。';
$string['enrol_initial_password'] = '初始密碼：{$a}';
$string['enrol_no_course_hint'] = '此場次尚未對應 Moodle 課程——僅寫入外掛名冊（不會同步課程選課）。';

$string['nav_applications'] = '開課申請審核';
$string['setting_attendance_map_heading'] = '等級→出缺席活動對照';
$string['setting_attendance_map_desc'] = '請選擇課程中「既有」的出缺席活動（course module）。外掛不會自動新建出缺席活動；核准開課時只會在選定活動內新增上課時段。';
$string['setting_attendance_map_one'] = '{$a} 對照出缺席活動';
$string['setting_attendance_none'] = '— 尚未對照 —';

$string['app_apply_title'] = '申請開課';
$string['app_apply_intro'] = '請選擇您有授權的檢定等級、考場與時段。申請時批次上傳學員將於下一階段提供；核准後可於場次名單安插學員。';
$string['app_submit'] = '送出申請';
$string['app_submit_success'] = '申請 #{$a} 已送出，等待審核。';
$string['app_applicant_note'] = '給審核者的備註（選填）';
$string['app_manager_note'] = '審核備註（選填）';
$string['app_roster_hint_label'] = '學員名單';
$string['app_roster_hint'] = '申請時批次上傳將於 P4 提供。目前請於核准後至場次名單新增學員。';
$string['app_review_title'] = '開課申請審核';
$string['app_status'] = '狀態';
$string['app_status_pending'] = '待審';
$string['app_status_approved'] = '已核准';
$string['app_status_rejected'] = '已駁回';
$string['app_col_id'] = '編號';
$string['app_col_applicant'] = '申請人';
$string['app_empty_list'] = '此篩選條件下尚無申請。';
$string['app_action_approve'] = '核准';
$string['app_action_reject'] = '駁回';
$string['app_confirm_approve'] = '確認核准';
$string['app_confirm_reject'] = '確認駁回';
$string['app_approve_success'] = '已核准，並建立場次 #{$a}。';
$string['app_approve_attendance_skipped'] = '因該等級尚未對照出缺席活動，已略過建立上課時段。';
$string['app_reject_success'] = '申請已駁回。';
$string['error_app_venue_required'] = '請選擇考場。';
$string['error_app_venue_invalid'] = '所選考場與此等級不符或未啟用。';
$string['error_app_instructor_not_qualified'] = '所選講師未獲此等級授權。';
$string['error_app_applicant_not_qualified'] = '您未獲此等級的申請授權。';
$string['error_app_course_unmapped'] = '此等級尚未對應 Moodle 課程，請先請管理員設定等級→課程對照。';
$string['error_app_not_pending'] = '此申請不是待審狀態。';
$string['error_app_not_found'] = '找不到此申請。';
$string['error_app_no_qualified_level'] = '您目前沒有任何講師等級授權，請先請管理員於「講師等級授權」設定。';
