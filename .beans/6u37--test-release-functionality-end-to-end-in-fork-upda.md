---
# 6u37
title: Test release functionality end-to-end in fork + update mechanism
status: completed
type: task
priority: normal
created_at: 2026-09-28T08:33:36Z
updated_at: 2026-09-28T09:11:09Z
---

Do a fresh test-phase release in fork lgersman/ionos-wordpress (S3_FOLDER=test), after cleaning up prior test releases (GitHub releases + S3 test folder objects), then verify ionos-core self-update mechanism against a local isolated WordPress stack.

## Plan

- [x] Delete existing GitHub fork releases (essentials@1.7.3, ionos-core@0.5.2, latest) + their tags
- [x] Delete existing S3 test-folder objects for those plugins
- [x] Push feat/replace-github-s3 to origin main (triggers pre-release.yml)
- [x] Watch pre-release run to completion (note: final merge-back-to-develop step in pre-release.sh failed due to divergent history after force-push of main; releases were already created before that step, so it did not affect the outcome)
- [x] Trigger release workflow manually (gh workflow run "release (manual workflow)")
- [x] Watch release run to completion
- [x] Verify S3 objects (curl info.json + zip HEAD) and GitHub releases -> ionos-core 0.5.1, ionos-essentials 1.7.2, both info.json + zips present in s3://web-hosting/test/, @ionos-wordpress/latest github release created
- [x] Set up isolated TEST_PRODUCTION docker stack for update-mechanism test (CONTAINER_NAME=ionos-wordpress-update-test, port 8899)
- [x] Force update check + run wp_update_plugins cron for ionos-core, confirm version bump -> 0.5.0 -> 0.5.1, matching the published S3 info.json
- [x] Clean up isolated stack (pnpm destroy), reset fork main (git push --force origin upstream/main:main)

## Summary of Changes

Verified the S3-only release/update pipeline end-to-end in the fork lgersman/ionos-wordpress:

1. Cleaned up prior test-phase artifacts: deleted the fork's 3 old GitHub releases + tags, and
   deleted the ionos-core/ionos-essentials objects (all versions) from s3://web-hosting/test/,
   leaving unrelated shared-bucket objects untouched.
2. Pushed feat/replace-github-s3 to origin main (force-push required, since the fork's main had
   diverged from a previous test round) to trigger pre-release.yml. It created GitHub pre-releases
   ionos-core@0.5.1 and essentials@1.7.2. The workflow's final step (merging main back into the
   fork's develop) failed due to divergent history from the force-push - this is a known,
   expected side effect documented in docs/6-forking.md ("never merge that main back") and did
   not affect the release artifacts, which were already created before that step.
3. Manually triggered the "release (manual workflow)" run, which promoted the pre-releases to
   @ionos-wordpress/latest and mirrored zips + <plugin>-info.json to s3://web-hosting/test/.
   Verified both info.json files and a zip download over HTTP with the correct version/package
   fields.
4. Set up an isolated TEST_PRODUCTION docker stack (docs/5-test.md recipe) via .env.local
   (CONTAINER_NAME=ionos-wordpress-update-test, port 8899), built at ionos-core 0.5.0, cleared
   the update_plugins transient, and ran the wp_update_plugins cron event.
5. Confirmed the ionos-core mu-plugin self-updated from 0.5.0 to 0.5.1 in place (via the custom
   MU_Plugin_Upgrader), proving the whole S3-only update-checker resolves, downloads, and installs
   correctly end to end.
6. Cleaned up: pnpm destroy for the isolated stack, and reset the fork's main branch back to
   upstream/main.

No code changes were made to the repo itself - this was a pure verification/testing pass of the
existing S3-only release and update-checker changes already on this branch.
