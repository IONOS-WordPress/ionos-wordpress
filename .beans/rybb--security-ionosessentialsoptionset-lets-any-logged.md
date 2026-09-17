---
# rybb
title: 'Security: /ionos/essentials/option/set lets any logged-in user write arbitrary WordPress options'
status: completed
type: bug
priority: critical
created_at: 2026-09-17T06:55:20Z
updated_at: 2026-09-17T07:36:07Z
blocked_by:
  - rzu8
---

## Problem

`packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/index.php:283` registers POST `ionos/essentials/option/set` with:

    'permission_callback' => fn () => 0 !== \get_current_user_id(),

Any authenticated user (including a Subscriber) can call it. The callback reads `option`/`key`/`value` straight from the JSON body and passes them to `\update_option()` with no allowlist, no capability check, no nonce and no sanitization.

## Impact

Privilege escalation / site takeover. A subscriber can POST `{"key":"users_can_register","value":"1"}` plus `{"key":"default_role","value":"administrator"}`, or overwrite `siteurl`/`home`, `active_plugins`, `ionos_essentials_maintenance_mode`, etc.

Secondary: in the `else` branch the value from `\get_option($option, ...)` is used as an array without a type check, and `$option` is attacker-controlled.

## Todo

- [x] Restrict permission_callback to `\current_user_can('manage_options')`
- [x] Allowlist the option/key names the dashboard actually sends (see the `.input-switch` handler in `src/dashboard/index.js:254`); reject anything else
- [x] Validate `value` (UI only ever sends 0/1)
- [x] Guard the `else` branch against a non-array stored option
- [x] PHPUnit test: subscriber gets 403 (landed separately, after rzu8 unblocked it)
- [x] Changeset (patch, `@ionos-wordpress/essentials`)

## Allowlist: complete set of options reachable via the UI

Sole caller is the `.input-switch` delegated handler in `src/dashboard/index.js:241-254`. It sends `{option: input.dataset.option ?? '', key: input.id, value: checked ? 1 : 0}`, and skips inputs carrying `data-manual`.

### Shape A — `option` empty → top-level `\update_option($key, $value)`

| key                                 | rendered at                        |
| ----------------------------------- | ---------------------------------- |
| `ionos_essentials_maintenance_mode` | `inc/dashboard/tabs/tools.php:30`  |
| `ionos_essentials_dashboard_mode`   | `inc/dashboard/tabs/tools.php:204` |

### Shape B — `option` = `IONOS_SECURITY_FEATURE_OPTION` → key inside that array

All emitted by `render_section()` (`inc/dashboard/tabs/tools.php:16`), which hardcodes `data-option="IONOS_SECURITY_FEATURE_OPTION"`; the key is the `id` arg. Four call sites:

| key (constant, value == name)                        | render_section call |
| ---------------------------------------------------- | ------------------- |
| `IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY`          | tools.php:121       |
| `IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING` | tools.php:144       |
| `IONOS_SECURITY_FEATURE_OPTION_XMLRPC`               | tools.php:155       |
| `IONOS_SECURITY_FEATURE_OPTION_PEL`                  | tools.php:166       |

These are exactly the four keys of `IONOS_SECURITY_FEATURE_OPTION_DEFAULT` (`inc/security/index.php:15`), so the allowlist for shape B can just be `array_keys(IONOS_SECURITY_FEATURE_OPTION_DEFAULT)` — self-maintaining.

### Not reachable

`inc/mcp/view.php:26` also renders an `.input-switch`, but its input has `data-manual="true"` and posts to `/ionos/essentials/mcp/action` instead. No other caller of `/ionos/essentials/option/set` exists in `src/` or `inc/`.

### Value domain

Always integer `1` or `0` (the comment at `index.js:250` notes `false` would store as NULL).

## Summary of Changes

`inc/dashboard/index.php`

- `permission_callback` is now `\current_user_can('manage_options')` (was: any logged-in user).
- New `ALLOWED_TOP_LEVEL_OPTIONS` map of option name => value type, so future options are added
  by extending the map rather than by touching the callback.
- New `get_allowed_option_type()`: resolves the accepted type for a request, or null when the
  option/key pair is not writable. Keys of the `IONOS_SECURITY_FEATURE_OPTION` array option are
  derived from `IONOS_SECURITY_FEATURE_OPTION_DEFAULT` and treated as `bool`.
- New `sanitize_option_value()`: casts by type (`bool`/`int`/`float`/`string`), rejects arrays,
  objects and values that do not fit the type. `bool` still stores 1/0.
- Rejected requests answer 400 instead of silently writing.
- The `else` branch falls back to the defaults when the stored option is not an array.
- Route registration extracted into `register_option_set_route()` so tests can register it
  (the plugin's `rest_api_init` hooks are stripped when the whole PHPUnit suite runs).

`inc/dashboard/tests/phpunit/OptionSetEndpointTest.php` (new, 12 tests - landed in a follow-up commit, see rzu8)

Subscriber 403, anonymous 401, non-allowlisted option 400, unknown security key 400, security key
redirected into another option 400, structured value 400, both allowlisted toggles write, non-array
security option repaired, plus a data-provider covering every `sanitize_option_value()` type and a
guard asserting every type used in the allowlist is supported.

`.changeset/secure-essentials-option-set-endpoint.md` — patch bump for `@ionos-wordpress/essentials`.

Verification: `pnpm test:php` 36/36 pass, `pnpm lint` passes.

## Note

The route stayed in its inline `rest_api_init` closure. Because of the hook-stripping issue
tracked in rzu8, the dispatch tests in `OptionSetEndpointTest` skip themselves when the route
is not registered, so only the first one exercises the endpoint per run. The allowlist helpers
are covered directly and always run.
