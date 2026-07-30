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

