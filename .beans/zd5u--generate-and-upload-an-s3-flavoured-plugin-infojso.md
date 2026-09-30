---
# zd5u
title: Generate and upload an S3 flavoured <plugin>-info.json
status: completed
type: task
priority: high
created_at: 2026-09-16T12:39:32Z
updated_at: 2026-09-16T13:08:28Z
parent: ig4m
---

`scripts/release.sh` currently builds one `<plugin>-info.json` whose `package` field points at the GitHub release download URL, and uploads it to the GitHub release only. S3 needs its own flavour.

## Todos

- [x] Build a second info.json whose `package` points at `https://s3-de-central.profitbricks.com/web-hosting/$S3_FOLDER/<versioned-asset>`
- [x] Keep the existing GitHub flavour pointing at the GitHub release download URL, unchanged
- [x] Upload the S3 flavour to `s3://web-hosting/$S3_FOLDER/<plugin>-info.json`
- [x] Reuse the existing `jq -n` invocation for both flavours instead of duplicating the JSON construction
- [x] Verify the resulting JSON keys still match what the update resolvers expect: `version`, `slug`, `package`, `last_updated`, `requires_wp`, `sections.changelog`

## Notes

Both files carry the same name `<plugin>-info.json` and differ only in the `package` URL; they simply live in different places.

Pre-existing quirk worth being aware of: the info.json is regenerated inside the per-asset loop, so with several PHP variants the `package` URL ends up pointing at whichever variant was processed last. This bean does not change that behaviour, it only mirrors it to S3.

## Summary of Changes

`scripts/release.sh` now renders the update descriptor twice from one shared definition: the jq filter moved into `INFO_JSON_FILTER` and the invariant fields into an `INFO_JSON_ARGS` array, so both flavours differ in nothing but their `--arg package` value.

- The GitHub flavour keeps pointing at the pre-release download URL and is attached to `@ionos-wordpress/latest` as before.
- The S3 flavour points at `$S3_BASE_URL/$ASSET`, i.e. the versioned zip next to it in the same S3 folder, and is uploaded as `<plugin>-info.json` via `ionos.wordpress.s3_upload`.

Verified by rendering both flavours with representative values: the output is byte-identical apart from `package`, and the key set (`version`, `slug`, `package`, `last_updated`, `requires_wp`, `sections.changelog`) is unchanged from what the update resolvers consume today.
