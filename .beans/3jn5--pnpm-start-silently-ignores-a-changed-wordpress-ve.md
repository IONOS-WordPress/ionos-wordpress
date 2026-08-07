---
# 3jn5
title: pnpm start silently ignores a changed WORDPRESS_VERSION
status: completed
type: bug
created_at: 2026-08-06T14:47:20Z
updated_at: 2026-08-06T14:47:20Z
---

`scripts/start.sh` only `docker start`s an already existing container, but env vars and
bind mounts are baked in at `docker run` time. A bumped `WORDPRESS_VERSION` (e.g. 788dc044,
`WordPress/WordPress#7.0` -> `#7.0.3`) was therefore silently ignored: the dev container kept
serving the core dir it was originally created for, while `pnpm test:php` (ephemeral container)
already ran the new version.

- [x] compare the existing container's `WORDPRESS_VERSION` env against the configured one
- [x] recreate the container via `destroy.sh` on mismatch (before the overlay dirs are rebuilt)
- [x] warn about the implied data loss (`${MNT_HOME}/dev`: database, uploads, wp-config.php)
- [x] document the exception in README.md and docs/3-tools.md
- [x] fix the stale wp-env-era `.vscode/launch.json` section in README.md

## Summary of Changes

`scripts/start.sh` extracts `WORDPRESS_VERSION` from `docker inspect` and, when it differs
from the configured value, logs a warning and delegates to `scripts/destroy.sh` so the
following block recreates the container with the correct core mount. Scope is deliberately
limited to `WORDPRESS_VERSION` - ports and the mount list are not compared.
