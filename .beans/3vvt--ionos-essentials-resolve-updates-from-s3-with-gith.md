---
# 3vvt
title: 'ionos-essentials: resolve updates from S3 with GitHub fallback'
status: completed
type: task
priority: high
created_at: 2026-09-16T12:39:48Z
updated_at: 2026-09-17T11:47:43Z
parent: ig4m
---

Rework `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php` to fetch its info.json from S3 first and fall back to GitHub.

## Todos

- [x] Add constants for the S3 info.json URL (with the `__S3_FOLDER__` placeholder, substituted by `scripts/build.sh`) and the GitHub info.json URL
- [x] Implement a resolver that fetches the S3 info.json and falls back to the GitHub one only on transport error, non-200 status or invalid JSON
- [x] Register the resolver for both `update_plugins_github.com` and `update_plugins_s3-de-central.profitbricks.com`
- [x] Change the `Update URI` header in `packages/wp-plugin/ionos-essentials/ionos-essentials.php` to the S3 URL
- [x] Fix the `plugins_api` filter: it currently derives the GitHub user/repo by exploding `$plugin_data['UpdateURI']`, which breaks once that header points at S3 - use an explicit constant for the raw changelog URL instead
- [x] Keep the existing `error_log()` diagnostics and extend them so a failed S3 attempt is distinguishable from a failed GitHub attempt

## Notes

The dual hook registration is what keeps already installed copies updatable: WordPress only dispatches `update_plugins_<host>` for the host found in the installed plugin's header, so an installation still carrying the GitHub header would otherwise stop receiving updates.

A valid S3 info.json always wins - no version comparison against GitHub.

## Summary of Changes

`inc/update/index.php` now drives the update check from two constants instead of from the `Update URI` header:

- The plugin's own `Update URI` header is the authoritative source and is queried first. `fetch_update_info($update_uri)` returns the first source that answers with usable JSON, skipping a source on transport error, on a non-200 status and on a body that does not decode to an array. Every skip is logged with the URL that failed, so the sources are distinguishable in the log.
- `LEGACY_INFO_JSON_URL` holds the GitHub descriptor and is only queried when the header's URL fails. `array_unique` collapses the list for an installation whose header still _is_ the GitHub URL, so that case makes one request rather than two identical ones.
- `CHANGELOG_URL` replaces the previous derivation of the GitHub user and repository by exploding the `Update URI` header, which would have produced garbage once that header points at S3.

The filter is registered for both `update_plugins_s3-de-central.profitbricks.com` and `update_plugins_github.com` in a loop over the two hosts, so an installation that still carries the old header keeps updating.

The `Update URI` header in `ionos-essentials.php` now points at the S3 descriptor and carries the `__S3_FOLDER__` placeholder. Because the resolver reads the header instead of a parallel constant, that header is the single place the S3 URL is written - the placeholder occurs exactly once in the whole plugin.

## Deliberately left alone

The translatable string in the `plugins_api` error branch was kept byte for byte, so the existing translations in all shipped locales stay valid - only the URL passed into it changed. The string itself has two pre-existing defects worth a separate ticket: it names `<plugin>-info.json` although what failed is the changelog download, and its markup is written as `<a href=\"%1$s\">` inside a single-quoted PHP string, so the backslashes are literal and the whole thing is run through `esc_html()` anyway, which means no link is ever rendered.

## Verification

- `php -l` clean
- `pnpm lint --use php` passes, which covers both the plugin header check (it accepts the placeholder form) and ECS formatting
- Simulated the build substitution over a copy of the plugin: the header and the constant both resolve to the configured folder, while the hook host list and the unrelated `IONOS_LOOP_DATACOLLECTOR_PUBLIC_KEY_URL` (which points into a different folder of the same bucket) are untouched
