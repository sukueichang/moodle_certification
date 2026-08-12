# DEV_WORKFLOW — 檢定外掛開發 SOP

> 對齊 `local_tm_course` 流程。  
> **驗收訊號：** 回覆 `ok`／`測試無誤` → 才 commit／push／開或更新 PR。

## 提需求

1. 對照 [`../moodle_certification_plugin_prd.md`](../moodle_certification_plugin_prd.md)
2. 寫入 FEATURE_LOG（進行中）
3. 實作 → 測試站驗證 → ok 後推 GitHub（含 PR）

## 對話中的階段回報（必做）

依當下對話／分期狀態二選一：

1. **還在開發中**（本階段或後續階段未完／未測完）  
   - 用**簡短白話**重述：接下來還要做哪些階段（對齊 PRD §0.3，例如 P8 前置、P9 點名、P10 證書下載）。  
   - 說清楚「現在卡在哪／下一件是什麼」，不要只丟階段代號。

2. **已通通開發且測完**（使用者已回 `ok`／`測試無誤`）  
   - **一定要** commit → **push 上 GitHub** → **開 PR**（或更新既有 PR）。  
   - 未開 PR、未 push 視同流程未完成。  
   - 使用者 ok 前不主動 push／開 PR。

## 本機最短驗證

1. 將 `local_tm_certification` 放到 `{moodle}/local/tm_certification/`
2. Notifications 升版
3. 設定角色對應與 Dashboard 開關
4. 各身分帳號開啟 `/local/tm_certification/index.php`
5. 未授權帳號應被拒
