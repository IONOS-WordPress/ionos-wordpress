---
# 04ug
title: Address remaining Copilot review findings on S3 release PR (review 5276449818)
status: completed
type: task
priority: normal
created_at: 2026-09-22T09:55:04Z
updated_at: 2026-09-22T09:55:07Z
parent: ig4m
---

Fix two open Copilot findings on PR #940: (1) failed GitHub <plugin>-info.json upload was best-effort and could strand stale descriptors, (2) missing AWS credentials caused the release to abort mid-promotion instead of degrading gracefully to github-only, contradicting s3_upload's documented non-fatal credential behavior.

## Summary of Changes

- Made the GitHub-flavoured `<plugin>-info.json` upload fatal on failure (was best-effort), aborting promotion instead of leaving github-sourced installations on a stale/missing descriptor.
- Added an AWS credential preflight (`S3_MIRRORING_ENABLED`) before any GitHub mutation, so a run with no AWS secrets configured degrades cleanly to github-only publishing instead of hitting the same abort path used for genuine s3 upload failures partway through promotion.
