# BUGFIX_LOG — local_tm_certification

## 2026-07-29

### [BUG-20260729-01] 建立場次頁 fullname() debugging
- **現象**  
  開啟 `admin/edit_session.php` 出現：  
  `You need to update your sql to include additional name fields in the user object.`
- **根因**  
  講師下拉查詢僅選到部分姓名欄位，`fullname()` 需要完整 name fields。
- **修復**  
  補齊使用者姓名相關欄位查詢，避免 fullname debugging。
- **影響檔案**  
  `local_tm_certification/admin/edit_session.php`  
  `local_tm_certification/classes/enrolment_manager.php`
- **結果**  
  訊息消失，頁面可正常載入。

### [BUG-20260729-02] XMLDB CHAR NOT NULL DEFAULT '' 警告
- **現象**  
  升級時出現：  
  `XMLDB has detected one CHAR NOT NULL column (name) with '' (empty string) as DEFAULT value...`
- **根因**  
  `db/upgrade.php` 建立 `local_tm_cert_venues.name` 時，將 `CHAR NOT NULL` 欄位預設值設為空字串 `''`。
- **修復**  
  將該欄位 default 改為 `null`（不使用空字串預設值），符合 XMLDB 規範。
- **影響檔案**  
  `local_tm_certification/db/upgrade.php`
- **結果**  
  `php -l` 通過；重新升級後不應再出現該警告訊息。

### [BUG-20260729-03] 升級錯誤：Call to undefined method database_manager::key_exists()
- **現象**  
  升級到含 `venuecode` 的版本時中斷：  
  `Call to undefined method database_manager::key_exists()`
- **根因**  
  `db/upgrade.php` 使用了較新版本才有的 `database_manager::key_exists()`，在 Moodle 3.9 不存在。
- **修復**  
  改為相容寫法：  
  - 若方法存在才使用 `key_exists()`  
  - 否則直接 `add_key()`，並容錯處理重跑時的重複 key 例外
- **影響檔案**  
  `local_tm_certification/db/upgrade.php`
- **防再發準則**  
  新增 upgrade API 時先確認最低支援版（Moodle 3.9）的方法可用性；遇到差異一律採 `method_exists + fallback`。

### [BUG-20260729-04] 左側「檢定管理」快捷入口未出現（暫緩）
- **現象**  
  首頁左側導覽可見「網站管理」「課程報名管理」，但「檢定管理」未出現。  
  網站管理原有項目已可正常展開／進入（先前干擾已排除）。
- **已嘗試**  
  1. `extend_settings_navigation` 掛節點 → 曾干擾網站管理，已還原為空實作。  
  2. 改以 `extend_navigation` + `showinflatnavigation = true`（比照 `local_tm_course`）。  
  3. 語系 `nav_main`（檢定管理）已存在；清除快取後仍未顯示。
- **現況**  
  `settings.php` 已有 `localplugins` → `local_tm_certification_cat`（與課程報名管理同結構）。  
  `lib.php` 的 global navigation 節點仍保留，但左側快捷入口**暫不繼續修改**，改記錄為已知問題。
- **影響檔案（現況）**  
  `local_tm_certification/lib.php`  
  `local_tm_certification/settings.php`  
  `local_tm_certification/lang/*/local_tm_certification.php`
- **待辦（之後再開）**  
  對照線上 Moodle 實際版／佈景與 `local_tm_course` 生效差異；必要時改掛 `root` admin category 或查 flat navigation 顯示條件。
  狀態：**OPEN / DEFERRED**

### [BUG-20260729-05] 審核開課申請按鈕點擊 HTTP 500
- **現象**  
  Dashboard「審核開課申請」點擊後出現 HTTP ERROR 500，頁面無反應／無法開啟審核列表。
- **根因**  
  `admin/review.php` 載入 Moodle `config.php` 路徑少了一層目錄：  
  誤用 `__DIR__ . '/../../config.php'`（指向 `local/config.php`），應為 `__DIR__ . '/../../../config.php'`。
- **修復**  
  與其他 `admin/*.php` 一致，改為三層上層路徑。
- **影響檔案**  
  `local_tm_certification/admin/review.php`
- **結果**  
  重新部署該檔後應可開啟審核頁（無需升版；若已快取可清快取）。

### [BUG-20260730-01] 下載學員登入名單 CSV 只見 debugging 一行
- **現象**  
  點「下載當次學員登入名單 CSV」後內容異常，出現  
  `Coding problem: $PAGE...` 之類 notifytiny debugging HTML。
- **根因**  
  `roster_export.php` 未先 `$PAGE->set_context()`，且 `roster_login_csv()` 呼叫 `fullname()`／檔名用 `format_string()`，在下載腳本中觸發 Moodle coding debug，HTML 混入 CSV。
- **修復**  
  匯出前設定 system context；姓名改組 `lastname+firstname`；清除 output buffer 後再送檔。
- **影響檔案**  
  `admin/roster_export.php`、`classes/batch_helper.php`（0.4.7）

### [BUG-20260730-02] 場次學員數含已移除名冊；講師場次缺人數／名單
- **現象**  
  1. Dashboard「已開場次」顯示學員數 3，實際現有名單僅 1（另 2 人已移除／刪帳）。  
  2. 講師視圖「我的授課場次」沒有學員數統計，也沒有「學員名單」按鈕。
- **根因**  
  1. `session_manager::count_enrols()` 預設 `count_records` 含 `status=cancelled` 列。  
  2. 講師 Dashboard 場次卡片未顯示人數、未放 roster 連結（管理視圖才有）。
- **修復**  
  1. `count_enrols()` 預設只計非 cancelled（現行名冊）。  
  2. 講師場次卡片對齊管理視圖：顯示人數＋學員名單／安插／術科按鈕。
- **影響檔案**  
  `classes/session_manager.php`、`index.php`（0.5.1）

### [BUG-20260730-03] 講師同時勾選多等級後頁面 debugging／無法儲存顯示
- **現象**  
  「講師等級授權」同時勾選兩個以上等級並儲存後，頁面出現  
  `Duplicate value '…' found in column 'userid'`（`instructor_level_matrix`）。
- **根因**  
  `get_records_select(..., 'userid,certlevel')` 以首欄 `userid` 當陣列鍵；同一講師多筆 active 等級列時鍵重複。儲存邏輯本已支援多等級，阻斷來自讀取而非寫入。
- **修復**  
  改選 `id, userid, certlevel`；明示多等級可同時持有；「我的證書」／講師封面改讀採認結果。
- **影響檔案**  
  `classes/catalog_manager.php`、`admin/instructors.php`（文案）、`my_certificates.php`、`classes/dashboard_helper.php`、語系（0.5.2）

### [BUG-20260730-04] 講師效期逾一年仍顯示有效；需依等級可設定月數
- **現象**  
  取得證書日設為 1 年以上仍顯示 Valid（因「一年內有授課」會自動延長）。且效期寫死 1 年，無法依等級調整。
- **根因**  
  `get_instructor_certificate_status()` 以 YEARSECS 或近期授課判定 Valid。
- **修復**  
  改為：錨點＝取得／Renew 較晚日＋Admin 各等級「效期月數」設定即時計算；拿掉授課自動延長；「我的證書」依等級顯示狀態／到期日。
- **影響檔案**  
  `classes/catalog_manager.php`、`settings.php`、`my_certificates.php`、`dashboard_helper.php`、`admin/instructors.php`、申請／場次閘門、語系（0.6.1）

### [BUG-20260730-05] 授課延長改為 Admin 開關；顯示「更新效期期限」
- **需求**  
  授課自動延長改為設定勾選；勾選後即時追溯重算。過期顯示「需立即更新」，有效顯示截止日。
- **修復**  
  新增 `instructor_extend_by_teaching`；錨點可含最後授課日。我的證書／封面顯示「更新效期期限：…」。
- **影響檔案**  
  `classes/catalog_manager.php`、`settings.php`、`my_certificates.php`、`dashboard_helper.php`、語系（0.6.2）

### [BUG-20260730-06] 關閉場次誤擋講師過期；文案與 CSV 表頭
- **現象**  
  關閉場次因講師效期過期噴錯；提示用語偏硬；登入名冊 CSV 表頭為英文。
- **修復**  
  `close_session()` 不再走完整驗證；效期閘門僅在新建／改講師或等級時檢查。文案改白話。CSV 表頭改走語系字串（中文站＝帳號／姓名／預設密碼／所屬單位）。
- **影響檔案**  
  `classes/session_manager.php`、`classes/batch_helper.php`、語系（0.6.3）

### [FEAT-20260730-07] 場次「關閉」改「已結束」＋自動結束＋刪除
- **規則**  
  結束時間到 → 自動「已結束」；講師仍可見，不可再加學員；僅 Admin 可重開／刪除（刪除＝資料移除）。
- **影響檔案**  
  `session_manager.php`、`sessions.php`、`roster.php`、`index.php`、`enrolment_manager.php`、`batch_helper.php`、語系（0.6.4）

### [FEAT-20260730-08] 儀表板 UI v3「檢定管理中心」
- **變更**  
  標題改為檢定管理中心；封面事實 pill／KPI／場次列版面；拿掉長說明文。
- **影響檔案**  
  `index.php`、`styles.css`、`dashboard_helper.php`、語系、`docs/dashboard_preview_v3.html`（0.6.5）

### [FEAT-20260730-09] 名單／新增學員分頁
- **規則**  
  「名單」只看表＋下載＋成績＋移除；「新增學員」獨立頁（單筆／批次分頁）。
- **影響檔案**  
  `admin/roster.php`、`admin/enrol.php`、`index.php`、`styles.css`、語系（0.6.6）

### [FEAT-20260730-10] 輸入成績頁 UI
- **變更**  
  封面 pill、成績表 status pill、不需筆試隱藏筆試欄、文案縮短。
- **影響檔案**  
  `admin/practical_scores.php`、`styles.css`、語系、`docs/scores_preview.html`（0.6.7）

### [FEAT-20260730-11] P7 通知（審核／指派／建帳）
- **規則**  
  核准：申請者＋（不同人時）開課講師分文案；駁回只通知申請者；新建帳寄密碼；既有加入預設不寄；刪帳不寄。失敗不擋主流程。
- **影響檔案**  
  `classes/notification_helper.php`、`application_manager.php`、`enrolment_manager.php`、`batch_helper.php`、`settings.php`、語系（0.7.0）

### [BUG-20260730-12] 申請開課換等級後考場驗證失敗
- **現象**  
  已選符合中級的考場／講師，送出卻顯示「所選考場與此等級不符或未啟用」。
- **原因**  
  表單選項只載入初始等級；JS 換成其他等級後，Moodle 送出／驗證與等級對照不一致。
- **修復**  
  考場／講師選項改載入全部啟用項作 whitelist，畫面仍由 JS 依等級過濾；伺服端用 `venue_matches_level()` 驗證。
- **影響檔案**  
  `apply.php`、`edit_session.php`、`catalog_manager.php`、`application_manager.php`、`session_manager.php`、語系（0.7.1）

### [FEAT-20260730-13] 申請／審核備註顯示 + 申請學員名單預覽
- **現象／需求**  
  1. 申請備註、審核備註寫入後列表／儀表板無處可見。  
  2. 申請開課附帶 CSV 時希望先預覽人數與姓名，確認後再送出。
- **修復**  
  - 審核列表與確認頁、儀表板「待審／我的申請」顯示申請／審核備註。  
  - 申請頁有 CSV 時先進預覽（課程資訊＋名單），確認後才寫入申請。
- **影響檔案**  
  `apply.php`、`admin/review.php`、`index.php`、語系、`version.php`（0.7.2）

### [FEAT-20260730-14] P8 中／高級前置資格檢查
- **規則**  
  中／高級不新建帳；Email 優先查找；須已通過前一等級（筆試若必要＋術科）；summary 顯示編號或不合格原因；申請／單筆／批次同一邏輯。
- **影響檔案**  
  `classes/prerequisite_helper.php`、`batch_helper.php`、`enrolment_manager.php`、`admin/enrol.php`、`apply.php`、語系、`styles.css`、`version.php`（0.8.0）

### [BUG-20260812-15] 既有編號帳號無法加入場次（無法核發考生編號）
- **現象**  
  預覽顯示既有編號，確認／單筆加入卻「無法核發考生編號」；名單仍為 0 人。
- **原因**  
  帳號 username 已是考生編號，但 `local_tm_cert_candidates` 仍被已刪除／不存在使用者佔用；綁定時直接失敗且批次不顯示細節。
- **修復**  
  佔用者已刪除或不存在時自動釋放並綁給現有帳號；若被有效帳號佔用則顯示明確錯誤；批次失敗附帶第一筆原因。
- **影響檔案**  
  `classes/candidate_manager.php`、`admin/enrol.php`、語系、`version.php`（0.8.1）

