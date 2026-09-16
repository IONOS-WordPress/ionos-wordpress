---
# zd5u
title: Generate and upload an S3 flavoured <plugin>-info.json
status: todo
type: task
priority: high
created_at: 2026-09-16T12:39:32Z
updated_at: 2026-09-16T12:39:32Z
parent: ig4m
---

`scripts/release.sh` currently builds one `<plugin>-info.json` whose `package` field points at the GitHub release download URL, and uploads it to the GitHub release only. S3 needs its own flavour.

## Todos

- [ ] Build a second info.json whose `package` points at `https://s3-de-central.profitbricks.com/web-hosting/$S3_FOLDER/<versioned-asset>`
- [ ] Keep the existing GitHub flavour pointing at the GitHub release download URL, unchanged
- [ ] Upload the S3 flavour to `s3://web-hosting/$S3_FOLDER/<plugin>-info.json`
- [ ] Reuse the existing `jq -n` invocation for both flavours instead of duplicating the JSON construction
- [ ] Verify the resulting JSON keys still match what the update resolvers expect: `version`, `slug`, `package`, `last_updated`, `requires_wp`, `sections.changelog`

## Notes

Both files carry the same name `<plugin>-info.json` and differ only in the `package` URL; they simply live in different places.

Pre-existing quirk worth being aware of: the info.json is regenerated inside the per-asset loop, so with several PHP variants the `package` URL ends up pointing at whichever variant was processed last. This bean does not change that behaviour, it only mirrors it to S3.
