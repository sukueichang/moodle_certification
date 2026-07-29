# DEV_WORKFLOW — 檢定外掛開發 SOP

> 對齊 `local_tm_course` 流程。  
> **驗收訊號：** 回覆 `ok`／`測試無誤` → 才 commit／push／更新 PR。

## 提需求

1. 對照 [`../moodle_certification_plugin_prd.md`](../moodle_certification_plugin_prd.md)
2. 寫入 FEATURE_LOG（進行中）
3. 實作 → 測試站驗證 → ok 後推 GitHub

## 本機最短驗證

1. 將 `local_tm_certification` 放到 `{moodle}/local/tm_certification/`
2. Notifications 升版
3. 設定角色對應與 Dashboard 開關
4. 各身分帳號開啟 `/local/tm_certification/index.php`
5. 未授權帳號應被拒
