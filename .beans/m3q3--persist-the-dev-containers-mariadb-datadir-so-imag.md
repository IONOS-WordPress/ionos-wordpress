---
# m3q3
title: Persist the dev container's MariaDB datadir so image updates don't cost the local database
status: completed
type: task
priority: low
created_at: 2026-08-20T09:51:06Z
updated_at: 2026-08-20T09:53:33Z
---

The dev container's MariaDB datadir (/data) is neither a bind mount nor a named volume - it
lives only in the container's writable layer (`docker inspect ionos-wordpress-dev` shows mounts
for wp-content, wp-config.php, .htaccess and the shared core dir, but nothing for /data).

## Impact

Not a silent data-loss bug: `pnpm stop` is a plain `docker stop` (scripts/stop.sh), so the
database survives stop/start. The destructive paths are both deliberate and already warned
about - `pnpm destroy` (scripts/destroy.sh `docker rm -f`) and the WORDPRESS_VERSION-change
auto-recreate in scripts/start.sh.

What it does cost: env vars and bind mounts are baked in at `docker run` time, so picking up a
rebuilt wordpress-alpine image requires recreating the container - which currently also throws
away the local WordPress database (posts, options, users, activation state). Discovered while
rebuilding the image for bean p3j2 (WORDPRESS_DB_* support): the only way to move the running
dev container onto the new image was to lose the local site.

## Fix

Mount /data as a named docker volume derived from CONTAINER_NAME (so parallel stacks with a
different CONTAINER_NAME stay isolated), and have destroy.sh remove that volume so `pnpm destroy`
keeps its current "full reset" meaning. A named volume rather than a bind mount under mnt/dev:
docker seeds a fresh named volume from the image's /data including ownership, so mariadbd keeps
writing as the `mysql` uid - a host bind mount would need a chown that then makes the tree
awkward for the host user to clean up.

The ephemeral test container (scripts/test.sh) deliberately keeps no volume - it must stay
throwaway.

Also fix the stale warning text in scripts/start.sh, which claims recreating the container
"wipes ${MNT_HOME}/dev : database, uploads and wp-config.php" - the database was never in
mnt/dev.

## Todos

- [x] mount ${CONTAINER_NAME}-data at /data in scripts/start.sh
- [x] remove the volume in scripts/destroy.sh
- [x] correct the start.sh warning text
- [x] verify: recreating the container onto a new image keeps the database
- [x] verify: pnpm destroy still fully resets

## Summary of Changes

- `scripts/start.sh`: added `readonly DB_VOLUME_NAME="${CONTAINER_NAME}-data"` next to CORE_DIR/STACK_DIR
  and `--volume "${DB_VOLUME_NAME}:/data"` to the `docker run` in the container-creation branch.
- `scripts/destroy.sh`: `docker volume rm "${CONTAINER_NAME}-data" ... || true` after the `docker rm -f`,
  so `pnpm destroy` keeps meaning a full reset.
- `scripts/start.sh`: corrected the WORDPRESS_VERSION-mismatch warning, which claimed the database lived
  in `${MNT_HOME}/dev`.

Verified against the rebuilt image with a throwaway container/volume pair (the running dev stack was
deliberately not touched):

- fresh named volume is seeded from the image's own `/data` with ownership intact (`/data` = mysql:root,
  `/data/mysql` = mysql:mysql), so mariadbd writes as `mysql` without any chown.
- wrote a marker option + a post, then `docker rm -f` the container and recreated it on the same volume:
  both survived and the entrypoint took the "Database already exists, skipping creation" branch instead
  of reinstalling. This is the case the bean exists for.
- destroy path (container rm, then volume rm): the next container does a clean `will create database` +
  fresh install, marker gone. Removing an already-absent volume does not fail the script.

Note on what was _not_ executed verbatim: the new `--volume` line sits in start.sh's container-creation
branch, and `pnpm start` against an already-existing container only does `docker start`. The test
replicated start.sh's `docker run` flags by hand rather than running the script, since running it here
would either be a no-op or destroy the developer's live container.

Consequence for anyone with an existing dev container: it has no `/data` volume, so it keeps the datadir
in its writable layer until the container is next recreated. That one recreate still costs the database
(it is the destructive path either way); every recreate after it preserves it.
