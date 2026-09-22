---
# 62p0
title: Re-verify S3 release end to end after latest release.sh hardening fixes
status: in-progress
type: task
priority: normal
created_at: 2026-09-22T10:06:40Z
updated_at: 2026-09-22T10:07:14Z
parent: ig4m
---

feat/replace-github-s3 got 4 new commits (df6c5c17, 90779cca, 18edebf8, 53b978ff - hardening release.sh S3 mirroring, S3_FOLDER alphabet validation, array_all() replacement) since the last end-to-end fork verification in v31s. Re-run the fork release runbook (docs/7-release.md 'Test-phase releases in a fork') to confirm pre-release and release still work with S3_FOLDER=test on this fork before opening/updating the upstream PR.

## Progress

- Confirmed branch `feat/replace-github-s3` is clean, up to date with origin, and contains the 4 target commits (df6c5c17, 90779cca, 18edebf8, 53b978ff) plus history matching bean v31s precedent.
- Changesets present: `ionos-core-s3-update-fallback.md`, `ionos-essentials-s3-update-fallback.md`.
- Next: force-push to fork main, watch pre-release workflow.
