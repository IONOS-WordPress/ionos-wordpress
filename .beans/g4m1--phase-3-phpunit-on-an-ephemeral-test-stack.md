---
# g4m1
title: Phase 3 — PHPUnit on an ephemeral test stack
status: todo
type: task
priority: normal
created_at: 2026-08-03T10:59:25Z
updated_at: 2026-08-03T11:07:20Z
parent: b55y
blocked_by:
    - euw2
---

Goal: pnpm test:php starts a throwaway test container, runs PHPUnit inside it, and tears it
down unconditionally afterward.

## Tasks
- [ ] Rewrite the PHPUnit path in scripts/test.sh: start a fresh test-stack container (own
      compose project/name so it can't collide with the dev stack), wrap the run in a
      trap/finally so the container is destroyed on success and failure
- [ ] Replace the docker cp-based approach (pushing phpunit.xml/bootstrap.php/
      wp-tests-config.php in, pulling vendor/ out) with bind-mounting phpunit/ directly, since
      composer/phpunit deps are now baked into the image (Phase 1)
- [ ] Preserve the --exclude .../mu-plugins/stretch-extra filtering and per-file --filter
      behavior from current scripts/test.sh

## Exit criteria
pnpm test:php passes against current test suite, and confirms the container is gone
afterward regardless of pass/fail.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.
