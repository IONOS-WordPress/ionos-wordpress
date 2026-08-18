---
# zb17
title: 'docker-entrypoint.sh: mu-plugins permission-fix fallback dropped'
status: completed
type: bug
priority: normal
created_at: 2026-08-17T13:38:09Z
updated_at: 2026-08-18T08:14:38Z
parent: qi52
---

The deleted scripts/wp-env-after-start.sh had a guard:

    if find /var/www/html/wp-content/mu-plugins -mindepth 1 -maxdepth 1 -type d ...; then
      sudo chmod a+w -R /var/www/html/wp-content/mu-plugins 2>/dev/null || true
    fi

This protected against a specific wp-env restart/destroy failure caused by permission mismatches in the mu-plugins overlay directory. No equivalent chmod exists in packages/docker/wordpress-alpine/docker-entrypoint.sh beyond the initial 'chown -R php:php /htdocs' - which is itself skipped entirely when PHP_UID_REMAPPED is unset (i.e. locally built images).

## Impact

If mu-plugin bind-mounts end up read-only or owned differently (e.g. root-owned files written by root-run tooling), 'pnpm destroy'/restart could fail the same way the old guard was protecting against, with no fallback fix-up anymore.

## Fix

Confirm whether the new entrypoint's chown/permission handling genuinely supersedes this guard for all locally-built-image code paths (not just the PHP_UID_REMAPPED one), and add an equivalent fallback if not.

## Location

packages/docker/wordpress-alpine/docker-entrypoint.sh
scripts/wp-env-after-start.sh (deleted, for reference)

## Summary of Changes

No code change needed - confirmed the concern is already resolved, just not by the code path the bean's author was looking at.

The bean's premise was that docker-entrypoint.sh's only permission fix-up is `chown -R php:php /htdocs /home/php /opt/wp-tests` at line ~55, which is gated by `if [[ -n "$PHP_UID_REMAPPED" ]]` (only runs when HOST_UID/HOST_GID triggered a uid/gid remap). That gated block is real, but there's a SECOND, unconditional block a few lines later (docker-entrypoint.sh:88-89, after MariaDB comes up, unrelated to PHP_UID_REMAPPED):

    chown -R php:php /htdocs
    chmod -R a+rwX /htdocs

This runs on _every_ container start regardless of uid remapping or whether the image was locally built or pulled, and since bind mounts (including the per-container `wp-content/mu-plugins` overlay - see `_docker-mounts.sh`) are already attached by the time the entrypoint runs, this recursive chown+chmod reaches into mu-plugins too. It's strictly broader than the old wp-env-era guard (which only did a conditional `chmod a+w -R` on mu-plugins specifically).

## Verification

- Inspected the live dev container's mu-plugins host dir: files built by root-run tooling (`ionos-core`, `ionos-core.php`) are `root:root` on the host but show as `php:php` with `a+rwX` inside the container - already fixed by the unconditional block.
- Directly reproduced the scenario the bean worried about: created a host-owned file in `mu-plugins`, `chmod 000` it, restarted the dev container (`docker restart`) - the file came back `rw-rw-rw-`/`php:php` inside and on the host, confirming the fallback fires on every restart, not just first boot or PHP_UID_REMAPPED cases.
