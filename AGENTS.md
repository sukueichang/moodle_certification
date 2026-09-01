# AGENTS — local_tm_certification

AI coding agents: follow this repo’s existing SOP. Do not invent a parallel process.

## Before any code change

1. Read [`docs/BUGFIX_LOG.md`](docs/BUGFIX_LOG.md) top checklist + recent entries.
2. Confirm scope against [`moodle_certification_plugin_prd.md`](moodle_certification_plugin_prd.md) or [`docs/P8_P10_ADDENDUM.md`](docs/P8_P10_ADDENDUM.md).
3. Append an in-progress entry to [`docs/FEATURE_LOG.md`](docs/FEATURE_LOG.md) (or a BUGFIX entry when fixing a defect).

Full SOP: [`docs/DEV_WORKFLOW.md`](docs/DEV_WORKFLOW.md). RD gates: [`docs/RD_DEVELOPMENT_WORKFLOW.md`](docs/RD_DEVELOPMENT_WORKFLOW.md).

## Acceptance gate

- User replies **`ok`** / **`測試無誤`** / **`測試無誤了`** → then commit → push → open/update PR.
- Until that signal: do **not** commit, push, or open a PR unless the user explicitly asks.

## Package Moodle zip (Windows — hard rule)

Moodle rejects zips whose internal paths use `\`. On Windows:

1. Use **only** `tar.exe` (see `DEV_WORKFLOW.md` §4).
2. Never use `Compress-Archive` or `[System.IO.Compression.ZipFile]::CreateFromDirectory`.
3. Before upload, verify zero zip entries contain `\`.

```powershell
tar.exe -a -c -f local_tm_certification.zip -C "<repo-root>" local_tm_certification
```

If Moodle says it cannot detect the plugin type, check zip path separators first.

## Implementation invariants

- Do not hard-code Moodle role shortnames; use plugin settings / capabilities.
- Bump `local_tm_certification/version.php` when DB or behaviour needs an upgrade.
- Keep `lang/en` + `lang/zh_tw` in sync (and `zh_cn` when that tree is maintained).
- After features/bugs: update FEATURE_LOG / BUGFIX_LOG; keep preventive rules in the BUGFIX checklist.

## Gradebook empty / students missing

Remind: Site admin → Grades → General settings → **Roles that are graded** (`gradebookroles`) must include student roles. See also BUGFIX `attendance_update_users_grade` (0.11.10).
