---
# 7wdg
title: Phase 6 — CI integration
status: in-progress
type: task
priority: normal
created_at: 2026-08-03T10:59:36Z
updated_at: 2026-08-05T09:07:08Z
parent: dav1
blocked_by:
  - gjbp
---

Goal: GitHub Actions pulls the prebuilt GHCR image instead of running wp-env inside a
docker-in-docker devcontainer.

## Tasks

- [x] Update .github/workflows/integration.yaml: the build job pulls
      ${IMAGE_REGISTRY}/${IMAGE_REPOSITORY}:<hash>-php8.4 (same repo vars/secrets as the Phase 1
      publish workflow — no hardcoded ghcr.io path) and runs `pnpm run build` +
      `TEST_PRODUCTION=true pnpm run test` against it; a separate CI job/matrix leg sets
      PHP_VERSION_OVERRIDE=7.4 to pull the prebuilt :<hash>-php7.4 tag and run the suite against
      it on every PR update, without changing what the default build job pulls/publishes
- [x] Keep the docker-in-docker devcontainer feature as-is — out of scope for this migration.
      Only update port labels/exposed ports in .devcontainer/devcontainer.json (drop 9000/9001
      phpmyadmin, adjust 8888/8889 if renumbered)

## Exit criteria

CI green on a branch, full parity with current integration.yaml results.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.

## Summary of Changes

- `.github/workflows/integration.yaml`: added a "pull prebuilt wp-alpine image" step
  in the `build` job that pulls `${IMAGE_REGISTRY}/${IMAGE_REPOSITORY}:<hash>-php8.4`
  (same repo vars/defaults as the Phase 1 publish workflow) and retags it locally as
  `ionos-wordpress/wp-alpine:<version>` + `:latest` plus a `build-info` marker, so
  `scripts/build.sh`'s existing "already exists locally" check skips rebuilding it -
  falls back to today's local build if the pull fails (no hard dependency on the
  publish workflow's timing). Added a new `test-php74` job (installs, builds, then
  runs `PHP_VERSION_OVERRIDE=7.4 pnpm run test`, which pulls its own prebuilt tag via
  the scripts/test.sh logic from gjbp) so the legacy-PHP path runs on every push
  alongside the default PHP 8.4 leg.
- `.github/shared/actions/devcontainer-shell-run/action.yaml`: added an `env` input
  (newline-separated KEY=VALUE) forwarded into the underlying `devcontainers/ci`
  action, since nothing was previously wired to pass registry credentials/vars from
  the runner into the nested docker-in-docker devcontainer.
- `scripts/test.sh`: added a 5-attempt retry (30s apart) around the
  `PHP_VERSION_OVERRIDE` image pull, since a push that touches both
  `packages/docker/wp-alpine/**` and triggers `integration.yaml` can race the
  separate `build-wp-alpine-image.yaml` publish job tagging that same commit.
- `.devcontainer/devcontainer.json`: dropped the 9000/9001 phpMyAdmin port labels
  (dropped per the locked-in migration decisions) and renamed the 8888/8889 labels
  from "wp-env" to "wp-alpine" instance.

**Not fully verified locally** - this phase's exit criteria is "CI green on a
branch", which by definition requires an actual GitHub Actions run against the
real registry/GITHUB_TOKEN and the nested docker-in-docker devcontainer, none of
which can be reproduced in this sandbox. Pushed `feat/replace-wpenv` (first push of
this entire migration branch) and opened a PR to validate; see PR discussion for
the outcome and any follow-up fixes.
