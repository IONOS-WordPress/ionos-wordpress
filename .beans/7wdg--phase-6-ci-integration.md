---
# 7wdg
title: Phase 6 — CI integration
status: completed
type: task
priority: normal
created_at: 2026-08-03T10:59:36Z
updated_at: 2026-08-05T12:36:51Z
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

## CI validation

Pushed feat/replace-wpenv (first push of the whole wp-env→wp-alpine migration
branch) and opened PR #910 as a draft to validate against real GitHub Actions,
since "CI green on a branch" can't be reproduced in the local sandbox. Took
several iterations to get there - each surfaced a real, previously-undetected
bug rather than a CI-workflow-only issue:

1. **UID mismatch** (`fix: remap php user to the runtime host uid/gid`): a
   pulled/prebuilt image bakes in whatever uid built it in the publish
   workflow, unrelated to whoever runs it - files written into host bind
   mounts came back owned by a uid the consuming host couldn't clean up. This
   was a latent bug in gjbp's PHP_VERSION_OVERRIDE too, undetected there only
   because the local dev machine's uid happened to match the image default.
   Fixed by remapping the php user to a HOST_UID/HOST_GID runtime env var in
   docker-entrypoint.sh (skipped if already matching), with start.sh/test.sh
   always passing the current host's uid/gid.
2. **Stale ecs-php lint exclusion**: ECS's skip list only ever excluded the
   old wp-env-home/ directory, never updated for mnt/ (introduced in Phase 2)
   - recursively linted a populated mnt/ as project source, timing out
     against WordPress core itself. Added `*/mnt/*` to the skip list, plus
     applied unrelated pre-existing repo-wide Prettier drift pnpm lint-fix
     picked up along the way (CI's lint job checks the whole repo
     unconditionally).
3. **Readiness timeout too tight**: a cold run (fresh core download, no
   shared cache yet) inside CI's nested docker-in-docker devcontainer is
   slower than local - widened scripts/test.sh's wait budget from 60s to
   180s and added a docker logs dump on failure for future diagnosability.
4. **PHP_VERSION_OVERRIDE without TEST_PRODUCTION**: source is written
   against PHP 8+ syntax; only rector's transpiled dist/ output is meant to
   run under PHP 7.4. My own local verification of PHP_VERSION_OVERRIDE in
   gjbp never caught this because it unwittingly retagged a local PHP 8.4
   build as `-php7.4` on a scratch registry, never exercising a real PHP 7.4
   interpreter. Added a hard guard in scripts/test.sh (fails fast with a
   clear message) and fixed the CI job to always pass both flags together.
5. **Real PHP 7.4 incompatibilities**, only surfaced once tests actually ran
   against a genuine PHP 7.4 interpreter (not a mislabeled 8.4 image):
   - `wordpress-develop` trunk's own PHPUnit bootstrap and
     `wp-tests-config.php` call `str_starts_with()` (PHP 8.0+) before
     WordPress core's own polyfills ever load - fixed by adding
     `symfony/polyfill-php80` and loading it via `auto_prepend_file` on the
     PHP 7.x image variant only.
   - `ClassNBATest.php` used PHP 8.0+ named-argument syntax - a hard parse
     error, since phpunit/ test dirs are bind-mounted from source and never
     rector-transpiled even under PHP_VERSION_OVERRIDE (only the actual
     plugin code is). Switched to positional arguments.
   - The `@mcp` e2e test depends on a third-party plugin
     (Automattic/wordpress-mcp) downloaded fresh at test time, entirely
     outside our own transpile pipeline and not PHP 7.4-compatible -
     excluded on the PHP 7.4 CI leg specifically, since validating
     third-party plugins isn't this leg's purpose.

Final result: PR #910's `integration` workflow green across all 4 jobs
(`devcontainer`, `lint`, `build and test`, `test against legacy PHP 7.4`) -
https://github.com/IONOS-WordPress/ionos-wordpress/actions/runs/31005797267
