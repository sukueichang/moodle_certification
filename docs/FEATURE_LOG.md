# FEATURE_LOG — 檢定外掛

## 進行中／已完成

### 2026-07-29 — P1 骨架與權限／Dashboard 殼

- **需求：** 建立 `local_tm_certification`；權限不寫死；Dashboard 可開關；三角色視圖殼；我的證書頁殼。
- **決策：** 身分判定＝capability **或** 設定頁指派的 Moodle role id；Site admin 對 manage／approve 視為具備。
- **版本／狀態：** 0.1.0（2026072900）；**進行中（待測試站驗收）**

### 2026-07-29 — P1.1 首頁儀表板未顯示修正

- **現象：** Dashboard 顯示開關皆開啟，但網站首頁看不到儀表板。
- **根因：** 0.1.0 僅有獨立頁 `/local/tm_certification/index.php`，尚未像 `local_tm_course` 注入 frontpage／My home。
- **解法：** 新增 `before_standard_top_of_body_html` 注入首頁區塊；加強導覽；設定頁說明出現位置。
- **版本／狀態：** 0.1.1（2026072901）；**已驗收**（使用者：測試可以了）
