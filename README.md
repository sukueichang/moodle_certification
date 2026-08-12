# TM Certification Plugin (`local_tm_certification`)

Moodle local plugin for Techman certification exam operations.

## Status

- **Release:** 0.2.0 (P2 sessions + enrol — pending test)
- **GitHub:** https://github.com/sukueichang/moodle_certification (private)
- Spec: [`../moodle_certification_plugin_prd.md`](../moodle_certification_plugin_prd.md)
- UI preview (unified): [`../docs/ui_preview.html`](../docs/ui_preview.html)

## Install

1. Copy `local_tm_certification/` to `{moodle}/local/tm_certification/`
2. Site administration → Notifications (upgrade)
3. Site administration → Plugins → Local plugins → **TM 檢定管理**
   - Map Moodle roles to logical identities
   - Toggle dashboard visibility
4. Open `/local/tm_certification/index.php`

## Capabilities

| Capability | Purpose |
|---|---|
| `local/tm_certification:manage` | Settings, future direct session create |
| `local/tm_certification:approve` | Approve applications |
| `local/tm_certification:instruct` | Instructor features |
| `local/tm_certification:examinee` | Examinee features |
| `local/tm_certification:viewdashboard` | Open dashboard |

Role shortnames are **not** hard-coded — assign via plugin settings and/or Define roles.

## P1 included

- Plugin scaffold (en / zh_tw)
- Permissions manager (capability **or** configured role ids)
- Dashboard on/off + per-view toggles
- Dashboard shells: manage / instruct / examinee
- My certificates page (empty shell)

## Next (P2+)

Sessions, applications, batch enrol, candidate IDs, practical ↔ attendance, certificates.
