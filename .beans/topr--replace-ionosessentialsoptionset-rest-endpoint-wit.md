---
# topr
title: Replace ionos/essentials/option/set REST endpoint with core /wp/v2/settings
status: draft
type: task
priority: normal
created_at: 2026-09-17T08:47:04Z
updated_at: 2026-09-17T08:47:17Z
---

Replace the custom, unauthenticated-by-capability /wp-json/ionos/essentials/option/set REST endpoint in the Essentials plugin with WordPress core's /wp/v2/settings endpoint.

## Problem

The Essentials plugin registers a custom REST endpoint (`POST /wp-json/ionos/essentials/option/set`, see `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/index.php`) that lets the client set **any** WordPress option by name (`option`, `key`, `value` params), optionally as a key within a nested array option.

Issues with the current implementation:

- No capability check — permission callback only verifies the user is logged in (`0 !== get_current_user_id()`), not that they hold `manage_options` or any relevant capability.
- No nonce/CSRF check beyond whatever the REST API's default cookie-auth nonce handling provides.
- Accepts an arbitrary option name, so in principle any WP option (not just Essentials/dashboard-related ones) can be overwritten by any logged-in user.

WordPress core already ships `/wp/v2/settings`, which:

- Only exposes options explicitly registered via `register_setting(..., ['show_in_rest' => true])` with a JSON schema — no arbitrary option names.
- Is hard-restricted to users with the `manage_options` capability.
- Handles CSRF/nonce via the standard REST cookie-auth flow.

## Goal

Replace `/wp-json/ionos/essentials/option/set` with core's `/wp/v2/settings` endpoint:

- Register each option currently settable through the custom endpoint via `register_setting()` with `show_in_rest` and an appropriate schema (including the nested/array options, e.g. the security feature option keyed by sub-key).
- Remove the custom `ionos/essentials/option/set` route registration.
- Refactor all client-side callers to use `/wp/v2/settings` instead (currently: `packages/wp-plugin/ionos-essentials/ionos-essentials/src/dashboard/index.js`, the dashboard option toggle handler around line 254).
- Update/add PHPUnit and any relevant e2e/JS tests covering the old endpoint and the new settings-based flow.
- Check `packages/wp-mu-plugin/stretch-extra/stretch-extra/plugins/ionos-essentials/` — appears to contain a generated/bundled copy of the plugin; confirm whether it needs no direct edits (build artifact) or is itself a source location.

## Todo

- [ ] Identify every WP option currently settable via the custom endpoint (direct options + nested keys inside `IONOS_SECURITY_FEATURE_OPTION` and any others)
- [ ] Register those options via `register_setting()` with `show_in_rest` + JSON schema so they're exposed on `/wp/v2/settings`
- [ ] Preserve special side-effects on update (e.g. HDDCache flush when `ionos_essentials_maintenance_mode` changes) via a `update_option_{option}` hook or `rest_after_insert` filter
- [ ] Remove the `ionos/essentials/option/set` route registration in `inc/dashboard/index.php`
- [ ] Refactor `src/dashboard/index.js` (and any other caller) to call `/wp/v2/settings` instead of `/ionos/essentials/option/set`
- [ ] Update/add PHPUnit tests covering the new settings registration and behavior
- [ ] Update/add e2e/JS tests covering the dashboard toggle flow against the new endpoint
- [ ] Grep the full repo for any remaining references to `ionos/essentials/option/set` and remove/update them
- [ ] Add a changeset for the Essentials plugin (bump type TBD with user)
