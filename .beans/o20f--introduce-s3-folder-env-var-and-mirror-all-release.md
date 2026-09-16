---
# o20f
title: Introduce S3_FOLDER env var and mirror all release assets to S3
status: todo
type: task
priority: high
created_at: 2026-09-16T12:39:32Z
updated_at: 2026-09-16T12:39:32Z
parent: ig4m
---

Make the S3 target folder configurable and turn the S3 upload in `scripts/release.sh` into a full mirror of the GitHub release assets.

## Todos

- [ ] Add `S3_FOLDER="${S3_FOLDER:-ionos-group}"` to `.env`, next to the other release-related settings, with a comment explaining the test-phase override
- [ ] Document the `S3_FOLDER=test` override in `.env.local.example`
- [ ] Replace the hardcoded `s3://web-hosting/ionos-group/` target in `scripts/release.sh` with `s3://web-hosting/$S3_FOLDER/`
- [ ] Upload the versioned asset (`<plugin>-<version>-php<x>.zip`) to S3 under its original name
- [ ] Upload the `latest` asset (`<plugin>-latest-php<x>.zip`) to S3 under its original name, fixing today's collision between PHP variants
- [ ] Keep writing the legacy alias `<plugin>.latest.zip` for backwards compatibility
- [ ] Factor the repeated `docker run amazon/aws-cli` invocation into a small helper function so the three uploads do not duplicate the credential setup

## Notes

The bucket (`web-hosting`) and the endpoint (`https://s3-de-central.profitbricks.com`) stay hardcoded on purpose; only the folder is configurable.

The current name rewrite lives in `scripts/release.sh:131`:
`S3_FILENAME=$(echo $TARGET_ASSET_FILENAME | sed -E 's/-latest-.+$/.latest.zip/')`
It collapses `<plugin>-latest-php7.4.zip` and `<plugin>-latest-php8.3.zip` onto the same key, so whichever PHP variant is processed last wins. That name is kept only as an additional alias.
