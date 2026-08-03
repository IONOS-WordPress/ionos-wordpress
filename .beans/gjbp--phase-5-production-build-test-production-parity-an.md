---
# gjbp
title: Phase 5 — Production-build (TEST_PRODUCTION) parity and CI custom-PHP-version testing
status: todo
type: task
priority: normal
created_at: 2026-08-03T10:59:36Z
updated_at: 2026-08-03T11:07:20Z
parent: b55y
blocked_by:
    - 7hn5
---

Goal: preserve the ability to run the full test suite against transpiled dist/ output, and add
a way to run tests against a non-default PHP version without reintroducing a maintained
multi-version image matrix.

## Tasks
- [ ] Port the current .wp-env.override.json mapping-rewrite logic into the Phase 2/3 mount
      generator: when TEST_PRODUCTION=true is set, point generated bind mounts at
      packages/wp-plugin/<name>/dist/... instead of source, and rsync phpunit/ test dirs into
      the dist folders as today. Purely a source-vs-dist mount switch — unrelated to PHP version
- [ ] Add PHP_VERSION_OVERRIDE=<php-version> as a separate mechanism: Dockerfile keeps its
      ARG_PHP_VERSION build-arg, Phase 1 publish workflow builds/pushes a small prebuilt matrix
      (8.4 default, 7.4 legacy). When PHP_VERSION_OVERRIDE=7.4 is set, scripts/test.sh pulls the
      matching prebuilt tag instead of building a local image, and runs the ephemeral test stack
      against it — no build step on the hot path since this runs on every PR update and locally

## Exit criteria
TEST_PRODUCTION=true pnpm test passes, matching current CI behavior;
PHP_VERSION_OVERRIDE=7.4 pnpm test runs the suite against the prebuilt PHP 7.4 image with no
local build step, locally and in CI.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.
