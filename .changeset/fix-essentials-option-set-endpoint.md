---
'@ionos-wordpress/essentials': patch
---

fix a privilege escalation in the `ionos/essentials/option/set` REST endpoint: the permission callback only checked that a user was logged in and the option name was unvalidated, so any authenticated user (including subscribers) could overwrite arbitrary WordPress options. The endpoint now requires the `manage_options` capability and accepts only the option keys the dashboard actually toggles
