## Overview

Branch `feat/replace-wpenv` removes `@wordpress/env` (wp-env) and replaces it with a custom
Docker image, `wordpress-alpine` (`packages/docker/wordpress-alpine/`). The `pnpm start` /
`pnpm stop` / `pnpm destroy` / `pnpm test:*` command surface stays the same; almost everything
behind it changed. The design rationale lives in
[wp-env-to-alpine-migration-plan.md](agent/wp-env-to-alpine-migration-plan.md).

This page lists the changes in the branch ordered by impact on your day-to-day workflow, then
gives a migration checklist for pulling the branch.

## Changes, ordered by impact

### 1. wp-env is gone

`@wordpress/env` is removed as a dependency. `.wp-env.json`, `.wp-env.override.json`,
`scripts/wp-env.sh`, `scripts/wp-env-after-start.sh`, and `scripts/wp-env-after-destroy.sh` are
all deleted. A single all-in-one Alpine container (Apache, PHP, MariaDB, SSH, xdebug) now backs
`pnpm start`/`test`. You don't need to do anything by hand — the scripts regenerate everything —
but any local wp-env leftovers should be cleaned up (see checklist below).

### 2. `.env` variables changed

| Old (wp-env)        | New                                                   | Notes                                                                                                         |
| ------------------- | ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| `WP_ENV_HOME`       | `MNT_HOME`                                            | default `./mnt`; shared core cache at `./mnt/wordpress-core/<version>`, per-stack overlay at `./mnt/<stack>/` |
| `WP_ENV_CORE`       | `WORDPRESS_VERSION`                                   | same format (release version or `owner/repo#ref`), default `WordPress/WordPress#7.0.4`                        |
| `WP_ENV_START_OPTS` | _(removed, no replacement)_                           | —                                                                                                             |
| _(new)_             | `CONTAINER_NAME`                                      | default `ionos-wordpress-dev`                                                                                 |
| _(new)_             | `HTTP_PORT` / `TEST_HTTP_PORT`                        | default `8888` / `8889`                                                                                       |
| _(new)_             | `SSH_PORT`                                            | default `2222` — SSH into the dev container was not possible under wp-env                                     |
| _(new)_             | `IMAGE_REGISTRY` / `IMAGE_REPOSITORY`                 | default `ghcr.io` / `ionos-wordpress/wordpress-alpine-dev`                                                    |
| _(new)_             | `AFTER_START`                                         | defaults to `packages/docker/wordpress-alpine/after-start-ionos-wordpress.sh`; set empty for stock WordPress  |
| _(new, secrets)_    | `IMAGE_REGISTRY_USERNAME` / `IMAGE_REGISTRY_PASSWORD` | put in `.secrets`, only needed if the registry pull requires auth                                             |

### 3. Default PHP version bumped, new PHP-version override for tests

Default PHP moved 8.3 → 8.4. A new `PHP_VERSION_OVERRIDE=8.3` (set in `.env.local`) runs the test
suite against a prebuilt PHP 8.3 image pulled from the registry instead of building locally — this
represents the project's minimum supported PHP version. It's independent from
`TEST_PRODUCTION=true` (which switches source vs. dist mounts), and the two combine when needed.

The team originally targeted PHP 7.4 as the legacy floor but reverted to 8.3 because 7.4 needed
named-argument rewrites and polyfills, and broke compatibility with the `wordpress-mcp` plugin.

CI has since narrowed further: `9e6fbc0c` and `7606a25a` disabled the PHP 8.3 image-build/CI
variant, building PHP 8.4 only. If you rely on `PHP_VERSION_OVERRIDE=8.3`, confirm a matching
prebuilt image tag still exists before depending on it.

### 4. New top-level `pnpm` commands replace `pnpm wp-env`

| Command                       | Script                      | Purpose                                                                                                                                                       |
| ----------------------------- | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `pnpm cli <wp-cli args>`      | `scripts/cli.sh`            | run wp-cli inside the dev container as the `php` user                                                                                                         |
| `pnpm enter`                  | `scripts/enter.sh`          | open an interactive shell in the dev container                                                                                                                |
| `pnpm logs`                   | `scripts/logs.sh`           | tail the dev container's logs                                                                                                                                 |
| `pnpm purge-registry [--yes]` | `scripts/purge-registry.sh` | delete this repo's published GHCR container packages — destructive, dry-run by default, needs `GH_TOKEN` in `.secrets` with `read:packages`+`delete:packages` |
| `pnpm beans`                  | `scripts/beans.sh`          | wrapper around the Beans issue tracker                                                                                                                        |

### 5. Dev and test stacks are now separate containers

`pnpm start` only starts the persistent dev container (`ionos-wordpress-dev`). `pnpm test:*`
creates its own ephemeral container that's destroyed after the run, pass or fail — it no longer
reuses or disturbs your dev container. mu-plugin dual-mapping (loader file + subdirectory) is now
generated host-side into `./mnt/compose-<stack>/wp-content/mu-plugins/` (see the new
`scripts/includes/_docker-mounts.sh`). Container readiness is checked via `docker inspect`/
`docker ps` instead of `wp-env status --json`.

### 6. Playwright / e2e changes

`playwright/wp-env.js` is replaced by `playwright/exec-test-cli.js`, which `docker exec`s directly
into `TEST_CONTAINER_NAME` (default `ionos-wordpress-test`) instead of discovering the wp-env CLI
container. `playwright.config.js`'s `webServer.command` is now a no-op since `scripts/test.sh`
starts the container itself. E2E sharding is now supported via `E2E_SHARD_INDEX` /
`STORAGE_STATE_PATH`, giving each shard its own container/port/storage-state/report directory. The
HTML report output path moved from `playwright/storybook/.playwright-report` to
`playwright/e2e/.playwright-report` to avoid colliding with component-test reports.

### 7. Permission workarounds removed

The `chmod -R a+w` / "delete not owned by user" hacks that used to live in `start.sh`/`destroy.sh`,
and the in-container `sudo chmod` in `wp-env-after-start.sh`, are gone. The new image builds its
`php` user from `HOST_UID`/`HOST_GID` build args matching the host user, so container-written
files come back correctly owned. **If your host UID/GID ever changes (new machine, new user),
rebuild the image.**

### 8. Xdebug and `.vscode/launch.json` are now generated automatically

`pnpm start` now (re)generates `.vscode/launch.json`, and changing `WORDPRESS_VERSION` triggers an
automatic container recreation. Xdebug is baked into the image and always on — there's no toggle.
Don't hand-edit `.vscode/launch.json`; it's overwritten.

### 9. Devcontainer and CI speedups

The devcontainer now installs `ecs-php`, `rector-php`, `potrans`, and `dennis-i18n` natively
(`scripts/includes/_native-tools.sh`) instead of via separate Docker images, cutting
docker-in-docker round trips. The devcontainer image itself is pulled prebuilt rather than rebuilt
via buildx cache-import. Playwright now installs chromium-only browser deps. The pnpm store is
cached across CI runs. An escape hatch, `IONOS_WP_FORCE_DOCKER=1`, still forces the old dockerized
tool path (e.g. `IONOS_WP_FORCE_DOCKER=1 pnpm lint`) for local verification — but be aware CI no
longer exercises that path, so it can drift silently.

### 10. pnpm upgraded to 11.22.0, workspace config consolidated

`pnpm-workspace.yaml` gained a config block that used to live in `.npmrc`: `saveExact`,
`enablePrePostScripts`, `useNodeVersion: 24.18.0`, `storeDir: .pnpm-store`,
`scriptShell: /bin/bash`, and an `allowBuilds` allowlist for native postinstall scripts (e.g.
`@parcel/watcher`, `esbuild`). The `@wordpress/components` catalog entry bumped
`^35.0.0` → `^39.0.0`.

### 11. phpMyAdmin dropped, SSH added

Devcontainer port forwards `9000`/`9001` (wp-env's phpMyAdmin) are removed with no replacement —
use `pnpm cli` or `pnpm enter` for database access instead. In exchange, SSH port `2222` is now
exposed on the dev container.

### 12. Minor renames

- Essentials plugin: `tests/phppunit` → `tests/phpunit` (typo fix).
- The `wp-alpine` package was renamed `wordpress-alpine`.
- WordPress core default bumped 7.0 → 7.0.2 → 7.0.4.

### Internal refactors (no action needed)

A long series of `refactor(scripts): extract X helper, dedupe across N scripts` commits
(`print_help`, `container_running`/`container_exists`, `parse_use_flag`, `core_dir`,
`docker_image_name_for_package`, native/Docker fallback unification, and more) cleaned up the new
`scripts/` codebase without changing behavior, alongside repo-wide prettier passes and `.beans/`
ticket bookkeeping. None of these affect your workflow.

## Migration checklist

After pulling this branch:

1. Upgrade your global `pnpm` install to the latest version:
   `curl -fsSL https://get.pnpm.io/install.sh | sh -` — the workspace now requires pnpm `11.22.0`
   or newer.
2. `pnpm install` — picks up the removed `@wordpress/env` dependency, pnpm `11.22.0`, and the new
   `pnpm-workspace.yaml` settings (including Node `24.18.0` via `useNodeVersion`).
3. Delete any local `.wp-env.json`, `.wp-env.override.json`, and old wp-env home directory —
   replaced by `./mnt/` (configurable via `MNT_HOME`).
4. In `.env.local`, rename `WP_ENV_CORE`→`WORDPRESS_VERSION` and `WP_ENV_HOME`→`MNT_HOME` if you
   had them set. `WP_ENV_START_OPTS` has no replacement.
5. Replace any `pnpm wp-env ...` usage with `pnpm cli`, `pnpm enter`, `pnpm logs`, or
   `pnpm exec docker ...` depending on what you need.
6. Rebuild your devcontainer — native tool installation changed; a stale devcontainer image will
   be missing `ecs-php`/`rector-php`/`potrans`/`dennis-i18n`.
7. Make sure you can pull from `ghcr.io/ionos-wordpress/wordpress-alpine-dev`; only set
   `IMAGE_REGISTRY_USERNAME`/`IMAGE_REGISTRY_PASSWORD` in `.secrets` if the pull requires auth.
8. To test against the minimum supported PHP version, set `PHP_VERSION_OVERRIDE=8.3` (add
   `TEST_PRODUCTION=true` if testing dist output) — verify the PHP 8.3 image tag is still published
   before relying on it, since later commits reduced the 8.3 CI build variant.
9. Don't hand-edit `.vscode/launch.json` — it's regenerated by `pnpm start`.
10. If you referenced `tests/phppunit` in the essentials plugin, update to `tests/phpunit`.
11. If you use `IONOS_WP_FORCE_DOCKER=1` locally, know that this path is no longer exercised by CI.
