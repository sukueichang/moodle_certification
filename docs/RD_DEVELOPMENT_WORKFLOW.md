# RD 開發流程文件（local_tm_certification）

> 目的：把需求、設計、實作、測試、缺陷修復與版本控管，用同一套 RD 流程固定化。

---

## 1) 需求階段（Requirement）

### 1.1 輸入
- 產品需求（PRD）
- 使用者新增規格與限制條件
- 既有系統相依條件（Moodle core、mod_attendance、GitHub 維護）

### 1.2 輸出
- 規格更新到 `moodle_certification_plugin_prd.md`
- 明確定義：角色、權限、資料流、錯誤容忍策略
- 分期範圍（P0~P7）與驗收標準

### 1.3 Gate（可進入設計的條件）
- 新需求已寫入 PRD
- 影響範圍（資料表、頁面、權限、整合點）可被列舉

---

## 2) 設計階段（Design）

### 2.1 技術設計內容
- DB schema（`install.xml` + `upgrade.php`）
- 權限模型（capabilities + 動態角色對應）
- UI/UX（Dashboard、管理頁、表單行為）
- 外部整合（Moodle course mapping、attendance 綁定）

### 2.2 設計約束
- 禁止寫死角色 shortname
- 出缺席使用既有活動綁定，不自動新建 activity
- XMLDB 規範遵循（避免 `CHAR NOT NULL DEFAULT ''`）

### 2.3 Gate
- 能對應到具體檔案與類別
- 有可執行的測試清單

---

## 3) 實作階段（Implementation）

### 3.1 實作步驟
1. 先改資料層（schema / manager class）
2. 再改服務層（驗證與商業規則）
3. 最後改介面層（admin page / dashboard）
4. 更新語系字串與版本號

### 3.2 變更紀錄位置
- 功能里程碑：`docs/FEATURE_LOG.md`
- 缺陷修復：`docs/BUGFIX_LOG.md`

### 3.3 Gate
- PHP 語法檢查通過（`php -l`）
- DB 升級路徑可執行

---

## 4) 測試階段（Test）

### 4.1 測試層級
- 開發者自測：語法、升級、基本流程
- 情境測試：依角色驗證功能可見性與操作權限
- 回歸測試：修 bug 後重跑相關流程

### 4.2 必測案例（目前）
- 場次建立：名稱固定為 `[等級]檢定課程`
- 考場下拉：僅顯示該等級且啟用中的考場
- 講師下拉：僅顯示該等級授權講師
- Moodle 課程：由後台對照自動帶入，不可手選
- 升級流程：不得出現 XMLDB 規範警告

### 4.3 Gate
- 使用者回覆「測試可／ok」才進入提交階段

---

## 5) 發佈與維護階段（Release & Maintenance）

### 5.1 版本規則
- 每次功能或修復更新 `version.php`（version/release）
- 重大資料庫變更必有 upgrade step

### 5.2 提交規範
- 規格先行（先改 PRD 再改程式）
- commit 前補齊 FEATURE_LOG / BUGFIX_LOG
- 未驗收前不推送正式維護分支
- 使用者回 `ok`／`測試無誤`／`測試無誤了` 後：**一定要** push 上 GitHub，並開／更新 PR（細節見 `DEV_WORKFLOW.md` §3）
- 上傳 Moodle 外掛 zip：Windows 一律 `tar.exe`（見 `DEV_WORKFLOW.md` §4），禁止 `Compress-Archive`

### 5.3 對話中的階段回報
- 分期尚未做完／測完：用白話簡述後續還要開發哪些階段（對齊 PRD §0.3）
- 全部開發且驗收完成：執行 commit → push → PR，不可省略

---

## 6) 缺陷管理模板（Defect Template）

每筆錯誤至少包含：
- 現象（Error message / 觸發路徑）
- 根因（Root cause）
- 修復（Fix）
- 影響範圍（檔案 / 流程）
- 驗證結果（如何確認已修復）

