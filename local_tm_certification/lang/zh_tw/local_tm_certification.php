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

$string['setting_plugin_version'] = '已安裝版本';
$string['setting_plugin_version_desc'] = '目前 local_tm_certification 版本：<strong>{$a}</strong>';

$string['setting_dashboard_heading'] = 'Dashboard 顯示';
$string['setting_dashboard_where'] = '儀表板會出現在：① Moodle<strong>網站首頁</strong>／<strong>我的首頁</strong>區塊；② 左側導覽「檢定管理」；③ 直接開啟 <code>/local/tm_certification/index.php</code>。若仍看不到，請至「邏輯身分 ↔ Moodle 角色」指派角色，並清除快取。';
$string['setting_dashboard_enabled'] = '啟用檢定儀表板';
$string['setting_dashboard_enabled_desc'] = '關閉後，多數使用者看不到儀表板入口（管理者仍可進入外掛設定）。';
$string['dashboard_home_title'] = '檢定儀表板';
$string['dashboard_open_full'] = '開啟完整儀表板';
$string['dashboard_hidden_admin_hint'] = '檢定儀表板目前對一般使用者關閉。';
$string['dash_view_manage'] = '視圖：管理';
$string['dash_view_instruct'] = '視圖：講師';
$string['dash_view_examinee'] = '視圖：學員';
$string['setting_dashboard_show_manage'] = '顯示管理儀表板視圖';
$string['setting_dashboard_show_manage_desc'] = '允許具備管理／審核身分者看到管理儀表板。';
$string['setting_dashboard_show_instruct'] = '顯示講師儀表板視圖';
$string['setting_dashboard_show_instruct_desc'] = '允許講師儀表板。';
$string['setting_dashboard_show_examinee'] = '顯示學員儀表板視圖';
$string['setting_dashboard_show_examinee_desc'] = '允許學員儀表板。';

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

$string['dash_action_create_session'] = '手動建立場次（即將推出）';
$string['dash_action_apply'] = '申請開課（即將推出）';
$string['dash_action_temp_enrol'] = '臨時安插學員（即將推出）';
$string['dash_action_practical'] = '輸入術科成績（即將推出）';
$string['dash_action_roster'] = '學員名單（即將推出）';

$string['cert_page_title'] = '我的證書';
$string['cert_page_intro'] = '此處將列出完整證書歷史。講師可見檢定與認證講師證書；學員僅見檢定證書。';
$string['cert_empty'] = '尚無證書紀錄。';

$string['perm_page_intro'] = '將 Moodle 角色指派給各邏輯身分。設定立即生效。亦可至「網站管理 → 使用者 → 權限 → 定義角色」直接授予對應 capability。';
$string['perm_saved'] = '角色對應已儲存。';
$string['perm_save'] = '儲存角色對應';
$string['perm_open_moodle_roles'] = '開啟 Moodle 角色指派';
