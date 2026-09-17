---
# aor5
title: 'test-mu-plugin pilot: align the documented update checker with the S3 pattern'
status: completed
type: task
priority: low
created_at: 2026-09-16T12:40:05Z
updated_at: 2026-09-17T13:30:43Z
parent: ig4m
---

`docs/packages/wp-mu-plugin/test-mu-plugin` is the documented copy-paste reference for the mu-plugin update mechanism. It must show the new S3-first pattern, otherwise the documentation drifts away from the shipped code.

## Todos

- [x] Update `docs/packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin/inc/update/index.php` to the S3-first / GitHub-fallback pattern
- [x] Update the `Update URI` header in `docs/packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin.php`
- [x] Keep it consistent with the ionos-core implementation, since that is the code it documents

## Summary of Changes

- `test-mu-plugin.php`: `Update URI` header changed from the GitHub-only URL to the S3 URL with the `__S3_FOLDER__` placeholder, matching essentials/ionos-core.
- `inc/update/index.php`: added a `LEGACY_INFO_JSON_URL` GitHub-fallback constant and a `fetch_update_info($update_uri)` resolver (tries the header's S3 url first, falls back to GitHub on transport error, non-200 status, empty body, or invalid/incomplete json), replacing the old single-source fetch. `check_for_update()` now calls this resolver instead of inlining the HTTP/JSON checks; `install_update()` and `record_check_result()` are unchanged. Updated the file's header comment to point at ionos-core's implementation instead of the stale 'other mu-plugins stay download-only' claim.
- No changeset - this lives under `docs/packages/`, not a released workspace package.
