---
# mrwg
title: Phase 1 — Base image, adapted from the prototype
status: completed
type: task
priority: normal
created_at: 2026-08-03T10:59:14Z
updated_at: 2026-08-04T12:08:23Z
parent: vjbx
---

Goal: an ionos-wordpress-specific Alpine image building on /opt/dev/wordpress-docker-image's
Dockerfile, published to GHCR.

## Tasks
- [x] Fork the Dockerfile into this repo (e.g. packages/docker/wp-alpine/Dockerfile), default
      ARG_PHP_VERSION to PHP 8.4. Keep the build-arg, but shrink the published matrix to exactly
      two tags — 8.4 (default) and 7.4 (legacy, for PHP_VERSION_OVERRIDE)
- [x] Bake into entrypoint/image (replacing wp-env-after-start.sh):
  - [x] yoast/phpunit-polyfills composer install
  - [x] xdebug + APCu ini patch
  - [x] .vscode/launch.json generation logic (host-side, generated at container-start time)
  - [x] wp-cli bootstrap: structure/flush, brand options, admin password, plugin/theme activation
- [x] Extend entrypoint for git-ref core installs (risk #1 in plan doc)
- [x] Add AFTER_START support: optional host script from .env, bind-mounted, executed as php
      user via doas (keepenv)
- [x] Set up image publish workflow (.github/workflows/build-wp-alpine-image.yaml): triggered on
      Dockerfile/entrypoint changes, tags by content hash, builds/pushes both PHP variants as
      :<hash>-php8.4 and :<hash>-php7.4 to ${IMAGE_REGISTRY}/${IMAGE_REPOSITORY} (default GHCR).
      Registry/repo from repo Actions variables; auth from repo secrets — never hardcode ghcr.io.

## Exit criteria
docker run each of the two built image variants manually, confirm WP installs, wp-cli/Apache/
MariaDB/xdebug all work — equivalent to the prototype's `task verify`.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.

## Summary of Changes

Added `packages/docker/wp-alpine/` — a forked, ionos-wordpress-specific Alpine image
(Apache + mod_php + MariaDB + wp-cli + sshd + Xdebug + APCu), adapted from
`/opt/dev/wordpress-docker-image`:

- `Dockerfile`: `ARG_PHP_VERSION`/`ARG_ALPINE_VERSION` now default to `8.4`/`3.24`; added
  `${PHP_PKG}-pecl-apcu` + build-time ini patch (`apc.enable`/`apc.enable_cli`), build-time
  `xdebug.log_level=0`, and `git` (needed for git-ref core installs). Baked
  `phpunit/phpunit` + `yoast/phpunit-polyfills:^3.0` into `/opt/wp-tests`, branching the
  phpunit constraint on PHP major (`^9.0` for 7.4, `^11.0` for 8.x — yoast/phpunit-polyfills
  ^3.0 doesn't support 10.x, and phpunit 11 requires PHP >=8.2).
- `docker-entrypoint.sh`: forked verbatim except the core-download step now branches on
  `WORDPRESS_VERSION`'s shape — `^[0-9.]+$` keeps the release-tarball path, `owner/repo#ref`
  git-clones the ref into `/htdocs` instead (risk #1 in the migration plan), both inside the
  existing flock guard. AFTER_START hook kept as-is.
- `examples/after-start-ionos-wordpress.sh`: the wp-cli customization previously in
  `wp-env-after-start.sh` (brand options, theme activation, admin password, plugin-activation
  exclusions) — kept out of the generic entrypoint per discussion, wired up via `AFTER_START`
  instead. Not yet referenced by any `.env`/compose config — a later phase does that.
- `scripts/generate-vscode-launch.sh`: host-side `.vscode/launch.json` generator, ported from
  `wp-env-after-start.sh`'s logic with container paths updated to `/htdocs/wp-content/...`.
  Not wired into any `pnpm` command yet — needs Phase 2's mount layout to describe.
- `.env`/`package.json`/`.dockerignore`/`scripts/update-dependencies.sh`: follow the existing
  `packages/docker/{ecs-php,potrans,rector-php}` package convention.
- `.github/workflows/build-wp-alpine-image.yaml`: builds+pushes both PHP variants
  (`8.4`/`3.24` and `7.4`/`3.15`) on changes under `packages/docker/wp-alpine/**`, tagged by
  the git tree hash of that directory (`<hash>-php8.4`/`<hash>-php7.4`). Registry/repo come
  from `vars.IMAGE_REGISTRY`/`vars.IMAGE_REPOSITORY` (default GHCR), auth from
  `secrets.IMAGE_REGISTRY_USERNAME`/`PASSWORD` falling back to `github.actor`/`GITHUB_TOKEN` —
  never hardcodes `ghcr.io`.

**Verified manually** (both `ARG_PHP_VERSION=8.4`/`3.24` and `7.4`/`3.15`, built and run
locally via `docker build`/`docker run`): HTTP 200 on the installed site, `wp core version`
over both `docker exec` and SSH, xdebug + apcu both loaded (`php -m`), MariaDB reachable as
`wordpress`/`password`, git-ref `WORDPRESS_VERSION` (`WordPress/WordPress#6.9`) installs
correctly, and the `AFTER_START` hook (bind-mounted `examples/after-start-ionos-wordpress.sh`)
runs and applies `ionos_group_brand` etc. Deferred to later phases: full PHPUnit test-suite
wiring (`WP_TESTS_DIR`), reconciling `phpunit.xml`'s 10.5 schema with the 9/11 split baked
here, and wiring `AFTER_START`/`generate-vscode-launch.sh` into `.env`/`pnpm start`.
