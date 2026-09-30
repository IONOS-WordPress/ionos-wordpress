---
# 4bvx
title: Test ionos-essentials self-update path (wp-plugin, detection/download only)
status: completed
type: task
priority: normal
created_at: 2026-09-28T09:20:10Z
updated_at: 2026-09-28T09:22:25Z
---

Extend the fork release + update-mechanism verification (bean 6u37) to also exercise ionos-essentials wp-plugin update path via Plugin_Upgrader. Per docs/5-test.md this is expected to prove detection+download from S3 but fail at the final directory-swap step due to the TEST_PRODUCTION bind mount.

Related to 6u37 (fork release + ionos-core update test, completed).

## Summary of Changes

Tested the ionos-essentials (wp-plugin) self-update path against the same S3 test-folder release
(v1.7.2) from bean 6u37, in a fresh isolated TEST_PRODUCTION stack:

1. Temporarily commented out the `ionos-essentials` entry in
   packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/stretch-extra-config.php (its
   `upgrader_pre_install` filter otherwise rejects any update attempt for a stretch-extra
   provisioned slug before Plugin_Upgrader even runs), rebuilt, and started the isolated stack.
2. `wp plugin list --update=available` already showed 1.7.1 -> 1.7.2 available, resolved from the
   S3 info.json (no GitHub fallback needed, since S3 answered).
3. `wp plugin update ionos-essentials` downloaded and unpacked
   https://s3-de-central.profitbricks.com/web-hosting/test/ionos-essentials-1.7.2-php7.4.zip
   correctly, then failed at "Removing the old version of the plugin... Could not remove the old
   plugin" - exactly the known, documented limitation in docs/5-test.md: WordPress core's
   Plugin_Upgrader can't replace a directory that is itself a bind mount in TEST_PRODUCTION mode.
   This confirms resolver + download work end to end for the wp-plugin path; it cannot prove the
   final install step by design of this test setup (would need a non-bind-mounted plugin
   directory, e.g. a real installed-from-zip plugin, to go further).
4. Cleaned up: pnpm destroy, then reverted the stretch-extra-config.php change (working tree is
   clean again - `git status --porcelain` only shows the two new .beans/*.md files).

No code changes were made to the repo - the stretch-extra-config.php edit was temporary and fully
reverted after the test.
