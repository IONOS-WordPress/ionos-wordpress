---
'@ionos-wordpress/essentials': patch
---

fix wpscan upstream error retry storm by caching empty result with 5-minute backoff TTL
