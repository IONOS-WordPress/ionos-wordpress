---
# 4tjo
title: Migrate from wp-env to custom Alpine containers
status: todo
type: epic
created_at: 2026-08-03T10:58:57Z
updated_at: 2026-08-03T10:58:57Z
---

Replace @wordpress/env (wp-env) with custom Alpine-based Docker containers, prototyped at
/opt/dev/wordpress-docker-image, while preserving the pnpm start/stop/test/destroy interface
developers and CI already use.

Full plan: docs/agent/wp-env-to-alpine-migration-plan.md

## Locked-in decisions
- Single all-in-one container per stack (Apache+PHP+MariaDB+SSH+xdebug)
- Separate persistent dev stack vs ephemeral test stack (always torn down, pass or fail)
- Playwright runs against the same ephemeral test-stack container as PHPUnit
- Xdebug always-on
- Dynamic mount auto-discovery of packages/wp-plugin/*, wp-theme/*, wp-mu-plugin/*
- TEST_PRODUCTION dist-vs-source mount switch preserved
- Default PHP 8.4 (bump from 8.3), small prebuilt matrix {8.4, 7.4} published by Phase 1
- PHP_VERSION_OVERRIDE=<version> lets CI/devs test against PHP 7.4 via prebuilt tag, no local build
- phpMyAdmin dropped
- Keep pnpm start/stop/test/destroy CLI interface; rewrite scripts/*.sh internals
- Image published to a registry (IMAGE_REGISTRY/IMAGE_REPOSITORY from .env, default GHCR)
- Bootstrapping (composer polyfills, xdebug/APCu config, wp-cli bootstrap, launch.json) baked into image/entrypoint
- AFTER_START user hook script supported, runs as php user with doas available
- Hard cutover — remove wp-env once new setup passes validation

## Open risks (see plan doc for full resolutions)
1. WordPress core ref format (owner/repo#ref vs release tarball)
2. mu-plugins dual-mapping (loader .php + subdir)
3. Permission workarounds (HOST_UID/HOST_GID build-args replace chmod hacks)
4. Hardcoded DB credentials (accepted as-is, low exposure)

Each phase should land as its own PR/changeset so it's independently reviewable and revertible.
