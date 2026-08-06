---
'@ionos-wordpress/essentials': patch
---

fix PHP notices on the onboarding admin page: dequeue (not deregister) the `buttons` style to avoid a `_doing_it_wrong` notice, and explicitly set the `$title` global to avoid a `strip_tags(null)` deprecation notice
