---
'ionos-wordpress': patch
---

generalize the release mechanism to support multiple concurrently-releasable non-private packages

- fix release.sh/pre-release.sh to support multiple concurrently-releasable non-private packages
- close race between pre-release and release workflows by sharing one concurrency group
- update release docs and script header comments for multi-package support
