---
# az37
title: Local dev environment lost WordPress content bootstrapping (AFTER_START unwired by default)
status: completed
type: bug
priority: high
created_at: 2026-08-17T13:37:53Z
updated_at: 2026-08-18T07:50:45Z
parent: qi52
---

The deleted scripts/wp-env-after-start.sh ran unconditionally on every 'wp-env start' and did several things that establish local-dev parity with the real site:

- activated themes/plugins and set IONOS brand emulation options / IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION / IONOS_CUSTOM_DELETED_PLUGINS_OPTION
- reset the admin password / compromised-credentials-meta
- set the front page to a static page (`wp option update page_on_front 2` / `show_on_front page`)
- propagated an optional WPSCAN_TOKEN secret into the options table

This logic was moved into packages/docker/wordpress-alpine/examples/after-start-ionos-wordpress.sh, an *example* script explicitly commented "Not wired into .env/docker-compose yet." Nothing in .env, .env.local.example, scripts/start.sh, or scripts/test.sh sets AFTER_START by default. On top of that, the static-front-page and WPSCAN_TOKEN behaviors were dropped entirely - they aren't even present in the example script.

## Impact

A fresh 'pnpm start' now boots a stock, unbranded WordPress install with no themes/plugins activated and the default "latest posts" homepage instead of the previously expected static page - a real parity regression versus the old wp-env-based dev loop. Nothing errors; a developer just gets an environment that looks and behaves differently than before, until they notice or someone tells them to wire an AFTER_START script by hand.

## Fix

Either wire the equivalent AFTER_START script by default (e.g. via .env's AFTER_START default, matching what wp-env used to do unconditionally), or explicitly document in the migration plan / onboarding docs that this is now opt-in and how to enable it. Also decide whether the static front page and WPSCAN_TOKEN behaviors should be restored (in the default path or the example) or intentionally dropped.

## Location

packages/docker/wordpress-alpine/examples/after-start-ionos-wordpress.sh (new)
scripts/wp-env-after-start.sh (deleted, for reference)

## Summary of Changes

1. Promoted `packages/docker/wordpress-alpine/examples/after-start-ionos-wordpress.sh` -> `packages/docker/wordpress-alpine/after-start-ionos-wordpress.sh` (out of `examples/`), updated its header comment to reflect it's now the default, not an unwired sample.
2. Restored the dropped static-front-page behavior (`page_on_front`=2 / `show_on_front`=page), matching the old `wp-env-after-start.sh`.
3. Wired `AFTER_START` by default in `.env` to point at that script; documented the empty-string opt-out in `.env.local.example`. Confirmed `AFTER_START` is only consumed by `scripts/start.sh` (dev container) - `scripts/test.sh` doesn't use it, so this only affects `pnpm start`.
4. WPSCAN_TOKEN propagation was already present in the example script - no change needed.

## Blocking issue found and fixed along the way

Wiring AFTER_START by default initially made `pnpm start` crash-loop the dev container: `wp --quiet plugin activate --all` (run under `set -eo pipefail`, and docker-entrypoint.sh itself runs under `set -eu` and calls `/after-start.sh` directly) failed with 'Only activated 2 of 4 plugins', which killed the whole container's PID 1.

Root cause: `packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/modify-commands/modify-commands-plugins.php` intercepts wp-cli's `plugin activate/deactivate/...` commands to redirect them to `activate_custom_plugin()`/`deactivate_custom_plugin()` for stretch-extra's secondary-plugin-dir-provisioned plugins (which aren't real files under `WP_PLUGIN_DIR` and can't be activated by wp-cli's stock logic). This interceptor was completely dead code:

- Its `WP_CLI::add_hook("before_invoke:plugin:{$subcommand}", ...)" used a colon (`plugin:activate`) instead of wp-cli's real space-separated hook name (`plugin activate`) - confirmed correct usage two files over in `plugin-block-list.php`. The hook never fired.
- Even with the name fixed, `before_invoke:plugin <cmd>` only ever passes the command name string to the callback (per `WP_CLI\Dispatcher\Subcommand::invoke()`), not real `$args`/`$assoc_args` - so the old `function ($args, $assoc_args)` signature was reading the wrong data entirely, and `--all` invocations (empty $args) were never handled at all.

Fixed both: hook name corrected to the space-separated form; slugs/`--all` are now parsed from `$_SERVER['argv']` (same pattern already used correctly in `plugin-block-list.php`). Added `--all` bulk handling for activate/deactivate/toggle: the interceptor now activates/deactivates all eligible custom-mounted plugins itself, then sets a new `ionos_stretch_extra_suppress_custom_plugins` filter so the `all_plugins` filter stops injecting them for the remainder of that single wp-cli invocation - letting wp-cli's real `--all` proceed to completion for genuine filesystem plugins without also (fatally) attempting the virtual ones.

## Verification

- `pnpm destroy && pnpm start`: container stays up; `page_on_front`=2, `show_on_front`=page, `ionos_group_brand`=ionos, active theme=twentytwentyfive, all 4 plugins (incl. the 2 secondary-dir ones) active; front page serves 'Sample Page' at HTTP 200.
- `wp plugin activate --all` / `wp plugin deactivate --all` inside the container: exit 0, no partial-failure errors.
- `pnpm test:e2e secondary-plugin-dir.spec.js secondary-theme-dir.spec.js security.spec.js`: 10/10 passed (includes the secondary-plugin-dir 'activation / deactivation' test exercising the exact interceptor code path changed).
- Confirmed `welcome.spec.js`'s pre-existing failure ('Failed to delete custom field' on `wp user meta delete`) reproduces identically on the unmodified original code (via `git stash`) - unrelated to this change, not fixed here.

## Files changed

- packages/docker/wordpress-alpine/examples/after-start-ionos-wordpress.sh -> packages/docker/wordpress-alpine/after-start-ionos-wordpress.sh (moved, edited)
- .env, .env.local.example
- packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/modify-commands/modify-commands-plugins.php
