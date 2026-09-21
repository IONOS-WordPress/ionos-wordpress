---
# iq0w
title: 'Privilege escalation: mcp/action endpoint installs and activates a plugin for any logged-in user'
status: completed
type: bug
priority: critical
created_at: 2026-09-17T09:25:36Z
updated_at: 2026-09-17T09:52:42Z
---

POST /wp-json/ionos/essentials/mcp/action (inc/mcp/index.php:24) used `0 !== get_current_user_id()` as its permission_callback, so any logged-in user (incl. subscriber) could reach it.

The callback installs a plugin zip from GitHub and activates it (inc/mcp/index.php:110-121), enables MCP site-wide with create/update/delete tools (inc/mcp/index.php:40-46), and mints an application password returned in the response body. That is remote code installation gated on nothing but having an account.

The in-callback wp_rest nonce check is authentication/CSRF, not authorization — a subscriber in wp-admin has a valid nonce.

- [x] Require an administrator capability on the route
- [x] Changeset — covered by the shared .changeset/fix-essentials-option-set-endpoint.md

## Decision: install_plugins, not manage_options

The route now uses `current_user_can('install_plugins')`, matching the sibling GML install route (inc/dashboard/index.php:183) and core's own /wp/v2/plugins create route.

There is no capability meaning "is an administrator"; both candidates resolve to the administrator role on single site, so they are equivalent there. They differ in two ways, and both favour install_plugins for this callback:

- Multisite: manage_options is held by each subsite admin, install_plugins only by super admins. A subsite admin must not install plugins network-wide.
- DISALLOW_FILE_MODS: core maps install_plugins to do_not_allow for everyone. Correct for the install path, but it also blocks the toggle-off path and the case where the MCP plugin is already installed (activate_mcp_server() returns early at inc/mcp/index.php:105).

Open question for the user: if any IONOS hosting tier sets DISALLOW_FILE_MODS, split the gate — manage_options on the route, install_plugins checked only on the branch that installs. Not done pending that answer.

Left alone deliberately: the in-callback wp_rest nonce check is redundant with REST cookie auth but harmless; removing it is separate cleanup.

Verified with php -l and pnpm lint (clean). No PHPUnit run, endpoint not exercised against a live site.

## Summary of Changes

inc/mcp/index.php: permission_callback on the ionos/essentials/mcp/action route changed from `0 !== get_current_user_id()` to `current_user_can('install_plugins')`, closing the path from any authenticated user to plugin installation, site-wide MCP activation and application-password issuance. Rationale for the capability choice is in the Decision section above.

No changeset of its own: .changeset/fix-essentials-option-set-endpoint.md (patch, @ionos-wordpress/essentials) covers both endpoint fixes, since they ship together. Its wording was deliberately kept terse ("important security fix", dd52672a) rather than naming the endpoints.
