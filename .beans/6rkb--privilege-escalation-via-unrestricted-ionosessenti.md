---
# 6rkb
title: Privilege escalation via unrestricted /ionos/essentials/option/set REST endpoint
status: completed
type: bug
priority: critical
created_at: 2026-09-17T08:34:22Z
updated_at: 2026-09-17T08:57:26Z
---

Any logged-in user (incl. subscriber) could write arbitrary wp_options via POST /wp-json/ionos/essentials/option/set. The permission_callback only checked that a user was logged in, and the option key/value were unvalidated, allowing e.g. default_role=administrator + users_can_register=1 and thus full site takeover.

- [x] Tighten permission_callback to manage_options
- [x] Allowlist writable option keys (both branches, 400 on unknown key)
- [x] Changeset

Value sanitization, the non-array get_option guard and PHPUnit coverage are superseded by topr, which replaces this endpoint with core /wp/v2/settings.

## Summary of Changes

inc/dashboard/index.php:

- permission_callback changed from `0 !== get_current_user_id()` to `current_user_can('manage_options')`. This is the actual security fix: it closes the escalation path from any authenticated user to arbitrary option writes.
- Added a SETTABLE_OPTIONS const (ionos_essentials_maintenance_mode, ionos_essentials_dashboard_mode) and guards on both branches of the callback; the nested branch accepts only IONOS_SECURITY_FEATURE_OPTION with a key present in IONOS_SECURITY_FEATURE_OPTION_DEFAULT. Unknown keys return WP_Error with status 400. This is hardening rather than a fix — it matters mainly on multisite, where a site admin must not reach super-admin-controlled options.

Callers verified: only src/dashboard/index.js (the .input-switch handler) posts here; the MCP switch carries data-manual and uses /mcp/action instead. No client changes needed.

Verified with php -l and pnpm lint (clean). PHPUnit was not run and the endpoint was not exercised against a live site.

Changeset: .changeset/fix-essentials-option-set-endpoint.md (patch, @ionos-wordpress/essentials).
