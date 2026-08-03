---
# 7hn5
title: Phase 4 — Playwright/E2E against the same ephemeral test stack
status: todo
type: task
created_at: 2026-08-03T10:59:25Z
updated_at: 2026-08-03T10:59:25Z
parent: 4tjo
blocked_by:
    - g4m1
---

Goal: pnpm test:e2e reuses the Phase 3 ephemeral test container.

## Tasks
- [ ] Update playwright.config.js: baseURL points at the ephemeral test stack's dynamically
      assigned (or fixed) port instead of wp-env's hardcoded localhost:8889
- [ ] Rewrite playwright/wp-env.js's execTestCLI to docker exec into the new test container
      (single container now, no more tests-cli-1 container-name discovery via
      `wp-env status --json`)
- [ ] Preserve global-setup.js behavior (RequestUtils auth/storage state, theme/plugin
      activation) — should need no changes beyond the base URL/port
- [ ] Ensure scripts/test.sh's pre-e2e admin-password-reset step still works against the new
      container
- [ ] Tie into the same ephemeral start→run→teardown wrapper from Phase 3: a single
      `pnpm test` invocation brings up one shared ephemeral test-stack container and runs both
      PHPUnit and Playwright against it, tearing it down once at the end (pass or fail)

## Exit criteria
pnpm test:e2e passes against the current Playwright suite; container is torn down after.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.
