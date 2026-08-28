<?php
/**
 * Simplified Chinese language strings for local_tm_certification.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'TM 检定管理';
$string['privacy:metadata'] = 'TM 检定管理外挂会为授权使用者储存检定场次与证书相关资料。';

$string['tm_certification:manage'] = '管理检定设定与场次';
$string['tm_certification:approve'] = '审核检定开课申请';
$string['tm_certification:instruct'] = '作为检定讲师';
$string['tm_certification:examinee'] = '作为检定学员';
$string['tm_certification:viewdashboard'] = '检视检定仪表板';

$string['nav_main'] = '检定管理';
$string['nav_dashboard'] = '检定仪表板';
$string['nav_my_certificates'] = '我的证书';
$string['nav_permissions'] = '角色指派';
$string['nav_plugin_about'] = '外挂版本';
$string['nav_sessions'] = '检定场次';
$string['nav_venues'] = '考场维护';
$string['nav_instructor_levels'] = '讲师等级授权';
$string['nav_quickentry'] = '检定快捷入口';

$string['setting_plugin_version'] = '已安装版本';
$string['setting_plugin_version_desc'] = '目前 local_tm_certification 版本：<strong>{$a}</strong>';

$string['setting_dashboard_heading'] = 'Dashboard 显示';
$string['setting_dashboard_where'] = '仪表板会出现在：① Moodle<strong>网站首页</strong>／<strong>我的首页</strong>区块；② 左侧导览“检定管理”；③ 直接开启 <code>/local/tm_certification/index.php</code>。若仍看不到，请至“逻辑身份 ↔ Moodle 角色”指派角色，并清除快取。';
$string['setting_dashboard_enabled'] = '启用检定仪表板';
$string['setting_dashboard_enabled_desc'] = '关闭后，多数使用者看不到仪表板入口（管理者仍可进入外挂设定）。';
$string['setting_dashboard_show_manage'] = '显示管理仪表板视图';
$string['setting_dashboard_show_manage_desc'] = '允许具备管理／审核身份者看到管理仪表板。';
$string['setting_dashboard_show_instruct'] = '显示讲师仪表板视图';
$string['setting_dashboard_show_instruct_desc'] = '允许讲师仪表板。';
$string['setting_dashboard_show_examinee'] = '显示学员仪表板视图';
$string['setting_dashboard_show_examinee_desc'] = '允许学员仪表板。';
$string['setting_coursemap_heading'] = '检定等级对应课程';
$string['setting_coursemap_desc'] = '建立场次时不再手动选 Moodle 课程，系统依检定等级自动带入此处对应。';
$string['setting_coursemap_one'] = '{$a} 对应 Moodle 课程';
$string['setting_coursemap_instructor'] = '认证讲师专区 Moodle 课程';
$string['setting_coursemap_instructor_desc'] = '仅具备讲师身份者会被选入此课程；不用于学员名册开通。';
$string['setting_coursemap_access_heading'] = '课程入口开通规则';
$string['setting_coursemap_access_desc'] = '学员加入该等级场次名册后开通对应课程；讲师开通全部等级对应课程与认证讲师专区。既有 Moodle 选课不批量清除（不溯及既往）。仅在离开该等级最后一笔有效名册时，非讲师才可能被移出该等级课程。自动隐藏对应课程功能已暂缓（尚有不在插件名册内但需进课的学员）。';

$string['dashboard_home_title'] = 'AI协作型机械手臂能力检定';
$string['dashboard_home_title_manage'] = 'AI协作型机械手臂能力检定';
$string['dashboard_home_title_instruct'] = 'AI协作型机械手臂能力检定';
$string['dashboard_home_title_examinee'] = 'AI协作型机械手臂能力检定';
$string['dashboard_open_full'] = '开启完整页面';
$string['dashboard_open_full_link'] = '完整仪表板 →';
$string['dashboard_hidden_admin_hint'] = '检定仪表板目前对一般使用者关闭。';
$string['dash_view_manage'] = '管理';
$string['dash_view_instruct'] = '讲师';
$string['dash_view_examinee'] = '学员';

$string['setting_roles_heading'] = '逻辑身份 ↔ Moodle 角色';
$string['setting_roles_intro'] = '将站台上的 Moodle 角色对应到逻辑身份。请勿在程式写死角色短名——在此调整对应即可。使用者若已具备对应 capability，亦视为拥有该身份。';
$string['setting_roles_manage'] = '管理身份角色';
$string['setting_roles_manage_desc'] = '视为检定管理者的角色（设定、手动开课等）。';
$string['setting_roles_approve'] = '审核身份角色';
$string['setting_roles_approve_desc'] = '可审核开课申请的角色（管理者永远可审核）。';
$string['setting_roles_instruct'] = '讲师身份角色';
$string['setting_roles_instruct_desc'] = '视为检定讲师的角色。';
$string['setting_roles_examinee'] = '学员身份角色';
$string['setting_roles_examinee_desc'] = '视为检定学员的角色。';
$string['setting_roles_view_dashboard'] = '额外可开启仪表板的角色';
$string['setting_roles_view_dashboard_desc'] = '除上述身份外，额外允许开启仪表板入口的角色（选用）。';
$string['setting_auto_role_examinee'] = '加入检定名册时自动指派的学员角色';
$string['setting_auto_role_examinee_desc'] = '讲师／管理员手动或批次将学员加入场次时（含新建账、补发编号、既有编号），自动在系统层级“加上”此 Moodle 角色；不会移除使用者原有角色。选“不自动指派”则略过。';
$string['setting_auto_role_examinee_none'] = '— 不自动指派 —';

$string['error_view_access_denied'] = '您没有使用检定外挂的权限。';
$string['error_dashboard_disabled'] = '管理员已关闭检定仪表板。';
$string['error_dashboard_view_none'] = '您的账号目前没有可用的仪表板视图。请管理员指派检定身份，或开启对应的仪表板显示。';
$string['error_session_name_required'] = '请填写场次名称。';
$string['error_session_level_invalid'] = '检定等级无效。';
$string['error_session_time_invalid'] = '结束时间必须晚于开始时间。';
$string['error_session_course_invalid'] = '找不到对应的 Moodle 课程。';
$string['error_batch_email_invalid'] = '请输入有效的 Email。';
$string['error_batch_name_required'] = '请填写名字与姓氏。';
$string['error_batch_user_invalid'] = '建立 Moodle 账号失败。';
$string['error_moodle_manual_enrol_disabled'] = '本站未启用手动选课（manual enrol）。';
$string['error_moodle_manual_instance_missing'] = '无法为此课程建立手动选课实体。';
$string['error_moodle_manual_role_not_configured'] = '手动选课尚未设定预设角色。';
$string['error_moodle_enrol_verify_failed'] = '无法确认 Moodle 课程选课结果。';
$string['error_session_venue_required'] = '请选择考场地点。';
$string['error_session_venue_level_mismatch'] = '所选考场不符合此检定等级，或已停用。';
$string['error_session_instructor_required'] = '请指定授课讲师。';
$string['error_session_instructor_level_mismatch'] = '此讲师尚未取得本等级授课资格，请先完成等级授权。';
$string['error_venue_code_required'] = '请填写考场代码。';
$string['error_venue_code_duplicated'] = '同一检定等级下，考场代码不可重复。';
$string['error_venue_name_required'] = '请填写考场名称。';
$string['error_instructor_cert_date_invalid'] = '证书日期格式错误，请使用有效日期。';

$string['dash_hello'] = '{$a}，您好';
$string['dash_product_title'] = 'AI协作型机械手臂能力检定';
$string['dash_manage_title'] = 'AI协作型机械手臂能力检定';
$string['dash_instruct_title'] = 'AI协作型机械手臂能力检定';
$string['dash_examinee_title'] = 'AI协作型机械手臂能力检定';
$string['dash_manage_cover_hint'] = '';
$string['dash_instructor_cover_hint'] = '';
$string['dash_instructor_cover_certified'] = '取得证书日：{$a}';
$string['dash_instructor_cover_renewed'] = 'Renew 证书日：{$a}';
$string['dash_examinee_cover_hint'] = '';
$string['dash_badge_instructor_level'] = '{$a}';
$string['dash_badge_instructor_level_none'] = '尚未采认';
$string['dash_badge_instructor_level_placeholder'] = '尚未采认';
$string['dash_badge_exam_level'] = '{$a}';
$string['dash_badge_exam_level_none'] = '尚无证书';
$string['dash_badge_exam_level_placeholder'] = '尚无证书';
$string['dash_badge_candidate_id'] = '{$a}';
$string['dash_badge_valid_placeholder'] = '有效';
$string['dash_badge_kpi_pending'] = '待审 {$a}';
$string['dash_badge_kpi_open'] = '进行中场次 {$a}';
$string['dash_badge_status_valid'] = '有效';
$string['dash_badge_status_expired'] = '已过期';
$string['dash_badge_status_pending'] = '待补日期';

$string['dash_section_pending'] = '待审申请';
$string['dash_section_sessions_manage'] = '场次';
$string['dash_section_my_sessions'] = '我的授课';
$string['dash_section_my_applications'] = '我的申请';
$string['dash_section_my_exams'] = '我的场次';
$string['dash_home_section_pending'] = '待处理';
$string['dash_home_section_next_teach'] = '下一场授课';
$string['dash_home_section_my_apps'] = '我的申请';
$string['dash_home_section_next_exam'] = '下一场考试';
$string['dash_empty_placeholder'] = '目前没有场次。';
$string['dash_empty_exams'] = '目前尚未被加入任何检定场次。';
$string['dash_empty_sessions'] = '目前没有场次。';
$string['examinee_start_written'] = '开始笔试';
$string['examinee_start_written_wait'] = '场次开始后即可进入笔试';
$string['examinee_view_result'] = '检视成绩';
$string['examinee_session_instructor'] = '讲师 {$a}';
$string['result_page_title'] = '检定成绩';
$string['result_section_session'] = '场次资讯';
$string['result_section_scores'] = '成绩结果';
$string['result_meta_level'] = '等级';
$string['result_meta_when'] = '时间';
$string['result_meta_venue'] = '考场';
$string['result_meta_instructor'] = '讲师';
$string['result_col_item'] = '项目';
$string['result_col_detail'] = '结果';
$string['result_item_written'] = '笔试';
$string['result_item_practical'] = '术科';
$string['result_item_overall'] = '总结果';
$string['result_practical_score'] = '分数 {$a}';
$string['result_practical_pending'] = '尚未输入';
$string['result_overall_pass'] = '通过';
$string['result_overall_fail'] = '未通过';
$string['result_overall_pending'] = '尚待判定';
$string['result_download_cert'] = '下载检定证书';
$string['result_back'] = '返回场次列表';
$string['error_result_not_enrolled'] = '您不在此场次名册中，无法查看成绩。';
$string['dash_home_section_past_exams'] = '已结束场次';
$string['dash_section_upcoming_exams'] = '进行中／即将到来';
$string['dash_section_past_exams'] = '已结束场次';
$string['dash_empty_next_exam'] = '目前没有即将到来的考试。';
$string['dash_empty_past_exams'] = '尚无已结束的场次。';
$string['dash_pending_later'] = '';

$string['dash_action_create_session'] = '新增场次';
$string['dash_action_manage_sessions'] = '全部场次';
$string['dash_action_apply'] = '申请开课';
$string['dash_action_review'] = '全部';
$string['dash_action_review_apps'] = '审核申请';
$string['dash_action_temp_enrol'] = '新增学员';
$string['dash_action_practical'] = '输入成绩';
$string['dash_action_roster'] = '名单';
$string['dash_action_all_exams'] = '全部场次';
$string['dash_action_cert_batch'] = '证书批次下载';
$string['dash_pending_count'] = '待审 {$a}';
$string['dash_empty_applications'] = '目前没有申请。';
$string['dash_app_line'] = '{$a->level} · {$a->when} · {$a->status}';
$string['dash_kpi_pending'] = '待审申请';
$string['dash_kpi_open'] = '进行中场次';
$string['dash_kpi_week'] = '本周学员';
$string['dash_session_people'] = '{$a} 人';

$string['cert_page_title'] = '我的证书';
$string['cert_page_intro'] = '';
$string['cert_empty'] = '尚无证书纪录。';
$string['cert_section_instructor'] = '认证讲师采认';
$string['cert_section_exam'] = '检定证书';
$string['cert_instructor_none'] = '您目前没有讲师等级采认。请管理员于“讲师等级授权”设定后再查看。';
$string['cert_instructor_level_item'] = '认证讲师 · {$a}';
$string['cert_instructor_status_line'] = '效期状态：{$a}';
$string['cert_instr_status_valid'] = '有效';
$string['cert_instr_status_expired'] = '已过期';
$string['cert_instr_status_pending'] = '待补日期';
$string['cert_exam_pending'] = '检定证书清单将对照各等级课程的 Moodle 证书活动（含已可领但尚未按 View certificate 者）。';
$string['cert_exam_no_plugin'] = '尚未安装 mod_customcert，无法列出检定证书。';
$string['cert_exam_empty'] = '目前没有可显示的检定证书（请确认已对照课程、已选课，且活动条件已达成）。';
$string['cert_col_level'] = '等级';
$string['cert_col_course'] = '课程';
$string['cert_col_status'] = '状态';
$string['cert_col_issued'] = '核发时间';
$string['cert_col_action'] = '操作';
$string['cert_status_issued'] = '已核发';
$string['cert_status_receivable'] = '可领取（尚未按 View）';
$string['cert_action_view'] = '检视／下载证书';
$string['cert_candidate_id_line'] = '考生编号：{$a}';
$string['cert_instructor_expires'] = '到期日 {$a}';
$string['cert_instructor_validity_months'] = '效期 {$a} 个月';
$string['setting_instr_validity_heading'] = '认证讲师效期（依等级）';
$string['setting_instr_validity_desc'] = '自“取得证书日／Renew 日”（取较晚者）起算，各等级效期月数可不同。储存后立即套用到所有讲师。若开启“授课自动延长”，最后授课日也会纳入起算并即时重新判定有效／过期。';
$string['setting_instr_extend_teaching'] = '授课自动延长效期';
$string['setting_instr_extend_teaching_desc'] = '勾选后：以取得／Renew／最后授课日中较晚者起算效期，并立即追溯重算所有讲师状态。取消勾选则仅看取得／Renew 日。';
$string['cert_renewal_deadline_label'] = '更新效期期限：';
$string['cert_renewal_deadline_urgent'] = '需立即更新';
$string['cert_renewal_deadline_until'] = '{$a}';
$string['cert_renewal_deadline_pending'] = '待补取得／Renew 日期';
$string['cert_extend_teaching_on'] = '目前已启用“授课自动延长效期”（最后授课日会纳入计算）。';
$string['setting_instr_validity_months'] = '{$a}：效期（月）';
$string['setting_instr_validity_months_desc'] = '正整数月数，预设 12。';
$string['instructor_levels_validity_hint'] = '目前各等级效期设定（于“认证讲师效期”调整）：';
$string['instructor_levels_validity_item'] = '{$a->level} {$a->months} 个月';
$string['dash_badge_instructor_level_status'] = '{$a->level}';
$string['task_refresh_instructor_expiry'] = '检定：统计认证讲师证书效期';
$string['error_app_instructor_expired'] = '您的讲师效期已过，请立即更新讲师资格后再安排课程。';
$string['error_session_instructor_expired'] = '您的讲师效期已过，请立即更新讲师资格后再安排课程。';
$string['error_app_no_qualified_level'] = '您目前没有可用的讲师资格，请先请管理员于“讲师等级授权”完成采认后再申请开课。';
$string['roster_csv_username'] = '账号';
$string['roster_csv_fullname'] = '姓名';
$string['roster_csv_password'] = '预设密码';
$string['roster_csv_institution'] = '所属单位';

$string['perm_page_intro'] = '将 Moodle 角色指派给各逻辑身份。设定立即生效。亦可至“网站管理 → 使用者 → 权限 → 定义角色”直接授予对应 capability。';
$string['perm_saved'] = '角色对应已储存。';
$string['perm_save'] = '储存角色对应';
$string['perm_open_moodle_roles'] = '开启 Moodle 角色指派';

$string['level_basic'] = '初级';
$string['level_intermediate'] = '中级';
$string['level_advanced'] = '高级';

$string['session_create'] = '建立场次';
$string['session_edit'] = '编辑场次';
$string['session_name'] = '场次名称';
$string['session_name_prefix'] = '[';
$string['session_name_suffix'] = '检定课程';
$string['session_name_auto_hint'] = '系统自动命名：依检定等级产生。';
$string['session_name_fixed_rule'] = '场次名称固定为“[等级]检定课程”。请改由下方选择考场与日期。';
$string['session_level'] = '检定等级';
$string['session_venue'] = '考场地点';
$string['session_venue_none'] = '— 请先选择等级后再选考场 —';
$string['session_time'] = '日期／时间';
$string['session_start'] = '开始';
$string['session_end'] = '结束';
$string['session_course'] = '对应 Moodle 课程';
$string['session_course_help'] = '加入此场次的学员会以手动选课方式报名此课程。若先不选，仅写入外挂名册。';
$string['session_course_none'] = '— 尚未对应 —';
$string['session_instructor'] = '指定授课讲师';
$string['session_instructor_none'] = '— 尚未指定 —';
$string['session_status'] = '状态';
$string['session_status_open'] = '进行中';
$string['session_status_ended'] = '已结束';
$string['session_status_closed'] = '已结束';
$string['session_status_hint'] = '结束时间一到会自动变“已结束”。仅管理员可手动重新开启；删除后讲师完全看不到。';
$string['session_enrol_count'] = '学员数';
$string['session_roster'] = '学员名单';
$string['session_end'] = '标示已结束';
$string['session_ended'] = '场次已标示为已结束。';
$string['session_reopen'] = '重新开启';
$string['session_reopened'] = '场次已重新开启（若结束时间已过，已自动延长一天供调整）。';
$string['session_delete'] = '删除场次';
$string['session_deleted'] = '场次及其名册已删除。';
$string['session_delete_confirm'] = '确定删除此场次？名册与场次资料将永久移除，讲师将无法再看到。';
$string['session_close'] = '标示已结束';
$string['session_closed'] = '场次已标示为已结束。';
$string['session_list_hint'] = '结束时间到后自动变成“已结束”：讲师仍可见、可看名单／成绩，但不能再新增学员。删除＝资料移除，仅管理员可操作。';
$string['session_ended_no_enrol_hint'] = '此场次已结束，无法再新增学员（手动或批次）。仍可查看名单与输入成绩。';
$string['session_ended_no_enrol_hint_short'] = '场次已结束，无法再新增学员。';
$string['error_session_ended_no_enrol'] = '此场次已结束，无法再加入学员。';
$string['session_saved'] = '场次已储存。';
$string['session_empty'] = '尚无场次，请先建立一笔。';

$string['venue_name'] = '考场名称';
$string['venue_code'] = '考场代码';
$string['venue_active'] = '启用';
$string['venue_passedat'] = '通过日期';
$string['venue_add'] = '新增／编辑考场';
$string['venue_save'] = '储存考场';
$string['venue_saved'] = '考场设定已储存。';
$string['venue_empty'] = '尚未建立考场。';

$string['instructor_levels_intro'] = '请为讲师勾选可授课的检定等级（初级／中级／高级可同时持有）。建立或申请场次时，只会显示符合该场次等级的讲师。采认结果会显示于讲师的“我的证书”。';
$string['instructor_levels_saved'] = '讲师等级授权已更新。';
$string['instructor_levels_empty'] = '目前没有可用的讲师名单。请先在设定页指派“讲师身份角色”，并确认该角色已在系统层级指派给使用者。';
$string['instructor_certifiedat'] = '取得证书日';
$string['instructor_renewedat'] = 'Renew 证书日';

$string['enrol_add_heading'] = '新增学员';
$string['enrol_add_examinee'] = '加入场次';
$string['enrol_empty'] = '点“新增学员”单笔加入或批次上传。';
$string['enrol_cancel'] = '移除';
$string['enrol_cancelled'] = '已自场次移除该学员。';
$string['enrol_remove_confirm_title'] = '确认移除学员';
$string['enrol_remove_user_line'] = '学员：{$a->name}（账号 {$a->username}／{$a->email}）';
$string['enrol_remove_will_delete'] = '此学员尚未登入过。继续将会自本场次移除，并删除该 Moodle 账号。是否继续？';
$string['enrol_remove_keep_account'] = '继续将仅自本场次移除名单，不会删除 Moodle 账号。';
$string['enrol_remove_will_release_id'] = '此为首次且尚无笔试／术科纪录之检定参与（现场 QR／点名不计）。考生编号将释出，之后可再核发。';
$string['enrol_remove_will_restore_password'] = '密码将还原为加入检定名册前的设定。';
$string['enrol_remove_continue'] = '确认继续移除';
$string['enrol_removed_deleted_user'] = '已自场次移除 {$a->name}，并删除未使用账号 {$a->username}。';
$string['enrol_removed_keep_user'] = '已自场次移除 {$a->name}。账号 {$a->username} 已保留。';
$string['enrol_removed_released_id'] = '已释出考生编号。';
$string['enrol_removed_restored_password'] = '已还原密码。';
$string['enrol_added_created'] = '已建立 Moodle 账号并将 {$a} 加入场次。';
$string['enrol_added_linked'] = '已连结既有账号 {$a} 并加入场次（密码已重设）。';
$string['enrol_added_linked_keep_auth'] = '已连结既有账号 {$a} 并加入场次（LDAP／外部登录，密码未改，请用原公司帐密）。';
$string['enrol_initial_password'] = '登入密码：{$a}';
$string['enrol_password_hint'] = '加入时会将密码重设为“Tmai”+考生编号；成功后会显示明文密码，亦可下载名单取得。';
$string['enrol_no_course_hint'] = '此场次尚未对应 Moodle 课程——仅写入外挂名册（不会同步课程选课）。若您已在设定中对照等级→课程，请重新整理本页；系统会自动带入目前对照。';

$string['nav_applications'] = '开课申请审核';
$string['setting_attendance_map_heading'] = '等级→出缺席活动对照';
$string['setting_attendance_map_desc'] = '请选择课程中“既有”的出缺席活动（course module）。外挂不会自动新建出缺席活动；核准开课时只会在选定活动内新增上课时段。';
$string['setting_attendance_map_one'] = '{$a} 对照出缺席活动';
$string['setting_attendance_none'] = '— 尚未对照 —';

$string['app_apply_title'] = '申请开课';
$string['app_apply_intro'] = '请选择您有授权的检定等级、考场与时段后送出申请。学员名单请于场次核准后，至该场次“新增学员”再加入。';
$string['app_submit'] = '送出申请';
$string['app_submit_success'] = '申请 #{$a} 已送出，等待审核。';
$string['app_applicant_note'] = '给审核者的备注（选填）';
$string['app_applicant_note_short'] = '申请备注';
$string['app_manager_note'] = '审核备注（选填）';
$string['app_manager_note_short'] = '审核备注';
$string['app_note_empty'] = '申请人未填写备注。';
$string['app_roster_hint_label'] = '学员名单';
$string['app_roster_hint'] = '可选填 CSV 学员名单；上传后会先预览，确认无误才送出。账号与考生编号会在申请“核准”时正式建立。';
$string['app_submit_learners'] = '已附带草稿名单 {$a} 人。';
$string['app_preview_title'] = '确认申请内容';
$string['app_preview_session'] = '课程资讯';
$string['app_preview_roster'] = '学员名单预览';
$string['app_preview_roster_count'] = '共 {$a} 人';
$string['app_preview_confirm_hint'] = '请确认人数与姓名无误后再送出；送出后进入待审。';
$string['app_preview_confirm'] = '确认无误，送出申请';
$string['app_preview_back'] = '返回修改';
$string['app_review_title'] = '开课申请审核';
$string['app_status'] = '状态';
$string['app_status_pending'] = '待审';
$string['app_status_approved'] = '已核准';
$string['app_status_rejected'] = '已驳回';
$string['app_col_id'] = '编号';
$string['app_col_applicant'] = '申请人';
$string['app_col_session'] = '场次';
$string['app_col_learners'] = '名单';
$string['app_empty_list'] = '此筛选条件下尚无申请。';
$string['app_action_approve'] = '核准';
$string['app_action_reject'] = '驳回';
$string['app_action_delete'] = '删除';
$string['app_status_deleted'] = '已自申请人画面移除';
$string['app_delete_confirm'] = '将此申请自申请人画面隐藏？若已核准，场次不受影响。管理员仍可在审核列表看到此笔。';
$string['app_deleted'] = '已自申请人画面隐藏此申请。';
$string['app_confirm_approve'] = '确认核准';
$string['app_confirm_reject'] = '确认驳回';
$string['app_approve_success'] = '已核准，并建立场次 #{$a}。';
$string['app_approve_attendance_skipped'] = '因该等级尚未对照出缺席活动，已略过建立上课时段。';
$string['app_reject_success'] = '申请已驳回。';
$string['error_app_venue_required'] = '请选择考场。';
$string['error_app_venue_invalid'] = '所选考场与此等级不符或未启用。请在考场维护确认该考场的“检定等级”是否为您选的等级，并已勾选启用；换等级后请重新选择考场。';
$string['error_app_instructor_not_qualified'] = '此讲师尚未取得本等级授课资格，请先完成等级授权。';
$string['error_app_applicant_not_qualified'] = '您尚未取得本等级申请资格，请先请管理员完成讲师等级授权。';
$string['error_app_course_unmapped'] = '此等级尚未对应 Moodle 课程，请先请管理员设定等级→课程对照。';
$string['error_app_not_pending'] = '此申请不是待审状态。';
$string['error_app_not_found'] = '找不到此申请。';
$string['error_app_delete_pending'] = '待审申请不可删除，请先核准或驳回。';
$string['error_app_already_deleted'] = '此申请已自申请人画面移除。';

$string['candidate_id'] = '考生编号';
$string['enrol_candidate_id'] = '考生编号：{$a}';
$string['batch_upload_heading'] = '批次上传学员';
$string['batch_csv_lastname'] = '姓';
$string['batch_csv_firstname'] = '名';
$string['batch_csv_email'] = '邮箱';
$string['batch_csv_institution'] = '学校名';
$string['batch_upload_intro'] = '请上传公版 CSV（字段：姓、名、邮箱、学校名）。新账号的“账号”=考生编号。预览分三种情境，确认后才写入。';
$string['batch_upload_intro_short'] = 'CSV 字段：姓、名、邮箱、学校名。账号＝考生编号；写入时密码重设为“Tmai”+编号。确认预览后才写入。';
$string['batch_upload_file'] = '学员 CSV';
$string['batch_download_template'] = '下载 CSV 公版';
$string['batch_preview'] = '预览上传';
$string['batch_preview_title'] = '上传预览';
$string['batch_preview_summary'] = '共 {$a->total} 人：全新 {$a->new} · 补发编号 {$a->issue} · 既有编号 {$a->keep}';
$string['batch_preview_total'] = '共 {$a} 人';
$string['batch_preview_new'] = '全新 {$a}';
$string['batch_preview_keep'] = '既有编号 {$a}';
$string['batch_scenario'] = '情境';
$string['batch_scenario_new'] = '全新学员';
$string['batch_scenario_issue_id'] = '补发编号';
$string['batch_scenario_keep_id'] = '既有编号';
$string['batch_scenario_prereq_ok'] = '可加入（已通过前一等级）';
$string['batch_scenario_prereq_fail'] = '不符合条件';
$string['batch_candidate_pending'] = '确认后核发';
$string['batch_confirm_commit'] = '确认写入';
$string['batch_commit_result'] = '批次完成：成功 {$a->ok}、失败 {$a->failed}。';
$string['batch_commit_skipped'] = '已略过不符合条件 {$a} 人。';
$string['batch_preview_eligible'] = '可加入 {$a}';
$string['batch_preview_blocked'] = '不符合 {$a}';
$string['batch_upload_intro_prereq'] = '中／高级不新建账：以 Email 查找既有学员，并检查是否已通过前一等级（笔试若必要＋术科：外挂成绩或对照出缺席之〔参〕）。写入时密码重设为“Tmai”+编号。预览确认后只写入符合条件者。';
$string['batch_password_reset_hint'] = '确认写入后，可加入者密码皆会重设为“Tmai”+考生编号；请下载学员名单取得完整帐密。';
$string['batch_commit_passwords_note'] = '密码已重设为预设规则。';
$string['batch_commit_keep_auth_note'] = '外部登录账号（如 LDAP）密码未改，请用原公司帐密签到。';
$string['batch_commit_password_more'] = '（另有 {$a} 人请下载名单查看）';
$string['prereq_single_hint'] = '中／高级请输入学员 Email（优先查找）。系统不新建账，并检查是否已通过前一等级。';
$string['prereq_none_eligible'] = '名单中无人符合前置条件，无法送出。请修正后重新上传。';
$string['prereq_col_reason'] = '不符合原因';
$string['prereq_reason_account_not_found'] = '查无此账号';
$string['prereq_reason_written_fail'] = '前一等级笔试未通过';
$string['prereq_reason_practical_fail'] = '前一等级术科未通过';
$string['prereq_reason_no_candidate_id'] = '尚无考生编号（请先完成初级）';
$string['prereq_reason_prev_level_not_passed'] = '前一等级尚未通过';
$string['error_prereq_blocked'] = '不符合前置条件：{$a}';
$string['error_prereq_account_not_found'] = '查无此账号，中／高级不可新建账。';
$string['error_prereq_no_candidate_id'] = '此学员尚无考生编号。';
$string['error_batch_csv_empty'] = 'CSV 档案是空的。';
$string['error_batch_csv_header'] = 'CSV 表头必须包含“姓”“名”“邮箱”字段（亦接受英文 lastname／firstname／email）。';
$string['error_batch_csv_row_email'] = '第 {$a} 列：邮箱无效。';
$string['error_batch_csv_row_name'] = '第 {$a} 列：姓与名为必填。';
$string['error_batch_csv_row_dup'] = '档案中 Email 重复：{$a}';
$string['error_batch_csv_has_errors'] = 'CSV 有错误：{$a}';
$string['error_batch_preview_expired'] = '预览已过期，请重新上传 CSV。';
$string['error_candidate_allocate_failed'] = '无法核发考生编号，请重试。';
$string['error_candidate_id_in_use'] = '考生编号 {$a} 已被其他有效账号使用，无法绑定。请联络管理员确认编号资料。';
$string['error_candidate_id_invalid'] = '考生编号须为 6 位数字。';
$string['error_candidate_id_cannot_clear'] = '此处不可清空考生编号。若需释出编号，请自场次名单移除（依释出规则）。';
$string['candidate_id_checking'] = '考生编号检查失败，请重试。';
$string['profile_category_candidate'] = 'TM 检定';
$string['batch_commit_first_error'] = '（{$a->email}：{$a->error}）';
$string['error_candidate_year_full'] = '民国 {$a} 年考生编号流水号已满（999）。';

$string['roster_export_login_csv'] = '下载登入名单';
$string['roster_export_login_hint'] = '含 Moodle 登入账号、姓名、预设密码（Tmai+考生编号）、所属单位。';
$string['roster_back'] = '返回';
$string['roster_back_list'] = '返回名单';
$string['enrol_empty_title'] = '尚无学员';
$string['enrol_mode_single'] = '单笔加入';
$string['enrol_mode_batch'] = '批次上传';
$string['enrol_single_preview_hint'] = '填写后先预览情境（全新／既有编号等），确认后才写入并重设密码。';
$string['enrol_no_course_hint_short'] = '此场次尚未对应 Moodle 课程，仅写入外挂名册。';

$string['setting_assess_heading'] = '评量规则（笔试＋术科）';
$string['setting_assess_desc'] = '依检定等级设定：是否需要笔试、笔试／术科及格分数，以及出缺席状态 id 覆写（选用）。';
$string['setting_written_enabled'] = '{$a}：需要笔试';
$string['setting_written_enabled_desc'] = '启用后，总通过需笔试＋术科皆过；关闭则仅看术科。';
$string['setting_written_quiz'] = '{$a}：笔试测验活动';
$string['setting_written_quiz_none'] = '— 尚未对照 —';
$string['setting_written_pass'] = '{$a}：笔试及格分数';
$string['setting_written_pass_desc'] = 'Moodle 测验成绩需达此分数（与测验成绩项目同分制）。';
$string['setting_practical_pass'] = '{$a}：术科及格分数';
$string['setting_practical_pass_desc'] = '术科分数需大于等于此值才算通过（预设 90）。';
$string['setting_attendance_status_heading'] = '出缺席状态覆写（选用）';
$string['setting_attendance_status_desc'] = '预设 0：自动辨识〔参〕／Present 与〔缺〕／Absent。仅在自动辨识失败时填入 attendance_statuses.id。';
$string['setting_attendance_statusid_present'] = '〔参〕状态 id 覆写';
$string['setting_attendance_statusid_present_desc'] = 'attendance_statuses.id（0＝自动）';
$string['setting_attendance_statusid_absent'] = '〔缺〕状态 id 覆写';
$string['setting_attendance_statusid_absent_desc'] = 'attendance_statuses.id（0＝自动）';

$string['practical_page_title'] = '输入成绩';
$string['practical_section_title'] = '学员成绩';
$string['practical_threshold_label'] = '及格门槛：{$a}';
$string['practical_threshold_short'] = '及格 {$a}';
$string['practical_threshold_rule'] = '术科 ≥ {$a} 为通过';
$string['practical_written_hint'] = '此等级另需笔试。笔试结果由对照的 Moodle 测验读取；请于下方输入术科分数。';
$string['practical_written_hint_short'] = '笔试由 Moodle 测验读取；请输入术科分数后储存。';
$string['practical_needs_written'] = '需笔试';
$string['practical_score'] = '术科分数';
$string['practical_result'] = '术科结果';
$string['practical_result_short'] = '术科';
$string['practical_pass'] = '通过';
$string['practical_fail'] = '未通过';
$string['practical_pending'] = '未输入';
$string['practical_save'] = '储存成绩';
$string['practical_save_result'] = '已储存：成功 {$a->ok}、失败 {$a->fail}。';
$string['practical_attendance_partial'] = '其中 {$a} 笔已存外挂，但出缺席同步略过（请检查出缺席对照）。';
$string['practical_blank_skip'] = '空白列不储存';
$string['practical_empty_hint'] = '请先至名单新增学员后再输入成绩。';
$string['practical_goto_roster'] = '前往名单';
$string['written_result'] = '笔试结果';
$string['written_result_short'] = '笔试';
$string['written_not_required'] = '不需笔试';
$string['written_no_score'] = '尚无测验成绩';
$string['written_no_score_short'] = '尚无成绩';
$string['written_pass_with_score'] = '通过（{$a}）';
$string['written_fail_with_score'] = '未通过（{$a}）';
$string['written_pass_short'] = '通过 {$a}';
$string['written_fail_short'] = '未通过 {$a}';
$string['error_enrol_cancelled'] = '此学员已自场次移除。';

$string['setting_notify_heading'] = '通知';
$string['setting_notify_desc'] = '以 Moodle 邮件寄出。寄信失败不阻断审核、建账或存成绩。名单移除并删除账号时不寄信。';
$string['setting_notify_enabled'] = '启用检定通知';
$string['setting_notify_enabled_desc'] = '关闭后，下列各项皆不寄信。';
$string['setting_notify_app_review'] = '开课申请审核结果';
$string['setting_notify_app_review_desc'] = '核准：通知原申请者；若开课讲师不同人，另通知讲师（指派）。驳回：只通知申请者。';
$string['setting_notify_new_account'] = '新建学员账号';
$string['setting_notify_new_account_desc'] = '新建立账号时寄送账号、预设密码与场次资讯。';
$string['setting_notify_enrol_existing'] = '既有学员加入场次';
$string['setting_notify_enrol_existing_desc'] = '预设关闭。开启后，将既有账号加入场次时也寄通知。';

$string['notify_app_approved_subject'] = '【{$a->sitename}】开课申请已核准：{$a->name}';
$string['notify_app_approved_body'] = '您好，

您的开课申请已核准。

场次：{$a->name}
等级：{$a->level}
时间：{$a->when} – {$a->endwhen}
考场：{$a->venue}

请登入系统查看：{$a->siteurl}/local/tm_certification/index.php
';
$string['notify_instructor_assigned_subject'] = '【{$a->sitename}】您已被指派为检定场次讲师：{$a->name}';
$string['notify_instructor_assigned_body'] = '您好，

您已被指派为下列检定场次的授课讲师。

场次：{$a->name}
等级：{$a->level}
时间：{$a->when} – {$a->endwhen}
考场：{$a->venue}

请登入系统查看：{$a->siteurl}/local/tm_certification/index.php
';
$string['notify_app_rejected_subject'] = '【{$a->sitename}】开课申请未通过（{$a->level}）';
$string['notify_app_rejected_body'] = '您好，

您申请的开课（等级：{$a->level}，预定开始：{$a->when}）未获核准。

审核备注：{$a->note}

如有疑问请联络管理员。
';
$string['notify_new_account_subject'] = '【{$a->sitename}】检定账号已建立：{$a->name}';
$string['notify_new_account_body'] = '您好 {$a->fullname}，

已为您建立检定登入账号，并加入场次。

场次：{$a->name}
时间：{$a->when}
考场：{$a->venue}
账号：{$a->username}
考生编号：{$a->candidateid}
预设密码：{$a->password}

请尽快登入并视需要修改密码：{$a->siteurl}/login/index.php
';
$string['notify_enrol_existing_subject'] = '【{$a->sitename}】您已加入检定场次：{$a->name}';
$string['notify_enrol_existing_body'] = '您好 {$a->fullname}，

您已被加入下列检定场次。

场次：{$a->name}
时间：{$a->when}
考场：{$a->venue}
账号：{$a->username}
考生编号：{$a->candidateid}

登入：{$a->siteurl}/login/index.php
';
$string['notify_no_note'] = '（无）';
$string['notify_venue_none'] = '（未指定）';
$string['notify_candidate_pending'] = '（待核发）';
$string['notify_password_unchanged'] = '（沿用原密码／预设规则）';
$string['cert_col_pass'] = '通过';
$string['cert_pass_ok'] = '通过';
$string['cert_pass_fail'] = '未通过';
$string['cert_pass_pending'] = '未判定';
$string['cert_download_one'] = '下载证书';
$string['cert_download_not_passed'] = '仅本场判定通过后可下载证书。';
$string['cert_download_unavailable'] = '此学员尚无 Moodle 可领／已核发证书。';
$string['cert_download_customcert_missing'] = '尚未安装 mod_customcert。';
$string['cert_download_course_unmapped'] = '此等级尚未对照 Moodle 课程。';
$string['cert_batch_title'] = '证书批次下载';
$string['cert_batch_session_title'] = '本场证书下载';
$string['cert_batch_site_title'] = '证书批次下载';
$string['cert_batch_pick_level'] = '证书等级';
$string['cert_batch_apply_level'] = '套用';
$string['cert_batch_level_hint'] = '预计下载名单依等级分开保存；切换等级会换成该等级的名单。';
$string['cert_batch_mode_session'] = '本场通过者';
$string['cert_batch_mode_search'] = '搜索全站';
$string['cert_batch_level'] = '等级：{$a}';
$string['cert_batch_switch_search'] = '改为搜索全站';
$string['cert_batch_switch_session'] = '改为本场通过者';
$string['cert_batch_session_passed'] = '本场已通过';
$string['cert_batch_count'] = '已选 {$a} 项';
$string['cert_batch_no_passed'] = '本场尚无判定通过的学员。';
$string['cert_batch_make_summary'] = '产生下载摘要';
$string['cert_batch_search_heading'] = '搜索并挑选';
$string['cert_batch_search_placeholder'] = 'Email、考生编号或姓名';
$string['cert_batch_search_hint'] = '可搜索全站考生。同一人若有多等级证书会分列显示；按「加入」只加该等级。更换关键字不会清空已选。';
$string['cert_batch_search_empty'] = '找不到符合的考生。';
$string['cert_batch_col_action'] = '操作';
$string['cert_batch_col_level'] = '证书等级';
$string['cert_batch_add'] = '加入';
$string['cert_batch_already_in_cart'] = '已加入预计名单';
$string['cert_batch_cart_heading'] = '预计下载名单';
$string['cert_batch_cart_empty'] = '名单是空的，请先搜索并按等级加入。';
$string['cert_batch_clear_cart'] = '清空';
$string['cert_batch_remove'] = '移除';
$string['cert_batch_summary_heading'] = '下载前摘要';
$string['cert_batch_summary_ok'] = '可下载 {$a}';
$string['cert_batch_summary_miss'] = '无证书 {$a}';
$string['cert_batch_summary_hint'] = '「有证书」＝ Moodle customcert 已核发或可领取。无证书者略过，不挡整批；确认后打包 zip。';
$string['cert_batch_col_moodle'] = 'Moodle 证书（{$a}）';
$string['cert_batch_col_moodle_short'] = 'Moodle 证书';
$string['cert_batch_col_result'] = '结果';
$string['cert_batch_result_include'] = '将纳入 zip';
$string['cert_batch_result_include_n'] = '将纳入（{$a} 份 PDF）';
$string['cert_batch_result_skip'] = '略过（不挡整批）';
$string['cert_batch_all_levels'] = '一次处理所有等级';
$string['cert_batch_by_level'] = '按等级挑选';
$string['cert_batch_level_pick_hint'] = '搜索结果按证书等级分列；只加入需要的等级，zip 不会带入未选等级。';
$string['cert_batch_multilevel_hint'] = '每位已选学员会检查初／中／高对照证书；有的等级一并打包，没有的略过。';
$string['cert_batch_summary_hint_multilevel'] = '「有证书」＝该等级 Moodle customcert 已核发或可领取。缺的等级略过，不挡整批。';
$string['cert_batch_summary_pdfs'] = '{$a} 份 PDF';
$string['cert_batch_summary_users_ok'] = '有证书 {$a} 人';
$string['cert_batch_summary_users_miss'] = '全无 {$a} 人';
$string['cert_batch_confirm_zip'] = '确认下载 zip（{$a} 份 PDF）';
$string['cert_batch_zip_empty'] = '此选取中没有可下载的证书。';
$string['cert_status_issued'] = '已核发';
$string['cert_status_receivable'] = '可领取';
$string['cert_status_none'] = '无可领证书';

$string['rollcall_col'] = '现场点名';
$string['rollcall_status_present'] = '到课';
$string['rollcall_status_late'] = '迟到';
$string['rollcall_status_absent'] = '未到';
$string['rollcall_status_unknown'] = '未点名';
$string['rollcall_source_self'] = '学生自签';
$string['rollcall_source_staff'] = '讲师';
$string['rollcall_count_present'] = '到课 {$a}';
$string['rollcall_count_late'] = '迟到 {$a}';
$string['rollcall_count_absent'] = '未到 {$a}';
$string['rollcall_count_unknown'] = '未点名 {$a}';
$string['rollcall_all_present'] = '全部标到课';
$string['rollcall_all_present_done'] = '已将本场名单全部标为到课。';
$string['rollcall_roster_hint'] = '现场点名＝到课确认，不会写入 Moodle 出缺席。学生可扫「本场签到 QR」自签；讲师仍可在此栏手动变更。';
$string['rollcall_qr_title'] = '本场签到 QR';
$string['rollcall_qr_for_students'] = '给学生扫描';
$string['rollcall_qr_rules_hint'] = '规则：开场前扫＝到课；开场后至结束＝迟到；场次结束后＝关闭自签。须先登录 Moodle。';
$string['rollcall_qr_present_until'] = '到课门槛 {$a}';
$string['rollcall_qr_closes_at'] = '关闭 {$a}';
$string['rollcall_qr_scan_heading'] = '扫码后请先登录，再确认签到';
$string['rollcall_qr_scan_hint'] = '此 QR 仅限本场次。建议投影在教室或印成纸本置于入口。';
$string['rollcall_qr_download'] = '下载 QR';
$string['rollcall_qr_open_link'] = '打开签到链接';
$string['rollcall_qr_regen'] = '重新产生';
$string['rollcall_qr_regen_hint'] = '「重新产生」会作废旧链接（旧 QR 失效），适合链接外流时使用。';
$string['rollcall_qr_regenerated'] = '已重新产生本场签到 QR；旧链接已失效。';
$string['rollcall_window_open'] = '签到开放中';
$string['rollcall_window_closed'] = '自签已关闭';
$string['rollcall_checkin_title'] = '本场签到';
$string['rollcall_checkin_will_be'] = '将记为：{$a}';
$string['rollcall_checkin_now'] = '现在 {$a}';
$string['rollcall_checkin_logged_in'] = '已登录';
$string['rollcall_checkin_confirm'] = '确认签到';
$string['rollcall_checkin_done'] = '签到完成';
$string['rollcall_checkin_already'] = '已签到';
$string['rollcall_checkin_done_hint'] = '可关闭此页。若状态有误，请洽讲师于名单修改。';
$string['rollcall_checkin_closed'] = '自签已关闭';
$string['rollcall_checkin_closed_hint'] = '场次已结束。若需补登，请洽讲师于名单手动点名。';
$string['rollcall_checkin_not_roster'] = '不在本场名册';
$string['rollcall_checkin_not_roster_hint'] = '目前登录身分不在本场学员名单。请确认账号，或洽讲师加入名册。';
$string['rollcall_checkin_bad_token'] = '签到链接无效';
$string['rollcall_checkin_bad_token_hint'] = 'QR 可能已重新产生。请向讲师索取最新签到码。';
$string['error_rollcall_status_invalid'] = '无效的点名状态。';
$string['error_rollcall_enrol_cancelled'] = '此学员已自名册移除，无法点名。';

