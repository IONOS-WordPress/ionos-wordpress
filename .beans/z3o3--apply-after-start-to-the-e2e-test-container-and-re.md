---
# z3o3
title: Apply AFTER_START to the e2e test container and re-refactor the spec hooks
status: completed
type: task
created_at: 2026-08-20T11:59:55Z
updated_at: 2026-08-20T11:59:55Z
---

Follow-up to okbi. The e2e test container never ran the AFTER_START customization the dev stack
gets, so every spec had to reproduce the difference (brand, market, plugin-activation exclusions)
in its own beforeAll. Wiring it in makes the e2e baseline match what developers actually see and
lets more hooks go.

## Changes

scripts/test.sh

- mounts the AFTER_START script (from .env) at /after-start.sh in the test container
- runs it in the e2e phase, replacing the two ad-hoc commands that reset the admin password and
  the compromised-credentials meta (the script already does both)
- deliberately does NOT pass --env AFTER_START: that makes docker-entrypoint.sh run it on boot,
  and phpunit shares the live site's tables (phpunit/wp-tests-config.php uses the wp_ prefix), so
  the state would reach the PHPUnit run. Verified failure mode: with AFTER_START on boot,
  IONOS_CUSTOM_DELETED_PLUGINS_OPTION stops stretch-extra loading its provisioned
  ionos-essentials copy, which is what defines the constants ClassWPScanTest needs - 13 PHPUnit
  errors, "Undefined constant ionos\essentials\PLUGIN_DIR". Running it after phpunit instead is
  also required for correctness: phpunit reinstalls WordPress, dropping everything AFTER_START set.

packages/docker/wordpress-alpine/after-start-ionos-wordpress.sh

- the static front page is now looked up (falling back to creating a page) instead of assuming
  the default install's "Sample Page" is id 2. After a phpunit reinstall there is no such page, so
  page_on_front pointed at a missing id and the whole front page 404ed - which broke
  dashboard-no-errors.spec.js and mcp.spec.js (both assert an empty console). Also more correct
  for the dev stack, which was relying on the same assumption.

Specs, now redundant because AFTER_START provides the state:

- marketplace.spec.js: dropped `option set ionos_group_brand 'ionos'`
- dashboard-myaccount.spec.js: dropped `option update ionos_group_brand ionos` (+ unused import)
- descriptify.spec.js: dropped `option update ionos_market de` (+ unused import)

Specs, now needing MORE than before:

- secondary-plugin-dir.spec.js: re-added `IONOS_CUSTOM_DELETED_PLUGINS_OPTION '[]'`. AFTER_START
  lists the provisioned ionos-essentials (and beyond-seo) as deleted precisely so the dev site
  never runs the provisioned copy next to the mounted one; these tests drive that plugin's row on
  plugins.php, so they have to un-hide it.
- secondary-plugin-dir.spec.js: replaced `wp plugin deactivate ionos-essentials` with
  `wp eval 'deactivate_plugins("ionos-essentials/ionos-essentials.php");'`. The slug is ambiguous -
  both the mounted plugin and the provisioned copy answer to it, and wp-cli picks the provisioned
  one: it reports "Successfully performed deactivate on ionos-essentials" while active_plugins
  still contains ionos-essentials/ionos-essentials.php (verified by probe). Both being active is a
  hard fatal (Cannot redeclare the essentials namespace's _is_plugin_active()), which is what the
  test hit once AFTER_START's `plugin activate --all` left the mounted plugin active.
- secondary-theme-dir.spec.js: restored a one-line beforeAll deleting
  IONOS_CUSTOM_DELETED_THEMES_OPTION. See below.

## Unexplained, deliberately left as-is

secondary-theme-dir.spec.js's 'deletable' test passes in an e2e-only run but fails after phpunit.
Probing both baselines showed exactly one difference: IONOS_CUSTOM_DELETED_THEMES_OPTION is absent
in an e2e-only run and present as an empty array after phpunit. Bisecting the original hook showed
the delete of that option is what fixes it, and that a no-op `wp option get siteurl` in its place
does NOT (so it is the value, not a wp bootstrap warming something). inc/secondary-theme-dir.php
reads the option as get_option(..., []) everywhere, which ought to make absent and [] behave
identically - no explanation found. The line stays with a comment warning not to remove it without
running the combined path.

## Verification

- `pnpm run test --use php --use e2e`: PHPUnit OK (15 tests, 40 assertions), e2e 28/28
- `pnpm test:e2e` alone: 28/28, repeated (one earlier run reported 27 passed with a retry before
  the last spec fixes landed)
- `pnpm test:php` alone: OK (15 tests, 40 assertions)
- eslint + prettier clean on all touched specs

## Deferred

- the DELETED_THEMES absent-vs-empty-array behaviour above
- `pnpm run test --use php <anything>.js` dies with exit 137 at "Installing..." (reproduces with
  --use php alone, and predates this work) - passing a .js positional to the php phase
