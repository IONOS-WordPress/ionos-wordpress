---
# 9r7x
title: Rename S3 info descriptors to <slug>.info.json
status: completed
type: task
priority: normal
created_at: 2026-09-30T12:42:25Z
updated_at: 2026-09-30T12:44:21Z
---

Upload S3 flavoured descriptor as <plugin>.info.json (keeping <plugin>-info.json as legacy alias), point essentials Update URI and ionos-core INFO_JSON_URL at the new name. GitHub flavour untouched.

## Summary of Changes

- scripts/release.sh: S3 descriptor uploaded as <plugin>.info.json plus legacy alias <plugin>-info.json; baked-folder guard regex accepts both names; GitHub flavour untouched.
- essentials Update URI and ionos-core INFO_JSON_URL now point at .info.json; essentials UpdateTest S3_URL updated.
- Changeset added (patch, essentials + ionos-core). pnpm test:php passes (27 tests).
