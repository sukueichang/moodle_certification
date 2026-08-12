# P7 上架檢查清單（驗收後）

通知功能驗收 OK 後，再執行下列上架步驟（**使用者 ok 前不主動 push**）。

1. 確認 `version.php` release／version 正確（目前通知版：`0.7.0`）。
2. 更新 `moodle_certification_plugin_prd.md` 現況列為已驗收。
3. 依 `docs/RD_DEVELOPMENT_WORKFLOW.md` 寫入 FEATURE／BUGFIX 摘要。
4. Commit（訊息含 why）→ push `origin`。
5. GitHub Release：tag（如 `v0.7.0`）、附簡短 release notes（通知開關、審核雙收件人）。
6. 測試站：升級外掛、清 cache、確認 SMTP／`$CFG->noemailever`。

通知相關 Admin 路徑：**網站管理 → 外掛 → 本機外掛 → TM 檢定管理 → 通知**。
