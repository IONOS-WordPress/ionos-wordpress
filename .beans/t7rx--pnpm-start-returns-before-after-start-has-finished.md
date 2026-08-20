---
# t7rx
title: pnpm start returns before AFTER_START has finished configuring the site
status: completed
type: bug
priority: low
created_at: 2026-08-20T12:33:53Z
updated_at: 2026-08-20T12:40:05Z
---

scripts/start.sh considers the dev container up as soon as `http://localhost:$HTTP_PORT/` answers
200. That happens strictly before the container has finished configuring itself, so `pnpm start`
can return - printing "You can access the wordpress site at ..." - while docker-entrypoint.sh is
still mutating the site underneath it.

Ordering in packages/docker/wordpress-alpine/docker-entrypoint.sh:

  233  httpd &                      <- start.sh's 200 becomes possible here
  247  echo "Running AFTER_START script: /after-start.sh"
  248  /after-start.sh              <- still running while start.sh is already done
  251  touch /run/entrypoint-complete
  253  exec doas -u php /bin/bash -i

## Impact

Whatever AFTER_START does can land after the developer (or a script chained onto `pnpm start`) has
already begun using the site. From packages/docker/wordpress-alpine/after-start-ionos-wordpress.sh
that includes `wp plugin activate --all`, the brand/market options, the static front page, the
permalink flush and - most visibly - `wp user update admin --user_pass="$WP_PASSWORD"`, which
invalidates existing auth cookies. Expected symptoms are all transient and confusing rather than
fatal: a page served with the wrong brand or the latest-posts homepage, a plugin that "was not
active a second ago", or being logged out immediately after logging in.

Dev-only: no CI workflow runs `pnpm start` (it is referenced only from .devcontainer/README.md and
.devcontainer/Dockerfile), so nothing automated depends on this today.

## Fix

Same class of bug as lo2c, and the machinery from it is already in place: the entrypoint now
touches /run/entrypoint-complete as its very last statement (and clears it on boot so a restart
cannot serve a stale marker). start.sh should wait for that marker in addition to the HTTP check -
the marker for "configuration finished", the existing 200 for "the site actually serves". Order
matters: poll the marker first, then the HTTP code, so a timeout message still distinguishes
"never finished booting" from "booted but does not serve".

Consider also whether the 60s HTTP budget is still right once the wait covers AFTER_START, which
on a cold start does a plugin activation sweep and two rewrite flushes.

Beware: an image built before the marker existed will never satisfy the check.
scripts/test.sh grew a diagnostic for exactly that case; start.sh runs `pnpm build` first (unless
BUILD_UP_TO_DATE=1), so it is less exposed, but the BUILD_UP_TO_DATE escape hatch means it is not
immune either.

## Todos

- [x] poll /run/entrypoint-complete in scripts/start.sh before the existing HTTP 200 wait
- [x] keep the timeout messages distinguishable between the two phases
- [x] handle/report the pre-marker-image case (see scripts/test.sh's await_test_container)
- [x] re-check the 60s budget on a cold start

## Summary of Changes

scripts/start.sh waits for /run/entrypoint-complete (the marker lo2c added) before its existing
HTTP 200 check, so `pnpm start` no longer returns while AFTER_START is still writing.

- budget split: 180s for the marker, since everything slow now sits inside that phase (core
  download/clone, install, AFTER_START's activation sweep and two rewrite flushes); the HTTP wait
  keeps its 60s, which is ample because httpd is long up by the time the marker appears.
- separate messages per phase, so a timeout says whether the container never finished starting up
  or finished but does not serve.
- pre-marker containers degrade instead of hanging: if `docker exec true` works but the
  container's own /docker-entrypoint.sh has no marker support, it warns ("recreate it via
  'pnpm destroy'") and falls back to the old HTTP-only behaviour. This is reachable even with an
  up-to-date image, because `docker start` on a long-lived dev container re-runs the entrypoint
  that container was created with.

## Verification

Measured the window this closes, on a container with AFTER_START wired exactly as the dev stack
has it:

  HTTP 200 first answered at : 14s   <- what pnpm start used to wait for
  entrypoint actually done at: 18s   <- what it waits for now
  unguarded window           : 4s

- supported path: ran start.sh's new loop verbatim against a booting container built from the
  current image - waited 13s, ended with complete=1, unsupported empty
- fallback path: `BUILD_UP_TO_DATE=1 ./scripts/start.sh` against the running dev container (created
  from a pre-marker image) - warned and completed in 0.65s, container untouched
- marker clearing on `docker start`/`docker restart` was already verified under lo2c

Not exercised live: the hard-timeout branch (supported marker that never appears) - it would need a
deliberately broken entrypoint and three minutes. The branch is a single condition on two flags.
