---
# yvw0
title: 'ionos-wpdev-caddy: add an update checker and point it at S3'
status: scrapped
type: task
priority: normal
created_at: 2026-09-16T12:40:05Z
updated_at: 2026-09-16T12:49:59Z
parent: ig4m
---

`packages/wp-plugin/ionos-wpdev-caddy/ionos-wpdev-caddy.php` carries an `Update URI` header pointing at `ionos-support-info.json`, but the plugin has no update checker implementation at all, so the header currently has no effect.

## Todos

- [ ] Copy the update checker pattern from ionos-essentials into the plugin
- [ ] Adjust the plugin folder name, the info.json file name and the changelog source URL
- [ ] Point the `Update URI` header at the S3 info.json URL, using the `__S3_FOLDER__` placeholder
- [ ] Register both the GitHub and the S3 update hook, as in ionos-essentials
- [ ] Confirm the info.json file name matches what `scripts/release.sh` derives from the package folder name - today's header says `ionos-support-info.json` while the package folder is `ionos-wpdev-caddy`, which looks inconsistent and needs to be resolved

## Notes

The mismatch between `ionos-support-info.json` and the `ionos-wpdev-caddy` package folder should be clarified before implementing; `scripts/release.sh` derives the info.json name from the pnpm workspace folder basename.

## Reasons for Scrapping

`packages/wp-plugin/ionos-wpdev-caddy/package.json` sets `"private": true`, and `scripts/pre-release.sh:137` skips private packages. The plugin is therefore never pre-released, never gets a GitHub release asset, never gets a `<plugin>-info.json` and never reaches S3. An update checker would have nothing to resolve against.

The `ionos-support-info.json` reference in the `Update URI` header (and the stale `Plugin URI` pointing at a `packages/wp-plugin/ionos-support` folder that no longer exists) is leftover from a rename, not a naming rule. Had the package ever been released, `scripts/release.sh:116` would derive the info.json name from the pnpm workspace folder basename, i.e. `ionos-wpdev-caddy-info.json`.

The header cleanup was split out into its own bean. Onboarding the plugin into the release cycle would require flipping it to public, which is a separate product decision.
