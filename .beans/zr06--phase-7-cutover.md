---
# zr06
title: Phase 7 — Cutover
status: todo
type: task
priority: normal
created_at: 2026-08-03T10:59:47Z
updated_at: 2026-08-03T11:07:20Z
parent: dav1
blocked_by:
    - 7wdg
---

Goal: remove wp-env entirely.

## Tasks
- [ ] Delete .wp-env.json, .wp-env.override.json generation code, @wordpress/env dependency,
      scripts/wp-env-after-start.sh, scripts/wp-env-after-destroy.sh, scripts/wp-env.sh
- [ ] Update docs: docs/1-setup.md, docs/5-test.md, docs/agent/e2e-testing.md, and any
      AGENTS.md/docs/agent references to wp-env
- [ ] Update scripts/playground.sh (currently reads core/phpVersion from .wp-env.json) to read
      from the new config source instead
- [ ] Final regression pass: fresh clone → pnpm install → pnpm start → pnpm test end-to-end,
      plus a full CI run

## Exit criteria
No wp-env references remain in the repo (grep-clean), CI green, docs updated.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.
