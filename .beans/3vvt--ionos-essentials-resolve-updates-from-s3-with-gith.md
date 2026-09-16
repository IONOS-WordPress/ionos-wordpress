---
# 3vvt
title: 'ionos-essentials: resolve updates from S3 with GitHub fallback'
status: todo
type: task
priority: high
created_at: 2026-09-16T12:39:48Z
updated_at: 2026-09-16T12:39:48Z
parent: ig4m
---

Rework `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/index.php` to fetch its info.json from S3 first and fall back to GitHub.

## Todos

- [ ] Add constants for the S3 info.json URL (with the `__S3_FOLDER__` placeholder) and the GitHub info.json URL
- [ ] Implement a resolver that fetches the S3 info.json and falls back to the GitHub one only on transport error, non-200 status or invalid JSON
- [ ] Register the resolver for both `update_plugins_github.com` and `update_plugins_s3-de-central.profitbricks.com`
- [ ] Change the `Update URI` header in `packages/wp-plugin/ionos-essentials/ionos-essentials.php` to the S3 URL
- [ ] Fix the `plugins_api` filter: it currently derives the GitHub user/repo by exploding `$plugin_data['UpdateURI']`, which breaks once that header points at S3 - use an explicit constant for the raw changelog URL instead
- [ ] Keep the existing `error_log()` diagnostics and extend them so a failed S3 attempt is distinguishable from a failed GitHub attempt

## Notes

The dual hook registration is what keeps already installed copies updatable: WordPress only dispatches `update_plugins_<host>` for the host found in the installed plugin's header, so an installation still carrying the GitHub header would otherwise stop receiving updates.

A valid S3 info.json always wins - no version comparison against GitHub.
