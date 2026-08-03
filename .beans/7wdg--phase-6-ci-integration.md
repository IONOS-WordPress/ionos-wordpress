---
# 7wdg
title: Phase 6 — CI integration
status: todo
type: task
created_at: 2026-08-03T10:59:36Z
updated_at: 2026-08-03T10:59:36Z
parent: 4tjo
blocked_by:
    - gjbp
---

Goal: GitHub Actions pulls the prebuilt GHCR image instead of running wp-env inside a
docker-in-docker devcontainer.

## Tasks
- [ ] Update .github/workflows/integration.yaml: the build job pulls
      ${IMAGE_REGISTRY}/${IMAGE_REPOSITORY}:<hash>-php8.4 (same repo vars/secrets as the Phase 1
      publish workflow — no hardcoded ghcr.io path) and runs `pnpm run build` +
      `TEST_PRODUCTION=true pnpm run test` against it; a separate CI job/matrix leg sets
      PHP_VERSION_OVERRIDE=7.4 to pull the prebuilt :<hash>-php7.4 tag and run the suite against
      it on every PR update, without changing what the default build job pulls/publishes
- [ ] Keep the docker-in-docker devcontainer feature as-is — out of scope for this migration.
      Only update port labels/exposed ports in .devcontainer/devcontainer.json (drop 9000/9001
      phpmyadmin, adjust 8888/8889 if renumbered)

## Exit criteria
CI green on a branch, full parity with current integration.yaml results.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.
