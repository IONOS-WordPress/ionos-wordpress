---
# b55y
title: Test infrastructure (PHPUnit, E2E, prod/PHP-version parity)
status: completed
type: epic
priority: normal
created_at: 2026-08-03T11:07:14Z
updated_at: 2026-08-17T13:21:53Z
parent: p8wo
blocked_by:
  - hr03
---

Ephemeral test-stack container for PHPUnit and Playwright, always torn down pass or fail, plus
TEST_PRODUCTION dist-vs-source parity and PHP_VERSION_OVERRIDE for testing against the legacy
PHP 7.4 prebuilt image.

See docs/agent/wp-env-to-alpine-migration-plan.md, Phases 3-5.

## Summary of Changes

All child phases resolved: gjbp (Phase 5), 7hn5 (Phase 4), g4m1 (Phase 3) all completed.
