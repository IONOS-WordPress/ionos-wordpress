---
# vok6
title: Document the S3 release target and add changesets
status: completed
type: task
priority: normal
created_at: 2026-09-16T12:40:17Z
updated_at: 2026-09-17T13:23:57Z
parent: ig4m
---

## Todos

- [x] Extend `docs/7-release.md` with a section describing the S3 target: bucket, folder, the `S3_FOLDER` variable, the full file listing and the two info.json flavours
- [x] Document the transition period (S3 first, GitHub fallback) and the condition under which the GitHub fallback can eventually be removed
- [x] Update the "publishing a new plugin or mu-plugin" checklist in `docs/7-release.md`, which currently tells the reader to set the `Update URI` header to a GitHub URL
- [x] Correct the claim in `docs/7-release.md` that mu-plugins are download-only and never self-update, with `test-mu-plugin` as the only pilot exception - `ionos-core` has had a full self-updater all along (`packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php`, loaded from `ionos-core.php:19`). Describe the actual mechanism instead: a `wp_update_plugins` cron hook that fetches the update descriptor itself, compares against its own `Version` header and installs through `MU_Plugin_Upgrader`, because WordPress core offers mu-plugins no update mechanism at all
- [x] Describe the test-phase workflow: run it in a fork with `S3_FOLDER=test` in the fork's uncommitted `.env.local`, and explain why it must not be run in the main repository (the S3 zips are the same artifacts attached to `@ionos-wordpress/latest`, and build and release have to agree on the folder value)
- [x] Write changesets for the affected packages and present them for approval before writing (ionos-core already had one from vjp2; wrote one for ionos-essentials; skipped ionos-wpdev-caddy - it is `private` and explicitly out of scope for the S3 migration per epic ig4m, no related code changed)

## Summary of Changes

Extended `docs/7-release.md`:
- New section 'the S3 release target' documenting the bucket/endpoint, the `S3_FOLDER` variable and its production/fork guard, the full per-zip file listing, and the two `info.json` flavours (GitHub vs S3 `package` URL).
- New subsection 'S3-first, GitHub-fallback resolution (transition period)' documenting the fallback conditions and the removal criterion (no installation left carrying the pre-migration state).
- New subsection 'Test-phase releases in a fork' consolidating the runbook (repository variable/secrets, push+trigger steps, verification via public HTTPS).
- Fixed the 'publishing a new plugin or mu-plugin' checklist: corrected the false 'mu-plugins are download-only, test-mu-plugin is the only exception' claim to describe `ionos-core`'s actual self-updater (`wp_update_plugins` cron, own version comparison, `MU_Plugin_Upgrader`); changed the wp-plugin `Update URI` instruction from the GitHub-only URL to the S3 URL with the `__S3_FOLDER__` placeholder plus the `LEGACY_INFO_JSON_URL` GitHub fallback.

Changesets: `.changeset/ionos-essentials-s3-update-fallback.md` added (patch) for the essentials S3 resolver commits that had shipped without one. `ionos-core` already had one from vjp2. Skipped `ionos-wpdev-caddy` - confirmed with the user it is `private` and explicitly out of scope for this epic (ig4m), no related code changed.
