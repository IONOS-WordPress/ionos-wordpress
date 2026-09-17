---
# topr
title: Replace ionos/essentials/option/set REST endpoint with core /wp/v2/settings
status: draft
type: task
priority: normal
created_at: 2026-09-17T08:47:04Z
updated_at: 2026-09-17T09:00:06Z
---

Replace the custom /wp-json/ionos/essentials/option/set REST endpoint in the Essentials plugin with WordPress core's /wp/v2/settings endpoint.

## Status

The acute security problems have already been fixed on fix/essentials-option-set-endpoint (see 6rkb) — this bean is now a cleanup/architecture task, not a vulnerability.

- 3b152dc1 — permission_callback requires `manage_options` (was: any logged-in user)
- ab246a4e — both branches allowlist the writable keys and return 400 otherwise

## Problem

The plugin still hand-rolls an option-writing endpoint (`POST /wp-json/ionos/essentials/option/set`, `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/index.php`) with a bespoke `{option, key, value}` payload. What remains wrong with it:

- The allowlist is a hand-maintained const that has to be kept in sync with the UI by hand.
- `value` is still stored as whatever JSON arrives — no schema, no type coercion.
- The nested branch does `$options[$key] = $value` on whatever `get_option()` returned; a scalar there is a PHP 8.4 fatal.
- No option registration, so the values are invisible to core tooling and to any other REST consumer.

Core's `/wp/v2/settings` solves all four: options are registered via `register_setting(..., ['show_in_rest' => true])` with a JSON schema (allowlist + type coercion for free), and the route is already restricted to `manage_options` with standard REST cookie-auth nonce handling.

## Options currently settable (identified)

Standalone options, `SETTABLE_OPTIONS` in inc/dashboard/index.php, written when the request has no `option` param:

- `ionos_essentials_maintenance_mode` — bool-ish; on update flushes HDDCache when \Ionos\Performance\Controllers\Cache\HDDCache exists
- `ionos_essentials_dashboard_mode` — bool-ish; read at inc/dashboard/index.php:86 to redirect /wp-admin/

Nested option `IONOS_SECURITY_FEATURE_OPTION` (an array), written per sub-key. Permitted sub-keys are the keys of IONOS_SECURITY_FEATURE_OPTION_DEFAULT in inc/security/index.php:15:

- `IONOS_SECURITY_FEATURE_OPTION_XMLRPC`
- `IONOS_SECURITY_FEATURE_OPTION_PEL`
- `IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING`
- `IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY`

All four default to `true`.

## Callers (identified)

Only `src/dashboard/index.js` — the delegated `.input-switch` click handler (~line 254) posts `{option, key, value}` with `value` as 1/0. The switch markup lives in `inc/dashboard/tabs/tools.php`: the security toggles (lines 121/144/155/166, carrying `data-option`) and the two standalone toggles (lines 80, 204, no `data-option`).

The MCP switch in `inc/mcp/view.php:27` also has class `.input-switch` but carries `data-manual="true"`, so the handler returns early; it posts to `/ionos/essentials/mcp/action` instead and is out of scope.

## Design note

`/wp/v2/settings` writes whole options, not sub-keys. For `IONOS_SECURITY_FEATURE_OPTION` the schema needs `type: object` with `properties` for the four keys, and the client must send the full object (or the handler must merge). Worth deciding before the JS refactor, since it changes the toggle handler from "send one key" to "send merged state".

## Todo

- [x] Identify every WP option currently settable via the custom endpoint (direct options + nested keys inside IONOS_SECURITY_FEATURE_OPTION) — see above, six in total
- [ ] Decide the whole-object vs merge approach for the nested security option
- [ ] Register those options via register_setting() with show_in_rest + JSON schema
- [ ] Preserve the HDDCache flush side-effect on ionos_essentials_maintenance_mode via update_option_{option} or rest_after_insert
- [ ] Remove the ionos/essentials/option/set route registration and the now-redundant SETTABLE_OPTIONS const in inc/dashboard/index.php
- [ ] Refactor src/dashboard/index.js to call /wp/v2/settings
- [ ] Update/add PHPUnit tests covering the new settings registration and behavior
- [ ] Update/add e2e/JS tests covering the dashboard toggle flow against the new endpoint
- [ ] Grep the full repo for remaining references to ionos/essentials/option/set
- [ ] Add a changeset for the Essentials plugin (bump type TBD with user)

Resolved: packages/wp-mu-plugin/stretch-extra/stretch-extra/plugins/ionos-essentials needs no edits — it is gitignored build output (packages/wp-mu-plugin/stretch-extra/.gitignore:2 ignores stretch-extra/plugins/*), as are the copies under */dist/.
