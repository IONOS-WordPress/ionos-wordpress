---
# euw2
title: Phase 2 — Dev stack + dynamic mount generation
status: todo
type: task
priority: normal
created_at: 2026-08-03T10:59:14Z
updated_at: 2026-08-03T11:07:20Z
parent: hr03
blocked_by:
    - mrwg
---

Goal: pnpm start/pnpm stop/pnpm destroy work against the new container instead of wp-env,
with dev-only single persistent stack.

## Tasks
- [ ] Rewrite scripts/start.sh: drop .wp-env.json generation; generate docker-compose.dev.yml
      (or docker run -v arg list) by scanning packages/wp-plugin/*, wp-theme/*, wp-mu-plugin/*,
      reusing existing discovery logic. Preserve mu-plugin loader+dir dual-mount pattern (risk #2)
- [ ] Rewrite scripts/stop.sh → docker stop/docker compose stop on the dev container
- [ ] Rewrite scripts/destroy.sh → remove dev container + its mnt/ data (keep shared
      version-keyed WP-core cache)
- [ ] Replace WP_ENV_HOME with the prototype's split layout: ./mnt/wordpress-core/<version>
      (shared, version-keyed, survives destroy) and ./mnt/<stack>/ (per-stack overlay, wiped on
      destroy/teardown). Introduce MNT_HOME (default ./mnt) .env var. Readiness becomes
      `docker inspect --format '{{.State.Health.Status}}'`/`docker ps` instead of parsing
      `wp-env status --json`
- [ ] Replace WP_ENV_CORE with WORDPRESS_VERSION (same default/format/override behavior, risk #1
      entrypoint branching handles both shapes)
- [ ] Update .env: image tag, ports, WP_PASSWORD, optional AFTER_START path, plus
      IMAGE_REGISTRY/IMAGE_REPOSITORY (default GHCR/ionos-wordpress). Document
      IMAGE_REGISTRY_USERNAME/IMAGE_REGISTRY_PASSWORD as .secrets-only, never defaulted/committed.
      scripts/start.sh/test.sh run `docker login` when creds present, skip for anonymous pulls
- [ ] Drop the `chmod -R a+w` and not-owned-by-user cleanup hacks (validate no longer needed,
      risk #3)
- [ ] Replace scripts/wp-env.sh with explicit per-purpose scripts/targets: logs, enter, ssh, cli

## Exit criteria
pnpm start brings up a working dev site at a fixed port with all current plugins/themes/
mu-plugins mounted and active, matching today's dev experience; pnpm stop/pnpm destroy behave
as expected.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.
