---
# vok6
title: Document the S3 release target and add changesets
status: todo
type: task
priority: normal
created_at: 2026-09-16T12:40:17Z
updated_at: 2026-09-17T11:53:29Z
parent: ig4m
---

## Todos

- [ ] Extend `docs/7-release.md` with a section describing the S3 target: bucket, folder, the `S3_FOLDER` variable, the full file listing and the two info.json flavours
- [ ] Document the transition period (S3 first, GitHub fallback) and the condition under which the GitHub fallback can eventually be removed
- [ ] Update the "publishing a new plugin or mu-plugin" checklist in `docs/7-release.md`, which currently tells the reader to set the `Update URI` header to a GitHub URL
- [ ] Correct the claim in `docs/7-release.md` that mu-plugins are download-only and never self-update, with `test-mu-plugin` as the only pilot exception - `ionos-core` has had a full self-updater all along (`packages/wp-mu-plugin/ionos-core/ionos-core/update/index.php`, loaded from `ionos-core.php:19`). Describe the actual mechanism instead: a `wp_update_plugins` cron hook that fetches the update descriptor itself, compares against its own `Version` header and installs through `MU_Plugin_Upgrader`, because WordPress core offers mu-plugins no update mechanism at all
- [ ] Describe the test-phase workflow: run it in a fork with `S3_FOLDER=test` in the fork's uncommitted `.env.local`, and explain why it must not be run in the main repository (the S3 zips are the same artifacts attached to `@ionos-wordpress/latest`, and build and release have to agree on the folder value)
- [ ] Write changesets for the affected packages (ionos-essentials, ionos-core, ionos-wpdev-caddy) and present them for approval before writing
