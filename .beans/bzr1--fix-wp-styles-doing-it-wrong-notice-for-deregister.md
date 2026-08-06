---
# bzr1
title: Fix WP_Styles doing_it_wrong notice for deregistered buttons style
status: completed
type: bug
priority: normal
created_at: 2026-08-06T13:08:04Z
updated_at: 2026-08-06T13:09:41Z
---

wp_deregister_style('buttons') in ionos-essentials switch-page removes the buttons style registration entirely, but WP core's 'colors' admin stylesheet depends on it, triggering a _doing_it_wrong notice (added in WP 6.9.1). Fixed by using wp_dequeue_style('buttons') instead in both index.php and view.php.

## Summary of Changes

- packages/wp-plugin/ionos-essentials/ionos-essentials/inc/switch-page/index.php: replaced wp_deregister_style('buttons') with \wp_dequeue_style('buttons')
- packages/wp-plugin/ionos-essentials/ionos-essentials/inc/switch-page/view.php: replaced wp_deregister_style('buttons') with \wp_dequeue_style('buttons')
- Added .changeset/fix-buttons-style-doing-it-wrong.md (patch)
