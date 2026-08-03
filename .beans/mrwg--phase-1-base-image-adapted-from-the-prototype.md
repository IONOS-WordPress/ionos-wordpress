---
# mrwg
title: Phase 1 — Base image, adapted from the prototype
status: todo
type: task
priority: normal
created_at: 2026-08-03T10:59:14Z
updated_at: 2026-08-03T11:07:20Z
parent: vjbx
---

Goal: an ionos-wordpress-specific Alpine image building on /opt/dev/wordpress-docker-image's
Dockerfile, published to GHCR.

## Tasks
- [ ] Fork the Dockerfile into this repo (e.g. packages/docker/wp-alpine/Dockerfile), default
      ARG_PHP_VERSION to PHP 8.4. Keep the build-arg, but shrink the published matrix to exactly
      two tags — 8.4 (default) and 7.4 (legacy, for PHP_VERSION_OVERRIDE)
- [ ] Bake into entrypoint/image (replacing wp-env-after-start.sh):
  - [ ] yoast/phpunit-polyfills composer install
  - [ ] xdebug + APCu ini patch
  - [ ] .vscode/launch.json generation logic (host-side, generated at container-start time)
  - [ ] wp-cli bootstrap: structure/flush, brand options, admin password, plugin/theme activation
- [ ] Extend entrypoint for git-ref core installs (risk #1 in plan doc)
- [ ] Add AFTER_START support: optional host script from .env, bind-mounted, executed as php
      user via doas (keepenv)
- [ ] Set up image publish workflow (.github/workflows/build-wp-alpine-image.yaml): triggered on
      Dockerfile/entrypoint changes, tags by content hash, builds/pushes both PHP variants as
      :<hash>-php8.4 and :<hash>-php7.4 to ${IMAGE_REGISTRY}/${IMAGE_REPOSITORY} (default GHCR).
      Registry/repo from repo Actions variables; auth from repo secrets — never hardcode ghcr.io.

## Exit criteria
docker run each of the two built image variants manually, confirm WP installs, wp-cli/Apache/
MariaDB/xdebug all work — equivalent to the prototype's `task verify`.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.
