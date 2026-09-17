---
# 6rkb
title: Privilege escalation via unrestricted /ionos/essentials/option/set REST endpoint
status: in-progress
type: bug
priority: critical
created_at: 2026-09-17T08:34:22Z
updated_at: 2026-09-17T08:38:43Z
---

Any logged-in user (incl. subscriber) can write arbitrary wp_options via POST /wp-json/ionos/essentials/option/set. permission_callback only checks that a user is logged in, and the option key/value are unvalidated, allowing e.g. default_role=administrator + users_can_register=1.\n\n- [x] Tighten permission_callback to manage_options
