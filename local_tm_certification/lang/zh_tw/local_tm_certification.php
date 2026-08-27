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
$string['setting_coursemap_instructor'] = '認證講師專區 Moodle 課程';
$string['setting_coursemap_instructor_desc'] = '僅具備講師身分者會被選入此課程；不用於學員名冊開通。';
$string['setting_coursemap_access_heading'] = '課程入口開通規則';
$string['setting_coursemap_access_desc'] = '學員加入該等級場次名冊後開通對應課程；講師開通全部等級對應課程與認證講師專區。既有 Moodle 選課不批次清除（不溯及既往）。僅在離開該等級最後一筆有效名冊時，非講師才可能被移出該等級課程。自動隱藏對應課程功能已暫緩（尚有不在外掛名冊內但需進課的學員）。';

$string['dashboard_home_title'] = 'AI協作型機械手臂能力檢定';
$string['dashboard_home_title_manage'] = 'AI協作型機械手臂能力檢定';
$string['dashboard_home_title_instruct'] = 'AI協作型機械手臂能力檢定';
$string['dashboard_home_title_examinee'] = 'AI協作型機械手臂能力檢定';
$string['dashboard_open_full'] = '開啟完整頁面';
$string['dashboard_open_full_link'] = '完整儀表板 →';
$string['dashboard_hidden_admin_hint'] = '檢定儀表板目前對一般使用者關閉。';
$string['dash_view_manage'] = '管理';
$string['dash_view_instruct'] = '講師';
$string['dash_view_examinee'] = '學員';

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
$string['setting_auto_role_examinee'] = '加入檢定名冊時自動指派的學員角色';
$string['setting_auto_role_examinee_desc'] = '講師／管理員手動或批次將學員加入場次時（含新建帳、補發編號、既有編號），自動在系統層級「加上」此 Moodle 角色；不會移除使用者原有角色。選「不自動指派」則略過。';
$string['setting_auto_role_examinee_none'] = '— 不自動指派 —';

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
$string['error_session_instructor_level_mismatch'] = '此講師尚未取得本等級授課資格，請先完成等級授權。';
$string['error_venue_code_required'] = '請填寫考場代碼。';
$string['error_venue_code_duplicated'] = '同一檢定等級下，考場代碼不可重複。';
$string['error_venue_name_required'] = '請填寫考場名稱。';
$string['error_instructor_cert_date_invalid'] = '證書日期格式錯誤，請使用有效日期。';

$string['dash_hello'] = '{$a}，您好';
$string['dash_product_title'] = 'AI協作型機械手臂能力檢定';
$string['dash_manage_title'] = 'AI協作型機械手臂能力檢定';
$string['dash_instruct_title'] = 'AI協作型機械手臂能力檢定';
$string['dash_examinee_title'] = 'AI協作型機械手臂能力檢定';
$string['dash_manage_cover_hint'] = '';
$string['dash_instructor_cover_hint'] = '';
$string['dash_instructor_cover_certified'] = '取得證書日：{$a}';
$string['dash_instructor_cover_renewed'] = 'Renew 證書日：{$a}';
$string['dash_examinee_cover_hint'] = '';
$string['dash_badge_instructor_level'] = '{$a}';
$string['dash_badge_instructor_level_none'] = '尚未採認';
$string['dash_badge_instructor_level_placeholder'] = '尚未採認';
$string['dash_badge_exam_level'] = '{$a}';
$string['dash_badge_exam_level_none'] = '尚無證書';
$string['dash_badge_exam_level_placeholder'] = '尚無證書';
$string['dash_badge_candidate_id'] = '{$a}';
$string['dash_badge_valid_placeholder'] = '有效';
$string['dash_badge_kpi_pending'] = '待審 {$a}';
$string['dash_badge_kpi_open'] = '進行中場次 {$a}';
$string['dash_badge_status_valid'] = '有效';
$string['dash_badge_status_expired'] = '已過期';
$string['dash_badge_status_pending'] = '待補日期';

$string['dash_section_pending'] = '待審申請';
$string['dash_section_sessions_manage'] = '場次';
$string['dash_section_my_sessions'] = '我的授課';
$string['dash_section_my_applications'] = '我的申請';
$string['dash_section_my_exams'] = '我的場次';
$string['dash_home_section_pending'] = '待處理';
$string['dash_home_section_next_teach'] = '下一場授課';
$string['dash_home_section_my_apps'] = '我的申請';
$string['dash_home_section_next_exam'] = '下一場考試';
$string['dash_empty_placeholder'] = '目前沒有場次。';
$string['dash_empty_exams'] = '目前尚未被加入任何檢定場次。';
$string['dash_empty_sessions'] = '目前沒有場次。';
$string['examinee_start_written'] = '開始筆試';
$string['examinee_start_written_wait'] = '場次開始後即可進入筆試';
$string['examinee_session_instructor'] = '講師 {$a}';
$string['dash_pending_later'] = '';

$string['dash_action_create_session'] = '新增場次';
$string['dash_action_manage_sessions'] = '全部場次';
$string['dash_action_apply'] = '申請開課';
$string['dash_action_review'] = '全部';
$string['dash_action_review_apps'] = '審核申請';
$string['dash_action_temp_enrol'] = '新增學員';
$string['dash_action_practical'] = '輸入成績';
$string['dash_action_roster'] = '名單';
$string['dash_action_all_exams'] = '全部場次';
$string['dash_pending_count'] = '待審 {$a}';
$string['dash_empty_applications'] = '目前沒有申請。';
$string['dash_app_line'] = '{$a->level} · {$a->when} · {$a->status}';
$string['dash_kpi_pending'] = '待審申請';
$string['dash_kpi_open'] = '進行中場次';
$string['dash_kpi_week'] = '本週學員';
$string['dash_session_people'] = '{$a} 人';

$string['cert_page_title'] = '我的證書';
$string['cert_page_intro'] = '';
$string['cert_empty'] = '尚無證書紀錄。';
$string['cert_section_instructor'] = '認證講師採認';
$string['cert_section_exam'] = '檢定證書';
$string['cert_instructor_none'] = '您目前沒有講師等級採認。請管理員於「講師等級授權」設定後再查看。';
$string['cert_instructor_level_item'] = '認證講師 · {$a}';
$string['cert_instructor_status_line'] = '效期狀態：{$a}';
$string['cert_instr_status_valid'] = '有效';
$string['cert_instr_status_expired'] = '已過期';
$string['cert_instr_status_pending'] = '待補日期';
$string['cert_exam_pending'] = '檢定證書清單將對照各等級課程的 Moodle 證書活動（含已可領但尚未按 View certificate 者）。';
$string['cert_exam_no_plugin'] = '尚未安裝 mod_customcert，無法列出檢定證書。';
$string['cert_exam_empty'] = '目前沒有可顯示的檢定證書（請確認已對照課程、已選課，且活動條件已達成）。';
$string['cert_col_level'] = '等級';
$string['cert_col_course'] = '課程';
$string['cert_col_status'] = '狀態';
$string['cert_col_issued'] = '核發時間';
$string['cert_col_action'] = '操作';
$string['cert_status_issued'] = '已核發';
$string['cert_status_receivable'] = '可領取（尚未按 View）';
$string['cert_action_view'] = '檢視／下載證書';
$string['cert_candidate_id_line'] = '考生編號：{$a}';
$string['cert_instructor_expires'] = '到期日 {$a}';
$string['cert_instructor_validity_months'] = '效期 {$a} 個月';
$string['setting_instr_validity_heading'] = '認證講師效期（依等級）';
$string['setting_instr_validity_desc'] = '自「取得證書日／Renew 日」（取較晚者）起算，各等級效期月數可不同。儲存後立即套用到所有講師。若開啟「授課自動延長」，最後授課日也會納入起算並即時重新判定有效／過期。';
$string['setting_instr_extend_teaching'] = '授課自動延長效期';
$string['setting_instr_extend_teaching_desc'] = '勾選後：以取得／Renew／最後授課日中較晚者起算效期，並立即追溯重算所有講師狀態。取消勾選則僅看取得／Renew 日。';
$string['cert_renewal_deadline_label'] = '更新效期期限：';
$string['cert_renewal_deadline_urgent'] = '需立即更新';
$string['cert_renewal_deadline_until'] = '{$a}';
$string['cert_renewal_deadline_pending'] = '待補取得／Renew 日期';
$string['cert_extend_teaching_on'] = '目前已啟用「授課自動延長效期」（最後授課日會納入計算）。';
$string['setting_instr_validity_months'] = '{$a}：效期（月）';
$string['setting_instr_validity_months_desc'] = '正整數月數，預設 12。';
$string['instructor_levels_validity_hint'] = '目前各等級效期設定（於「認證講師效期」調整）：';
$string['instructor_levels_validity_item'] = '{$a->level} {$a->months} 個月';
$string['dash_badge_instructor_level_status'] = '{$a->level}';
$string['task_refresh_instructor_expiry'] = '檢定：統計認證講師證書效期';
$string['error_app_instructor_expired'] = '您的講師效期已過，請立即更新講師資格後再安排課程。';
$string['error_session_instructor_expired'] = '您的講師效期已過，請立即更新講師資格後再安排課程。';
$string['error_app_no_qualified_level'] = '您目前沒有可用的講師資格，請先請管理員於「講師等級授權」完成採認後再申請開課。';
$string['roster_csv_username'] = '帳號';
$string['roster_csv_fullname'] = '姓名';
$string['roster_csv_password'] = '預設密碼';
$string['roster_csv_institution'] = '所屬單位';

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
$string['session_status_open'] = '進行中';
$string['session_status_ended'] = '已結束';
$string['session_status_closed'] = '已結束';
$string['session_status_hint'] = '結束時間一到會自動變「已結束」。僅管理員可手動重新開啟；刪除後講師完全看不到。';
$string['session_enrol_count'] = '學員數';
$string['session_roster'] = '學員名單';
$string['session_end'] = '標示已結束';
$string['session_ended'] = '場次已標示為已結束。';
$string['session_reopen'] = '重新開啟';
$string['session_reopened'] = '場次已重新開啟（若結束時間已過，已自動延長一天供調整）。';
$string['session_delete'] = '刪除場次';
$string['session_deleted'] = '場次及其名冊已刪除。';
$string['session_delete_confirm'] = '確定刪除此場次？名冊與場次資料將永久移除，講師將無法再看到。';
$string['session_close'] = '標示已結束';
$string['session_closed'] = '場次已標示為已結束。';
$string['session_list_hint'] = '結束時間到後自動變成「已結束」：講師仍可見、可看名單／成績，但不能再新增學員。刪除＝資料移除，僅管理員可操作。';
$string['session_ended_no_enrol_hint'] = '此場次已結束，無法再新增學員（手動或批次）。仍可查看名單與輸入成績。';
$string['session_ended_no_enrol_hint_short'] = '場次已結束，無法再新增學員。';
$string['error_session_ended_no_enrol'] = '此場次已結束，無法再加入學員。';
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

$string['instructor_levels_intro'] = '請為講師勾選可授課的檢定等級（初級／中級／高級可同時持有）。建立或申請場次時，只會顯示符合該場次等級的講師。採認結果會顯示於講師的「我的證書」。';
$string['instructor_levels_saved'] = '講師等級授權已更新。';
$string['instructor_levels_empty'] = '目前沒有可用的講師名單。請先在設定頁指派「講師身分角色」，並確認該角色已在系統層級指派給使用者。';
$string['instructor_certifiedat'] = '取得證書日';
$string['instructor_renewedat'] = 'Renew 證書日';

$string['enrol_add_heading'] = '新增學員';
$string['enrol_add_examinee'] = '加入場次';
$string['enrol_empty'] = '點「新增學員」單筆加入或批次上傳。';
$string['enrol_cancel'] = '移除';
$string['enrol_cancelled'] = '已自場次移除該學員。';
$string['enrol_remove_confirm_title'] = '確認移除學員';
$string['enrol_remove_user_line'] = '學員：{$a->name}（帳號 {$a->username}／{$a->email}）';
$string['enrol_remove_will_delete'] = '此學員尚未登入過。繼續將會自本場次移除，並刪除該 Moodle 帳號。是否繼續？';
$string['enrol_remove_keep_account'] = '繼續將僅自本場次移除名單，不會刪除 Moodle 帳號。';
$string['enrol_remove_will_release_id'] = '此為既有帳號的首次且未使用之檢定參與。考生編號將釋出，之後可再核發。';
$string['enrol_remove_will_restore_password'] = '密碼將還原為加入檢定名冊前的設定。';
$string['enrol_remove_continue'] = '確認繼續移除';
$string['enrol_removed_deleted_user'] = '已自場次移除 {$a->name}，並刪除未使用帳號 {$a->username}。';
$string['enrol_removed_keep_user'] = '已自場次移除 {$a->name}。帳號 {$a->username} 已保留。';
$string['enrol_removed_released_id'] = '已釋出考生編號。';
$string['enrol_removed_restored_password'] = '已還原密碼。';
$string['enrol_added_created'] = '已建立 Moodle 帳號並將 {$a} 加入場次。';
$string['enrol_added_linked'] = '已連結既有帳號 {$a} 並加入場次（密碼已重設）。';
$string['enrol_initial_password'] = '登入密碼：{$a}';
$string['enrol_password_hint'] = '加入時會將密碼重設為「Tmai」+考生編號；成功後會顯示明文密碼，亦可下載名單取得。';
$string['enrol_no_course_hint'] = '此場次尚未對應 Moodle 課程——僅寫入外掛名冊（不會同步課程選課）。若您已在設定中對照等級→課程，請重新整理本頁；系統會自動帶入目前對照。';

$string['nav_applications'] = '開課申請審核';
$string['setting_attendance_map_heading'] = '等級→出缺席活動對照';
$string['setting_attendance_map_desc'] = '請選擇課程中「既有」的出缺席活動（course module）。外掛不會自動新建出缺席活動；核准開課時只會在選定活動內新增上課時段。';
$string['setting_attendance_map_one'] = '{$a} 對照出缺席活動';
$string['setting_attendance_none'] = '— 尚未對照 —';

$string['app_apply_title'] = '申請開課';
$string['app_apply_intro'] = '請選擇您有授權的檢定等級、考場與時段後送出申請。學員名單請於場次核准後，至該場次「新增學員」再加入。';
$string['app_submit'] = '送出申請';
$string['app_submit_success'] = '申請 #{$a} 已送出，等待審核。';
$string['app_applicant_note'] = '給審核者的備註（選填）';
$string['app_applicant_note_short'] = '申請備註';
$string['app_manager_note'] = '審核備註（選填）';
$string['app_manager_note_short'] = '審核備註';
$string['app_note_empty'] = '申請人未填寫備註。';
$string['app_roster_hint_label'] = '學員名單';
$string['app_roster_hint'] = '可選填 CSV 學員名單；上傳後會先預覽，確認無誤才送出。帳號與考生編號會在申請「核准」時正式建立。';
$string['app_submit_learners'] = '已附帶草稿名單 {$a} 人。';
$string['app_preview_title'] = '確認申請內容';
$string['app_preview_session'] = '課程資訊';
$string['app_preview_roster'] = '學員名單預覽';
$string['app_preview_roster_count'] = '共 {$a} 人';
$string['app_preview_confirm_hint'] = '請確認人數與姓名無誤後再送出；送出後進入待審。';
$string['app_preview_confirm'] = '確認無誤，送出申請';
$string['app_preview_back'] = '返回修改';
$string['app_review_title'] = '開課申請審核';
$string['app_status'] = '狀態';
$string['app_status_pending'] = '待審';
$string['app_status_approved'] = '已核准';
$string['app_status_rejected'] = '已駁回';
$string['app_col_id'] = '編號';
$string['app_col_applicant'] = '申請人';
$string['app_col_session'] = '場次';
$string['app_col_learners'] = '名單';
$string['app_empty_list'] = '此篩選條件下尚無申請。';
$string['app_action_approve'] = '核准';
$string['app_action_reject'] = '駁回';
$string['app_action_delete'] = '刪除';
$string['app_status_deleted'] = '已自申請人畫面移除';
$string['app_delete_confirm'] = '將此申請自申請人畫面隱藏？若已核准，場次不受影響。管理員仍可在審核列表看到此筆。';
$string['app_deleted'] = '已自申請人畫面隱藏此申請。';
$string['app_confirm_approve'] = '確認核准';
$string['app_confirm_reject'] = '確認駁回';
$string['app_approve_success'] = '已核准，並建立場次 #{$a}。';
$string['app_approve_attendance_skipped'] = '因該等級尚未對照出缺席活動，已略過建立上課時段。';
$string['app_reject_success'] = '申請已駁回。';
$string['error_app_venue_required'] = '請選擇考場。';
$string['error_app_venue_invalid'] = '所選考場與此等級不符或未啟用。請在考場維護確認該考場的「檢定等級」是否為您選的等級，並已勾選啟用；換等級後請重新選擇考場。';
$string['error_app_instructor_not_qualified'] = '此講師尚未取得本等級授課資格，請先完成等級授權。';
$string['error_app_applicant_not_qualified'] = '您尚未取得本等級申請資格，請先請管理員完成講師等級授權。';
$string['error_app_course_unmapped'] = '此等級尚未對應 Moodle 課程，請先請管理員設定等級→課程對照。';
$string['error_app_not_pending'] = '此申請不是待審狀態。';
$string['error_app_not_found'] = '找不到此申請。';
$string['error_app_delete_pending'] = '待審申請不可刪除，請先核准或駁回。';
$string['error_app_already_deleted'] = '此申請已自申請人畫面移除。';

$string['candidate_id'] = '考生編號';
$string['enrol_candidate_id'] = '考生編號：{$a}';
$string['batch_upload_heading'] = '批次上傳學員';
$string['batch_csv_lastname'] = '姓';
$string['batch_csv_firstname'] = '名';
$string['batch_csv_email'] = '信箱';
$string['batch_csv_institution'] = '學校名';
$string['batch_upload_intro'] = '請上傳公版 CSV（欄位：姓、名、信箱、學校名）。新帳號的「帳號」=考生編號。預覽分三種情境，確認後才寫入。';
$string['batch_upload_intro_short'] = 'CSV 欄位：姓、名、信箱、學校名。帳號＝考生編號；寫入時密碼重設為「Tmai」+編號。確認預覽後才寫入。';
$string['batch_upload_file'] = '學員 CSV';
$string['batch_download_template'] = '下載 CSV 公版';
$string['batch_preview'] = '預覽上傳';
$string['batch_preview_title'] = '上傳預覽';
$string['batch_preview_summary'] = '共 {$a->total} 人：全新 {$a->new} · 補發編號 {$a->issue} · 既有編號 {$a->keep}';
$string['batch_preview_total'] = '共 {$a} 人';
$string['batch_preview_new'] = '全新 {$a}';
$string['batch_preview_keep'] = '既有編號 {$a}';
$string['batch_scenario'] = '情境';
$string['batch_scenario_new'] = '全新學員';
$string['batch_scenario_issue_id'] = '補發編號';
$string['batch_scenario_keep_id'] = '既有編號';
$string['batch_scenario_prereq_ok'] = '可加入（已通過前一等級）';
$string['batch_scenario_prereq_fail'] = '不符合條件';
$string['batch_candidate_pending'] = '確認後核發';
$string['batch_confirm_commit'] = '確認寫入';
$string['batch_commit_result'] = '批次完成：成功 {$a->ok}、失敗 {$a->failed}。';
$string['batch_commit_skipped'] = '已略過不符合條件 {$a} 人。';
$string['batch_preview_eligible'] = '可加入 {$a}';
$string['batch_preview_blocked'] = '不符合 {$a}';
$string['batch_upload_intro_prereq'] = '中／高級不新建帳：以 Email 查找既有學員，並檢查是否已通過前一等級（筆試若必要＋術科：外掛成績或對照出缺席之〔參〕）。寫入時密碼重設為「Tmai」+編號。預覽確認後只寫入符合條件者。';
$string['batch_password_reset_hint'] = '確認寫入後，可加入者密碼皆會重設為「Tmai」+考生編號；請下載學員名單取得完整帳密。';
$string['batch_commit_passwords_note'] = '密碼已重設為預設規則。';
$string['batch_commit_password_more'] = '（另有 {$a} 人請下載名單查看）';
$string['prereq_single_hint'] = '中／高級請輸入學員 Email（優先查找）。系統不新建帳，並檢查是否已通過前一等級。';
$string['prereq_none_eligible'] = '名單中無人符合前置條件，無法送出。請修正後重新上傳。';
$string['prereq_col_reason'] = '不符合原因';
$string['prereq_reason_account_not_found'] = '查無此帳號';
$string['prereq_reason_written_fail'] = '前一等級筆試未通過';
$string['prereq_reason_practical_fail'] = '前一等級術科未通過';
$string['prereq_reason_no_candidate_id'] = '尚無考生編號（請先完成初級）';
$string['prereq_reason_prev_level_not_passed'] = '前一等級尚未通過';
$string['error_prereq_blocked'] = '不符合前置條件：{$a}';
$string['error_prereq_account_not_found'] = '查無此帳號，中／高級不可新建帳。';
$string['error_prereq_no_candidate_id'] = '此學員尚無考生編號。';
$string['error_batch_csv_empty'] = 'CSV 檔案是空的。';
$string['error_batch_csv_header'] = 'CSV 表頭必須包含「姓」「名」「信箱」欄位（亦接受英文 lastname／firstname／email）。';
$string['error_batch_csv_row_email'] = '第 {$a} 列：信箱無效。';
$string['error_batch_csv_row_name'] = '第 {$a} 列：姓與名為必填。';
$string['error_batch_csv_row_dup'] = '檔案中 Email 重複：{$a}';
$string['error_batch_csv_has_errors'] = 'CSV 有錯誤：{$a}';
$string['error_batch_preview_expired'] = '預覽已過期，請重新上傳 CSV。';
$string['error_candidate_allocate_failed'] = '無法核發考生編號，請重試。';
$string['error_candidate_id_in_use'] = '考生編號 {$a} 已被其他有效帳號使用，無法綁定。請聯絡管理員確認編號資料。';
$string['batch_commit_first_error'] = '（{$a->email}：{$a->error}）';
$string['error_candidate_year_full'] = '民國 {$a} 年考生編號流水號已滿（999）。';

$string['roster_export_login_csv'] = '下載登入名單';
$string['roster_export_login_hint'] = '含 Moodle 登入帳號、姓名、預設密碼（Tmai+考生編號）、所屬單位。';
$string['roster_back'] = '返回';
$string['roster_back_list'] = '返回名單';
$string['enrol_empty_title'] = '尚無學員';
$string['enrol_mode_single'] = '單筆加入';
$string['enrol_mode_batch'] = '批次上傳';
$string['enrol_single_preview_hint'] = '填寫後先預覽情境（全新／既有編號等），確認後才寫入並重設密碼。';
$string['enrol_no_course_hint_short'] = '此場次尚未對應 Moodle 課程，僅寫入外掛名冊。';

$string['setting_assess_heading'] = '評量規則（筆試＋術科）';
$string['setting_assess_desc'] = '依檢定等級設定：是否需要筆試、筆試／術科及格分數，以及出缺席狀態 id 覆寫（選用）。';
$string['setting_written_enabled'] = '{$a}：需要筆試';
$string['setting_written_enabled_desc'] = '啟用後，總通過需筆試＋術科皆過；關閉則僅看術科。';
$string['setting_written_quiz'] = '{$a}：筆試測驗活動';
$string['setting_written_quiz_none'] = '— 尚未對照 —';
$string['setting_written_pass'] = '{$a}：筆試及格分數';
$string['setting_written_pass_desc'] = 'Moodle 測驗成績需達此分數（與測驗成績項目同分制）。';
$string['setting_practical_pass'] = '{$a}：術科及格分數';
$string['setting_practical_pass_desc'] = '術科分數需大於等於此值才算通過（預設 90）。';
$string['setting_attendance_status_heading'] = '出缺席狀態覆寫（選用）';
$string['setting_attendance_status_desc'] = '預設 0：自動辨識〔參〕／Present 與〔缺〕／Absent。僅在自動辨識失敗時填入 attendance_statuses.id。';
$string['setting_attendance_statusid_present'] = '〔參〕狀態 id 覆寫';
$string['setting_attendance_statusid_present_desc'] = 'attendance_statuses.id（0＝自動）';
$string['setting_attendance_statusid_absent'] = '〔缺〕狀態 id 覆寫';
$string['setting_attendance_statusid_absent_desc'] = 'attendance_statuses.id（0＝自動）';

$string['practical_page_title'] = '輸入成績';
$string['practical_section_title'] = '學員成績';
$string['practical_threshold_label'] = '及格門檻：{$a}';
$string['practical_threshold_short'] = '及格 {$a}';
$string['practical_threshold_rule'] = '術科 ≥ {$a} 為通過';
$string['practical_written_hint'] = '此等級另需筆試。筆試結果由對照的 Moodle 測驗讀取；請於下方輸入術科分數。';
$string['practical_written_hint_short'] = '筆試由 Moodle 測驗讀取；請輸入術科分數後儲存。';
$string['practical_needs_written'] = '需筆試';
$string['practical_score'] = '術科分數';
$string['practical_result'] = '術科結果';
$string['practical_result_short'] = '術科';
$string['practical_pass'] = '通過';
$string['practical_fail'] = '未通過';
$string['practical_pending'] = '未輸入';
$string['practical_save'] = '儲存成績';
$string['practical_save_result'] = '已儲存：成功 {$a->ok}、失敗 {$a->fail}。';
$string['practical_attendance_partial'] = '其中 {$a} 筆已存外掛，但出缺席同步略過（請檢查出缺席對照）。';
$string['practical_blank_skip'] = '空白列不儲存';
$string['practical_empty_hint'] = '請先至名單新增學員後再輸入成績。';
$string['practical_goto_roster'] = '前往名單';
$string['written_result'] = '筆試結果';
$string['written_result_short'] = '筆試';
$string['written_not_required'] = '不需筆試';
$string['written_no_score'] = '尚無測驗成績';
$string['written_no_score_short'] = '尚無成績';
$string['written_pass_with_score'] = '通過（{$a}）';
$string['written_fail_with_score'] = '未通過（{$a}）';
$string['written_pass_short'] = '通過 {$a}';
$string['written_fail_short'] = '未通過 {$a}';
$string['error_enrol_cancelled'] = '此學員已自場次移除。';

$string['setting_notify_heading'] = '通知';
$string['setting_notify_desc'] = '以 Moodle 郵件寄出。寄信失敗不阻斷審核、建帳或存成績。名單移除並刪除帳號時不寄信。';
$string['setting_notify_enabled'] = '啟用檢定通知';
$string['setting_notify_enabled_desc'] = '關閉後，下列各項皆不寄信。';
$string['setting_notify_app_review'] = '開課申請審核結果';
$string['setting_notify_app_review_desc'] = '核准：通知原申請者；若開課講師不同人，另通知講師（指派）。駁回：只通知申請者。';
$string['setting_notify_new_account'] = '新建學員帳號';
$string['setting_notify_new_account_desc'] = '新建立帳號時寄送帳號、預設密碼與場次資訊。';
$string['setting_notify_enrol_existing'] = '既有學員加入場次';
$string['setting_notify_enrol_existing_desc'] = '預設關閉。開啟後，將既有帳號加入場次時也寄通知。';

$string['notify_app_approved_subject'] = '【{$a->sitename}】開課申請已核准：{$a->name}';
$string['notify_app_approved_body'] = '您好，

您的開課申請已核准。

場次：{$a->name}
等級：{$a->level}
時間：{$a->when} – {$a->endwhen}
考場：{$a->venue}

請登入系統查看：{$a->siteurl}/local/tm_certification/index.php
';
$string['notify_instructor_assigned_subject'] = '【{$a->sitename}】您已被指派為檢定場次講師：{$a->name}';
$string['notify_instructor_assigned_body'] = '您好，

您已被指派為下列檢定場次的授課講師。

場次：{$a->name}
等級：{$a->level}
時間：{$a->when} – {$a->endwhen}
考場：{$a->venue}

請登入系統查看：{$a->siteurl}/local/tm_certification/index.php
';
$string['notify_app_rejected_subject'] = '【{$a->sitename}】開課申請未通過（{$a->level}）';
$string['notify_app_rejected_body'] = '您好，

您申請的開課（等級：{$a->level}，預定開始：{$a->when}）未獲核准。

審核備註：{$a->note}

如有疑問請聯絡管理員。
';
$string['notify_new_account_subject'] = '【{$a->sitename}】檢定帳號已建立：{$a->name}';
$string['notify_new_account_body'] = '您好 {$a->fullname}，

已為您建立檢定登入帳號，並加入場次。

場次：{$a->name}
時間：{$a->when}
考場：{$a->venue}
帳號：{$a->username}
考生編號：{$a->candidateid}
預設密碼：{$a->password}

請盡快登入並視需要修改密碼：{$a->siteurl}/login/index.php
';
$string['notify_enrol_existing_subject'] = '【{$a->sitename}】您已加入檢定場次：{$a->name}';
$string['notify_enrol_existing_body'] = '您好 {$a->fullname}，

您已被加入下列檢定場次。

場次：{$a->name}
時間：{$a->when}
考場：{$a->venue}
帳號：{$a->username}
考生編號：{$a->candidateid}

登入：{$a->siteurl}/login/index.php
';
$string['notify_no_note'] = '（無）';
$string['notify_venue_none'] = '（未指定）';
$string['notify_candidate_pending'] = '（待核發）';
$string['notify_password_unchanged'] = '（沿用原密碼／預設規則）';
