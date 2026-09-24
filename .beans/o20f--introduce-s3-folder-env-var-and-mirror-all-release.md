---
# o20f
title: Introduce S3_FOLDER env var and mirror all release assets to S3
status: completed
type: task
priority: high
created_at: 2026-09-16T12:39:32Z
updated_at: 2026-09-17T09:22:18Z
parent: ig4m
---

Make the S3 target folder configurable and turn the S3 upload in `scripts/release.sh` into a full mirror of the GitHub release assets.

## Todos

- [x] Add `S3_FOLDER="${S3_FOLDER:-ionos-group}"` to `.env`, next to the other release-related settings, with a comment explaining the test-phase override
- [x] Document the `S3_FOLDER=test` override in `.env.local.example`
- [x] Replace the hardcoded `s3://web-hosting/ionos-group/` target in `scripts/release.sh` with `s3://web-hosting/$S3_FOLDER/`
- [x] Upload the versioned asset (`<plugin>-<version>-php<x>.zip`) to S3 under its original name
- [x] Upload the `latest` asset (`<plugin>-latest-php<x>.zip`) to S3 under its original name, fixing today's collision between PHP variants
- [x] Keep writing the legacy alias `<plugin>.latest.zip` for backwards compatibility
- [x] Factor the repeated `docker run amazon/aws-cli` invocation into a small helper function so the three uploads do not duplicate the credential setup

## Notes

The bucket (`web-hosting`) and the endpoint (`https://s3-de-central.profitbricks.com`) stay hardcoded on purpose; only the folder is configurable.

The current name rewrite lives in `scripts/release.sh:131`:
`S3_FILENAME=$(echo $TARGET_ASSET_FILENAME | sed -E 's/-latest-.+$/.latest.zip/')`
It collapses `<plugin>-latest-php7.4.zip` and `<plugin>-latest-php8.3.zip` onto the same key, so whichever PHP variant is processed last wins. That name is kept only as an additional alias.

## Summary of Changes

`scripts/release.sh`:

- Added the fixed `S3_ENDPOINT`/`S3_BUCKET` constants plus a `S3_BASE_URL` derived from `$S3_FOLDER`, and an early abort when `S3_FOLDER` is empty so a misconfigured override cannot scatter assets across the bucket root.
- Extracted the inline `docker run amazon/aws-cli` block into `ionos.wordpress.s3_upload <file> <object-name>`, which takes the object name separately so one downloaded file can be published under several names. Missing AWS credentials still only degrade the release instead of aborting it.
- Each asset is now mirrored three times: under its versioned pre-release name, under its `latest` name, and under the legacy `<plugin>.latest.zip` name. The legacy upload is skipped when the rewrite would not change the name, which also avoids a duplicate upload for assets without a semver in their filename.
- Updated the script header to describe the resulting S3 folder layout.

`.env`: added `S3_FOLDER="${S3_FOLDER:-ionos-group}"` following the repository's existing override pattern.

`.env.local.example`: documented the `S3_FOLDER=test` override together with the warning that it must only be used in a fork.

Verified `bash -n`, `shellcheck -S warning` (only pre-existing findings remain) and a smoke test of the helper with a stubbed `docker`, confirming the local file is mounted under the target object name and the bucket path honours `S3_FOLDER`.

## Addendum : repository identity guard

The original `.env.local` based approach did not actually ensure anything: `.env.local` is gitignored, so a fork's CI checkout never sees it and would build with `ionos-group` baked into the plugin header while a locally driven release uploads to `test`.

`scripts/release.sh` now aborts unless repository and folder match. `GITHUB_OWNER_REPO` moved up next to the S3 configuration (it only depends on `git remote`, not on the pre-release discovery) so the check fails fast, before any release work happens:

- upstream repository with a non-production folder -> abort, because those zips are attached to the real `@ionos-wordpress/latest` release and would point real users at a test folder
- fork with the production folder -> abort, so a fork cannot overwrite production assets

Verified all four repository/folder combinations behave as intended.

`.env` and `.env.local.example` were reworded accordingly: a fork commits `S3_FOLDER` into its own `.env` (that is what makes its CI see it), while `.env.local` remains a local-run convenience.

## Addendum : S3_FOLDER as a repository variable

Committing `S3_FOLDER` into a fork's `.env` works, but creates a commit that must never travel upstream in a pull request. Both release workflows now export the value from a GitHub repository variable instead:

- `.github/workflows/pre-release.yml` (inside the devcontainer `runCmd`, where the plugins are built)
- `.github/workflows/release.yaml` (in the release step, where the upload happens)

An unset variable expands to an empty string, and `.env` uses `${S3_FOLDER:-ionos-group}`, so `:-` treats it like unset and falls through to the production folder. No special casing needed.

This is safe to keep upstream permanently: without the variable it does nothing, and the repository identity guard already refuses a non-production folder when releasing from the upstream repository. A fork now configures itself entirely through Settings > Secrets and variables > Actions > Variables, with no fork-local commit to remember.
