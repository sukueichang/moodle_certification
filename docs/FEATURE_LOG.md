# FEATURE_LOG — 檢定外掛

## 規劃中（規格已鎖定）

### 2026-07-30 — P9～P10 追加規格

- **文件：** [`docs/P8_P10_ADDENDUM.md`](P8_P10_ADDENDUM.md)
- **P9 現場點名：** 名單入口；獨立點名表／統計；不與 `mod_attendance` 混淆。
- **P10 證書下載：** 學員自助；名單在**已判定通過**時顯示下載（與場次結束無關）；講師可代理下載。
- **狀態：** 規格鎖定，**尚未實作**。

## 進行中／已完成

### 2026-08-12 — 加入名冊時補上檢定學員角色（不移除既有角色）

- **需求：** 補發編號／既有帳加入場次時，也自動加上設定的檢定學員角色；不移除原角色。
- **實作：** `add_to_session` 在核准寫入時呼叫 `assign_configured_examinee_role`（已有則略過）。
- **版本／狀態：** 0.8.9（2026073031）；**待測**

### 2026-08-12 — 登入名單 CSV 使用真實 Moodle 帳號

- **現象：** 既有帳號（username ≠ 考生編號）下載登入名單時，「帳號」欄誤填考生編號，無法登入。
- **解法：** `roster_login_csv` 帳號欄改取 `user.username`；密碼仍為 `Tmai`+考生編號。
- **版本／狀態：** 0.8.8（2026073030）；**待測**

### 2026-08-12 — 學員儀表板：我的場次＋開始筆試

- **需求：** 學員登入可見被加入的場次；場次開始後顯示「開始筆試」跳至對照 Quiz；結束後不禁用。
- **實作：** `enrolment_manager::get_examinee_sessions`；`assessment_manager::written_quiz_url`／`examinee_written_button`；`index.php` examinee 視圖。
- **版本／狀態：** 0.8.7（2026073029）；**待測**

### 2026-08-12 — 單筆加入改為預覽確認；取消手動批次

- **需求：** 移除手動批次分頁；單筆加入改為與 CSV 相同的預覽→確認寫入流程。
- **實作：** `enrol.php` 僅保留單筆／批次上傳；單筆走 `preview_rows` + `commit_to_session`。
- **版本／狀態：** 0.8.6（2026073028）；**待測**

### 2026-08-12 — 新增學員：手動批次貼上

- **需求：** 場次「新增學員」除 CSV 外，可手動貼多行；流程比照批次（預覽／summary／確認寫入／密碼重設）。
- **實作：** `batch_helper::parse_manual_text`；`enrol.php` 新增「手動批次」分頁；與 CSV 共用 preview／commit。
- **版本／狀態：** 0.8.5（2026073027）；**已取消（由 0.8.6 取代）**

### 2026-08-12 — 加入名單時密碼重設並顯示明文

- **需求：** 單筆／批次加入（含既有帳／補考）一律重設為 `Tmai`+考生編號；成功訊息顯示明文密碼；批次預覽提示會重設密碼。
- **實作：** `enrol.php` 成功訊息不再限「新建」才顯示密碼；批次 commit 附加重設說明與首筆密碼範例；`apply_default_password` 對非 manual auth 記 debugging。
- **版本／狀態：** 0.8.4（2026073026）；**待測**

### 2026-08-12 — 申請開課純淨化（不帶學員）

- **需求：** 申請開課僅送場次資訊；移除 CSV／學員名單；審核與管理儀表板不再顯示學員人數；核准只建場次、不寫入名冊（舊草稿一併丟棄）。
- **實作：** 精簡 `apply.php`；`review.php`／`index.php` 拿掉學員欄；`application_manager::approve/reject` 刪除 `app_learners` 且不再 `commit_app_learners_to_session`。
- **版本／狀態：** 0.8.2（2026073024）；**待測**

### 2026-07-30 — P8 等級前置（中／高級）

- **需求：** 中／高級不新建帳；以 Email 查找；檢查前一等級筆試（若必要）＋術科；summary 顯示考生編號或不合格原因；初→中→高不可跳級；不限講師／考場。
- **實作：** `prerequisite_helper`；`batch_helper::preview_rows($rows, $level)`；單筆／批次／申請預覽同一套檢查；核准寫入時再檢一次。
- **版本／狀態：** 0.8.0（2026073022）；**待測**

### 2026-07-29 — P1 骨架與權限／Dashboard 殼

- **需求：** 建立 `local_tm_certification`；權限不寫死；Dashboard 可開關；三角色視圖殼；我的證書頁殼。
- **決策：** 身分判定＝capability **或** 設定頁指派的 Moodle role id；Site admin 對 manage／approve 視為具備。
- **版本／狀態：** 0.1.0（2026072900）；後由 0.1.1 接續。

### 2026-07-29 — P1.1 首頁儀表板未顯示修正

- **現象：** Dashboard 顯示開關皆開啟，但網站首頁看不到儀表板。
- **根因：** 0.1.0 僅有獨立頁 `/local/tm_certification/index.php`，尚未像 `local_tm_course` 注入 frontpage／My home。
- **解法：** 新增 `before_standard_top_of_body_html` 注入首頁區塊；加強導覽；設定頁說明出現位置。
- **版本／狀態：** 0.1.1（2026072901）；**已驗收**（使用者：測試可以了）
- **GitHub：** https://github.com/sukueichang/moodle_certification （private，`master`）

### 2026-07-29 — P2 場次與帳號／enrol

- **需求：** 管理端建立／編輯場次；Email 建帳或連結既有帳號並加入場次；可選同步 Moodle 課程 manual enrol。
- **決策：** 表 `local_tm_cert_sessions`、`local_tm_cert_enrols`；借 v2 provision／enrol 路徑 Copy-adapt。
- **版本／狀態：** 0.2.0（2026072902）；**進行中（待測）**
- **分支：** `feature/p2-sessions-enrol`

### 2026-07-29 — P2.1 建立場次 fullname debugging

- **現象：** 開啟建立場次頁大量 `You need to update your sql to include additional name fields`。
- **根因：** 講師下拉查詢只選 `firstname,lastname`，`fullname()` 需要完整 name fields。
- **解法：** 改用 `\core_user\fields::for_name()->get_required_fields()` 組 SELECT。
- **版本／狀態：** 0.2.1（2026072903）；**進行中（待測）**

### 2026-07-29 — P2.2 場次規則重構（等級化考場／講師／課程對照）

- **需求：**
  1) 場次名稱固定 `[等級]檢定課程`；2) 考場獨立維護且依等級授權；  
  3) Moodle 課程改為後台等級對照；4) 授課講師依等級授權過濾。
- **決策：**
  - 新增考場主檔與講師等級授權資料表。  
  - 場次建立頁移除手打名稱與課程選擇，改為系統自動決定。  
  - 以等級驅動下拉清單（考場／講師）。
- **版本／狀態：** 0.2.2（2026072904）；**進行中（待測）**
- **注意：** 升級過程曾遇 XMLDB `CHAR NOT NULL DEFAULT ''` 警告，已修復於 `db/upgrade.php`（見 `docs/BUGFIX_LOG.md`）。
