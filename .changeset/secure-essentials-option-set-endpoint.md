---
'@ionos-wordpress/essentials': patch
---

fix(essentials): restrict the option/set endpoint to administrators and a typed option allowlist

The `ionos/essentials/option/set` REST endpoint accepted any logged-in user and wrote arbitrary
WordPress options, which allowed privilege escalation. It now requires the `manage_options`
capability and only accepts options listed in a name-to-type allowlist, with the submitted value
cast to the configured type.
