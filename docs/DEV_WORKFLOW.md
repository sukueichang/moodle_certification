# DEV_WORKFLOW — 檢定外掛開發／修 Bug SOP

> **對齊** [`moodle_register` DEV_WORKFLOW](https://github.com/sukueichang/moodle_register/blob/main/docs/DEV_WORKFLOW.md)，並保留本外掛分期回報。  
> **文件角色：** 每次提需求或修 bug 時雙方（你與 AI）應遵循的標準流程。  
> **驗收訊號：** 你回覆 **`ok`**／**`測試無誤`**／**`測試無誤了`** → AI 才 commit → push → 開／更新 PR。  
> **相關：** [`../moodle_certification_plugin_prd.md`](../moodle_certification_plugin_prd.md)／[`FEATURE_LOG.md`](FEATURE_LOG.md)／[`BUGFIX_LOG.md`](BUGFIX_LOG.md)

---

## 0. 開工前（必做）

1. 讀 [`BUGFIX_LOG.md`](BUGFIX_LOG.md) 頂部「強制檢查清單」與近期條目。
2. 確認需求落在 [`../moodle_certification_plugin_prd.md`](../moodle_certification_plugin_prd.md)（或 [`P8_P10_ADDENDUM.md`](P8_P10_ADDENDUM.md)）或需先改規格再寫碼。
3. 在 [`FEATURE_LOG.md`](FEATURE_LOG.md) 追加「進行中」條目（需求＋初步決策）。

---

## 1. 提需求（功能）

| 步驟 | 你做 | AI 做 |
|------|------|--------|
| 1 | 說明目標、角色、驗收標準 | 釐清歧義；必要時對照 PRD／Addendum |
| 2 | 確認決策（或授權 AI 提方案） | 寫入 FEATURE_LOG；必要時更新 PRD／Addendum |
| 3 | — | 實作；遵守 BUGFIX_LOG 預防準則 |
| 4 | 在 Moodle 環境自行測試 | 依回報修正；問題寫入 BUGFIX_LOG |
| 5 | 回覆 **ok／測試無誤** | 見 §3 Git／PR／Push |

---

## 2. 回報 Bug

| 步驟 | 你做 | AI 做 |
|------|------|--------|
| 1 | 現象、重現步驟、期望行為 | 定位根因；對照 BUGFIX_LOG 是否舊疾 |
| 2 | — | 修復；**先**在 BUGFIX_LOG 記一條（現象／根因／解法／預防） |
| 3 | 回歸測試（含清單相關項） | 依回報再修 |
| 4 | 回覆 **ok／測試無誤** | 見 §3 |

---

## 3. 驗收後：Commit → PR → Push（僅在你說 ok 之後）

**原則：** 未明確要求 commit／push 前，AI **不**主動推遠端；本 SOP 以你的 **ok** 作為該輪工作的推送授權。

1. **整理變更**  
   - `git status`／`git diff`／近期 commit 風格  
   - 不納入 secrets；不改 git config  
2. **Commit**（訊息著重 why；必要時含版本 bump）  
3. **Push** 目前分支到 `origin`（`-u` 若尚未追蹤）  
4. **PR**  
   - 已有開啟中 PR：更新描述（Summary／Test plan），反映本輪 FEATURE／BUGFIX  
   - 尚無 PR：`gh pr create` 建立，並回報 PR URL  
5. **文件收尾**  
   - FEATURE_LOG 該節 →「已驗收」  
   - BUGFIX_LOG 已有條目則確認預防準則寫入檢查清單（若為新類型）

### PowerShell 注意（Windows）

- Commit message 可用 here-string，避免 bash heredoc：
  ```powershell
  git commit -m @"
  訊息內容。

  "@
  ```
- 打包外掛 zip：見 **§4**（一律 `tar.exe`，內部路徑 `/`）。

---

## 4. 打包外掛 zip（Windows／上傳 Moodle）

> 對齊 `moodle_register` BUGFIX：Windows PowerShell 的 `Compress-Archive`／`ZipFile::CreateFromDirectory` 會寫入 `\`，Moodle 無法偵測外掛類型。

1. **禁止** `Compress-Archive` 或 `[System.IO.Compression.ZipFile]::CreateFromDirectory`。
2. 用內建 `tar.exe` 打包（內部路徑一律 `/`）：
   ```powershell
   tar.exe -a -c -f local_tm_certification.zip -C "<專案根目錄>" local_tm_certification
   ```
3. 上傳前檢查：zip 內不得有任何含 `\` 的條目，且僅單一根目錄 `local_tm_certification`，並含 `version.php`：
   ```powershell
   Add-Type -AssemblyName System.IO.Compression.FileSystem
   $zip = [System.IO.Compression.ZipFile]::OpenRead("local_tm_certification.zip")
   ($zip.Entries | Where-Object { $_.FullName -match '\\' }).Count   # 必須是 0
   $zip.Dispose()
   ```
4. 若出現「無法偵測到外掛類型」／`Sorry, the type of the plugin could not be detected automatically`，**先查打包路徑格式**，再查程式碼。

細節與根因見 [`BUGFIX_LOG.md`](BUGFIX_LOG.md) 強制清單「Windows 打包 zip」與相關條目。

---

## 5. Moodle 本機驗證建議（最短路徑）

1. 將 `local_tm_certification` 放到 `{moodle}/local/tm_certification/`（或上傳 §4 的 zip 安裝）。
2. 升版：Site administration → Notifications（遠端 pluginfo `cURL` 錯誤可忽略，繼續本地 upgrade）。
3. 設定角色對應與 Dashboard 開關。
4. 各身分帳號開啟 `/local/tm_certification/index.php`；未授權帳號應被拒。
5. 若動到 DB：確認 upgrade 成功、無 XMLDB／表名錯誤。
6. 若動到 AJAX：確認 context／sesskey／權限。
7. 對照本輪功能入口（admin 選單／Dashboard）。

---

## 6. 對話中的階段回報（必做）

依當下對話／分期狀態二選一：

1. **還在開發中**（本階段或後續階段未完／未測完）  
   - 用**簡短白話**重述：接下來還要做哪些階段（對齊 PRD §0.3／Addendum，例如 P8 前置、P9 點名、P10 證書下載）。  
   - 說清楚「現在卡在哪／下一件是什麼」，不要只丟階段代號。

2. **已通通開發且測完**（使用者已回 `ok`／`測試無誤`）  
   - **一定要** commit → **push 上 GitHub** → **開 PR**（或更新既有 PR）。  
   - 未開 PR、未 push 視同流程未完成。  
   - 使用者 ok 前不主動 push／開 PR。

---

## 7. AI 每次開發自我檢查（摘要）

- [ ] BUGFIX_LOG 清單已掃  
- [ ] FEATURE_LOG／必要時 PRD／Addendum 已更新  
- [ ] 語系 en + zh_tw（本外掛另維 zh_cn 時一併同步）  
- [ ] `version.php` 若需升級已 bump  
- [ ] 未在使用者 ok 前 push  
- [ ] 使用者 ok 後：commit → push → 更新／建立 PR → 回報 URL  
- [ ] 若需上傳 Moodle：已依 §4 用 `tar.exe` 打包並通過反斜線檢查  

---

## 8. 文件地圖

| 檔案 | 用途 |
|------|------|
| [`../moodle_certification_plugin_prd.md`](../moodle_certification_plugin_prd.md) | 產品規格、分期、驗收 |
| [`P8_P10_ADDENDUM.md`](P8_P10_ADDENDUM.md) | P8～P10 追加規格 |
| [`FEATURE_LOG.md`](FEATURE_LOG.md) | 需求與決策時間軸 |
| [`BUGFIX_LOG.md`](BUGFIX_LOG.md) | 缺陷、強制檢查清單、回歸 |
| [`DEV_WORKFLOW.md`](DEV_WORKFLOW.md) | 本 SOP |
| [`RD_DEVELOPMENT_WORKFLOW.md`](RD_DEVELOPMENT_WORKFLOW.md) | RD 階段／Gate 細節 |
