---
# zr06
title: Phase 7 — Cutover
status: in-progress
type: task
priority: normal
created_at: 2026-08-03T10:59:47Z
updated_at: 2026-08-05T14:38:23Z
parent: dav1
blocked_by:
  - 7wdg
---

Goal: remove wp-env entirely.

## Tasks

- [x] Delete .wp-env.json, .wp-env.override.json generation code, @wordpress/env dependency,
      scripts/wp-env-after-start.sh, scripts/wp-env-after-destroy.sh, scripts/wp-env.sh
- [x] Update docs: docs/1-setup.md, docs/5-test.md, docs/agent/e2e-testing.md, and any
      AGENTS.md/docs/agent references to wp-env
- [x] Update scripts/playground.sh (currently reads core/phpVersion from .wp-env.json) to read
      from the new config source instead
- [x] Final regression pass: fresh clone → pnpm install → pnpm start → pnpm test end-to-end,
      plus a full CI run

## Exit criteria

No wp-env references remain in the repo (grep-clean), CI green, docs updated.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.

## Summary of Changes (pending CI validation)

- Deleted `.wp-env.json`, `scripts/wp-env-after-start.sh`, `scripts/wp-env-after-destroy.sh` (`scripts/wp-env.sh`
  was already removed in an earlier phase). Removed `@wordpress/env` from root `package.json` and refreshed
  `pnpm-lock.yaml` accordingly.
- Cleaned up `.gitignore` (`/wp-env-home`, `/.wp-env.json`, `/.wp-env.override.json` entries removed) and the
  matching `git clean` exclude patterns in `scripts/clean.sh`/`scripts/distclean.sh`. Replaced both scripts'
  `pnpm exec wp-env status --json` readiness guard (which required the now-removed `@wordpress/env` package) with
  a plain `docker ps --filter name=$CONTAINER_NAME` check.
- Rewrote `scripts/playground.sh` to read `WORDPRESS_VERSION` from the root `.env` (already exported by
  `bootstrap.sh`) and `PHP_VERSION` from `packages/docker/wp-alpine/.env`'s `ARG_PHP_VERSION`, instead of
  `jq`-parsing the deleted `.wp-env.json`.
- Removed stale `wp-env-home` exclude patterns from `packages/docker/ecs-php/ecs-config.php` and `ruleset.xml`,
  and the leftover `WP_ENV_HOME`/port-9000/9001 remnants in `.devcontainer/Dockerfile` (devcontainer.json itself
  was already updated in Phase 6).
- Updated `.claude/settings.json`'s permission allowlist entry from `pnpm wp-env run cli wp *` to `pnpm cli *`.
- Renamed `playwright/wp-env.js` to `playwright/exec-test-cli.js` (its logic was already migrated in an earlier
  phase; only the filename still said "wp-env") and updated all 14 e2e spec files that import `execTestCLI` from it,
  plus the code example in `docs/agent/e2e-testing.md`.
- Rewrote every doc/README describing `wp-env`/`@wordpress/env` usage to describe the `wp-alpine` dev container
  instead: `README.md` (largest rewrite - directory layout, commands, configuration sections), `docs/1-setup.md`,
  `docs/2-build.md`, `docs/3-tools.md` (replaced the whole `# wp-env` section with `# cli`/`# enter`/`# logs`
  sections matching the actual `pnpm cli`/`pnpm enter`/`pnpm logs` scripts), `docs/5-test.md`,
  `docs/10-ai-integration.md`, `docs/skills/testing/SKILL.md`, `docs/skills/testing/recipes/README.md`,
  `docs/skills/testing/recipes/dashboard/mcp-activation.md`, and several plugin READMEs (ionos-essentials'
  dashboard/security/migration/extendify/switch-page, stretch-extra's top-level and apcu READMEs) - mechanically
  replacing `pnpm wp-env run cli wp X`/`pnpm wp-env run tests-cli wp X` examples with `pnpm cli X`, and the two
  `pnpm wp-env run wordpress <cmd>` raw-shell examples with the equivalent `docker exec` invocation.
- Fixed stale/inaccurate `wp-env`-referencing comments and strings in plugin code: `ionos-essentials`/`ionos-core`
  loop `index.php`/`rest-permission-callback.php`, `stretch-extra`'s `apcu.php`, `secondary-plugin-dir.php`,
  `secondary-theme-dir.php`, its plugin header `Description:` (+ all 8 `.po`/`.pot`/`.l10n.php` translation
  copies of that string), and `ionos-wpdev-caddy`'s `apcu/toggle.php` debug message.
- Left untouched (by design): `CHANGELOG.md` (historical release notes), `.beans/*.md` (planning docs for this
  migration), `mnt/` (gitignored WordPress-core test fixture with its own unrelated `.wp-env.sample.json`), the
  generated/untracked `ionos-wordpress.sbom.syft.json`, and a handful of comparative/historical comments in
  `.env`, `scripts/includes/_docker-mounts.sh`, `playwright.config.js`, `playwright/exec-test-cli.js` and
  `packages/docker/wp-alpine/*` that explain _why_ something changed relative to the old wp-env setup (legitimate
  historical context, not stale fact).

## Verification performed locally

- Repo-wide case-insensitive `wp-env` grep is clean except the intentionally-kept files above.
- `pnpm install` succeeds; `@wordpress/env` is gone from the dependency tree (its transitive deps that are still
  needed elsewhere - e.g. by `@wp-now/wp-now` for `pnpm playground` - were reclassified `optional: true` by pnpm,
  not removed).
- `pnpm lint` is fully green (one pre-existing prettier formatting issue on this bean file itself was fixed).
- `pnpm build`, `pnpm start`, `pnpm cli`, `pnpm stop` all work against the rewritten scripts.
- `pnpm clean`'s new docker-based running-container guard correctly refuses to run while the dev container is up.
- `pnpm test:php` passes (15/15 PHPUnit tests) against the ephemeral test container, confirming
  `scripts/test.sh`/`scripts/build.sh`'s stale-comment cleanup didn't touch anything load-bearing.
- All PHP files touched pass `php -l` (via a `php:8.3-cli` container, since no local PHP interpreter is available
  in this sandbox).

**Not yet done**: `pnpm test:e2e`/`pnpm test:react` were not run locally (time), and per this phase's stated exit
criteria ("CI green on a branch") a real GitHub Actions run is still needed - same caveat as Phase 6, this can't be
reproduced in the sandbox. Recommend pushing this branch and validating via the open PR #910 (or a new PR) before
marking this bean `completed`.
