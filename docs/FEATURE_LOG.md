# FEATURE_LOG — 檢定外掛

## 進行中／已完成

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
