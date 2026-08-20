---
# euw2
title: Phase 2 — Dev stack + dynamic mount generation
status: completed
type: task
priority: normal
created_at: 2026-08-03T10:59:14Z
updated_at: 2026-08-04T12:52:43Z
parent: hr03
blocked_by:
  - mrwg
---

Goal: pnpm start/pnpm stop/pnpm destroy work against the new container instead of wp-env,
with dev-only single persistent stack.

## Tasks

- [x] Rewrite scripts/start.sh: drop .wp-env.json generation; generate docker-compose.dev.yml
      (or docker run -v arg list) by scanning packages/wp-plugin/_, wp-theme/_, wp-mu-plugin/*,
      reusing existing discovery logic. Preserve mu-plugin loader+dir dual-mount pattern (risk #2)
- [x] Rewrite scripts/stop.sh → docker stop/docker compose stop on the dev container
- [x] Rewrite scripts/destroy.sh → remove dev container + its mnt/ data (keep shared
      version-keyed WP-core cache)
- [x] Replace WP_ENV_HOME with the prototype's split layout: ./mnt/wordpress-core/<version>
      (shared, version-keyed, survives destroy) and ./mnt/<stack>/ (per-stack overlay, wiped on
      destroy/teardown). Introduce MNT_HOME (default ./mnt) .env var. Readiness becomes
      `docker inspect --format '{{.State.Health.Status}}'`/`docker ps` instead of parsing
      `wp-env status --json`
- [x] Replace WP_ENV_CORE with WORDPRESS_VERSION (same default/format/override behavior, risk #1
      entrypoint branching handles both shapes)
- [x] Update .env: image tag, ports, WP_PASSWORD, optional AFTER_START path, plus
      IMAGE_REGISTRY/IMAGE_REPOSITORY (default GHCR/ionos-wordpress). Document
      IMAGE_REGISTRY_USERNAME/IMAGE_REGISTRY_PASSWORD as .secrets-only, never defaulted/committed.
      scripts/start.sh/test.sh run `docker login` when creds present, skip for anonymous pulls
      (scope narrowed - see Summary of Changes: local dev always builds locally, no registry/login wired into start.sh)
- [x] Drop the `chmod -R a+w` and not-owned-by-user cleanup hacks (validate no longer needed,
      risk #3)
- [x] Replace scripts/wp-env.sh with explicit per-purpose scripts/targets: logs, enter, ssh, cli

## Exit criteria

pnpm start brings up a working dev site at a fixed port with all current plugins/themes/
mu-plugins mounted and active, matching today's dev experience; pnpm stop/pnpm destroy behave
as expected.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.

## Summary of Changes

Rewrote `pnpm start`/`stop`/`destroy`/`wp-env` to drive the `wp-alpine` image (Phase 1,
`mrwg`) instead of `@wordpress/env`, as a single persistent dev container:

- `scripts/build.sh`: `ionos.wordpress.build_workspace_package_docker()` now always
  passes `--build-arg HOST_UID=$(id -u) --build-arg HOST_GID=$(id -g)` (harmless no-op
  for the 3 existing docker packages, required for wp-alpine's uid/gid-matched `php`
  user — this is what let the chmod/not-owned-by-user hacks be dropped instead of
  ported).
- `.env`: removed `WP_ENV_HOME`; renamed `WP_ENV_CORE` → `WORDPRESS_VERSION` (same
  default/format); added `MNT_HOME=./mnt`, `CONTAINER_NAME=ionos-wordpress-dev`,
  `HTTP_PORT=8888` (unchanged from wp-env), `SSH_PORT=2222` (new). Same in
  `.env.local.example`. **Scope narrowed from the literal task wording** (confirmed
  with user during planning): `IMAGE_REGISTRY`/`IMAGE_REPOSITORY`/`docker login` are
  _not_ wired into `start.sh` — the existing `pnpm build` step already rebuilds
  `wp-alpine` locally via `scripts/build.sh`'s docker-package dispatch, so the dev
  inner loop never needs a registry pull. GHCR stays a CI-only concern for a later
  phase (`PHP_VERSION_OVERRIDE` fast path).
- `scripts/start.sh`: dropped `.wp-env.json`/`.wp-env.override.json` generation.
  Now: prepares `${MNT_HOME}/wordpress-core/<sanitized-version>` (shared core cache)
  and `${MNT_HOME}/dev/` (per-stack overlay: `wp-content/{plugins,themes,mu-plugins,
uploads}`, `wp-config.php`, `.htaccess`); builds a `--volume` list with one bind
  mount per discovered `wp-plugin`/`wp-theme`/`wp-mu-plugin` package (**simplified
  from the migration doc's symlink-flatten proposal for risk #2** — Docker natively
  supports many individual bind mounts into one container directory, so mu-plugins
  just get two direct mounts (loader `.php` + subdir) instead of a generated
  symlink directory); does an idempotent `docker start` vs fresh `docker run`
  depending on whether the named container already exists; polls
  `http://localhost:${HTTP_PORT}/` for HTTP 200 as the readiness check (replacing
  `wp-env status --json`). `TEST_PRODUCTION=true` keeps the existing dist-path
  resolution logic, now emitting mounts instead of JSON mappings.
- `scripts/stop.sh`/`destroy.sh`: rewritten as thin `docker stop`/`docker rm -f` +
  `rm -rf "${MNT_HOME}/dev"` (core cache under `${MNT_HOME}/wordpress-core` survives
  `destroy`).
- `scripts/wp-env.sh` deleted; added `scripts/logs.sh` (`pnpm logs`), `scripts/enter.sh`
  (`pnpm enter`), `scripts/cli.sh` (`pnpm cli`) — SSH needs no wrapper (`ssh -p 2222
php@localhost`, always-on per the image).
- `package.json`: removed `"wp-env"` script, added `"logs"`/`"enter"`/`"cli"`. Kept
  `@wordpress/env` devDependency and `scripts/wp-env-after-start.sh`/
  `wp-env-after-destroy.sh` — `scripts/test.sh` still depends on them for PHPUnit/e2e,
  unchanged in this bean.

**Verified manually** end-to-end (clean `rm -rf mnt`, real `docker build`/`pnpm start`
run): `pnpm start` brings up the dev container with `ionos-essentials`/
`ionos-wpdev-caddy` (wp-plugin) and `ionos-core`/`stretch-extra` (wp-mu-plugin) all
mounted and active (`wp plugin list` parity-checked against today's `.wp-env.json`
mount list); HTTP 200 at `localhost:8888`; `pnpm cli`/`pnpm enter` both work;
`ssh -p 2222 php@localhost wp core version` works; `pnpm stop` + `pnpm start` restarts
the same container without rebuilding; `pnpm destroy` removes the container and
`./mnt/dev` while `./mnt/wordpress-core/<version>` survives, and a follow-up
`pnpm start` reuses that cache (~4s, no re-download); `TEST_PRODUCTION=true pnpm start`
correctly bind-mounts each plugin's `dist/<zip-name>/<name>` path instead of source.

**Known, accepted gap** (flagged for the PR description): `pnpm test:php`/
`pnpm test:e2e` still call `pnpm exec wp-env status/run ...`, which breaks once this
lands (no more `.wp-env.json`/wp-env containers). This is expected — Phase 3
(`p8wo` milestone) rewrites `test.sh` against the new container next.
