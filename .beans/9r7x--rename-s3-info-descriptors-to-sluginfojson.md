---
# 9r7x
title: Rename S3 info descriptors to <slug>.info.json
status: completed
type: task
priority: normal
created_at: 2026-09-30T12:42:25Z
updated_at: 2026-09-30T12:44:21Z
---

Upload S3 flavoured descriptor as <plugin>.info.json only (no legacy <plugin>-info.json alias: no released plugin resolves its update from an S3 -info.json url yet), point essentials Update URI and ionos-core INFO_JSON_URL at the new name. GitHub flavour untouched.

## Summary of Changes

- scripts/release.sh: S3 descriptor uploaded as <plugin>.info.json only; the baked-folder guard rejects artifacts still carrying the legacy <plugin>-info.json s3 url (they must be rebuilt); GitHub flavour untouched.
- docs/7-release.md: S3 descriptor name updated.
- essentials Update URI and ionos-core INFO_JSON_URL now point at .info.json; essentials UpdateTest S3_URL updated.
- Changeset added (patch, essentials + ionos-core). pnpm test:php passes (27 tests).
