---
# aor5
title: 'test-mu-plugin pilot: align the documented update checker with the S3 pattern'
status: todo
type: task
priority: low
created_at: 2026-09-16T12:40:05Z
updated_at: 2026-09-16T12:40:05Z
parent: ig4m
---

`docs/packages/wp-mu-plugin/test-mu-plugin` is the documented copy-paste reference for the mu-plugin update mechanism. It must show the new S3-first pattern, otherwise the documentation drifts away from the shipped code.

## Todos

- [ ] Update `docs/packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin/inc/update/index.php` to the S3-first / GitHub-fallback pattern
- [ ] Update the `Update URI` header in `docs/packages/wp-mu-plugin/test-mu-plugin/test-mu-plugin.php`
- [ ] Keep it consistent with the ionos-core implementation, since that is the code it documents
