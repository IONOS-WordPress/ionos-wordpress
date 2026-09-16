---
# ti5q
title: Fix strip_tags(null) deprecation on onboarding admin page
status: completed
type: bug
priority: normal
created_at: 2026-08-06T13:16:26Z
updated_at: 2026-08-06T13:22:57Z
---

add_submenu_page() is called with parent_slug=false for the onboarding page, which prevents get_admin_page_title() from resolving a title from $menu/$submenu. This leaves the $title global null, and wp-admin/admin-header.php's strip_tags($title) call triggers a PHP 8.1+ deprecation notice. Fixed by explicitly setting $title on the page's load-{hook} action.

## Summary of Changes

- packages/wp-plugin/ionos-essentials/ionos-essentials/inc/switch-page/index.php: register the onboarding submenu under Tenant::get_slug() (real parent) instead of false, and conditionally remove_submenu_page() it when not on the onboarding page itself
- Folded into existing .changeset/fix-buttons-style-doing-it-wrong.md (patch)
