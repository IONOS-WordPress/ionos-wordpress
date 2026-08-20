---
# 0sfp
title: Dedupe 'is the dev container running/existing' check across 5 scripts
status: completed
type: task
priority: low
created_at: 2026-08-17T13:38:53Z
updated_at: 2026-08-18T08:57:56Z
parent: qi52
---

The same check is reimplemented 5 times:

    docker ps [-a] --filter "name=${CONTAINER_NAME}" --format '{{.Names}}' | grep -qx "$CONTAINER_NAME"

in scripts/start.sh, stop.sh, clean.sh, destroy.sh, distclean.sh. All five already source scripts/includes/_bootstrap.sh, which would be the natural home for a shared helper (e.g. ionos.wordpress.container_running / container_exists), but none was added.

## Impact

If the container-name matching logic ever needs to change (e.g. podman's different docker-ps filter semantics, or adding a consistent --all variant), the fix has to be applied in 5 separate call sites - easy to update start.sh/stop.sh but forget clean.sh/destroy.sh/distclean.sh, silently reintroducing the "run cleanup while the container is live" bug these guards exist to prevent.

## Suggested fix

Extract a shared 'ionos.wordpress.container_running <name>' / 'container_exists <name>' helper into _bootstrap.sh.

## Location

scripts/start.sh, stop.sh, clean.sh, destroy.sh, distclean.sh

## Summary of Changes

Added two shared helpers to scripts/includes/_bootstrap.sh:

- `ionos.wordpress.container_running <name>` - true if the container is currently running (docker ps, no -a)
- `ionos.wordpress.container_exists <name>` - true if the container exists at all, running or stopped (docker ps -a)

Used `container_running` in stop.sh, clean.sh, distclean.sh (which only cared about the running state) and `container_exists` in start.sh, destroy.sh (which cared about existence regardless of state) - preserving each script's original -a/no -a distinction rather than collapsing them into one function.

## Verification

Exercised the full lifecycle against the real dev container: `pnpm clean` correctly refused while the container was running; `pnpm stop` stopped it; `pnpm start` detected the existing stopped container and restarted it (no recreate); `pnpm destroy` removed it; `pnpm start` recreated it fresh.
