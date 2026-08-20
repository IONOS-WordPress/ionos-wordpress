---
# t7rx
title: pnpm start returns before AFTER_START has finished configuring the site
status: todo
type: bug
priority: low
created_at: 2026-08-20T12:33:53Z
updated_at: 2026-08-20T12:33:53Z
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

- [ ] poll /run/entrypoint-complete in scripts/start.sh before the existing HTTP 200 wait
- [ ] keep the timeout messages distinguishable between the two phases
- [ ] handle/report the pre-marker-image case (see scripts/test.sh's await_test_container)
- [ ] re-check the 60s budget on a cold start
