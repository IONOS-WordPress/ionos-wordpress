---
# 4uj1
title: Test the S3-first update resolution
status: completed
type: task
priority: normal
created_at: 2026-09-16T12:40:17Z
updated_at: 2026-09-18T09:57:12Z
parent: ig4m
---

Verify the new resolution order without waiting for a real release.

## Todos

- [x] Add PHPUnit tests for the ionos-essentials resolver, stubbing HTTP through the `pre_http_request` filter
- [x] Cover: S3 answers with valid JSON (GitHub must not be queried), S3 returns a non-200 status (GitHub answers), S3 returns malformed JSON (GitHub answers), both fail (the original `$update` value is returned untouched)
- [x] Add an equivalent test for the ionos-core resolver
- [x] Perform one end-to-end run **in a fork**, with `S3_FOLDER=test` in the fork's uncommitted `.env.local`, driving both pre-release and release there; verify the resulting S3 object listing matches the fork's GitHub release assets

## Notes

The end-to-end run is the only way to validate the `build.sh` placeholder injection together with the `release.sh` upload, since both have to agree on the same folder value.

It has to happen in a fork rather than in the main repository: the zips that go to S3 are the same artifacts attached to `@ionos-wordpress/latest`, so a test-phase release in the main repository would ship plugins pointing at the `test` folder to real users. `pre-release.yml` also has no `workflow_dispatch` trigger, so there is no per-run switch available there.

## Runbook : exercising a release in a fork

This validates the S3 half of the pipeline end to end without touching the production folder or the real `@ionos-wordpress/latest` release. `scripts/release.sh` refuses to publish from a fork into `ionos-group` and refuses to publish from the upstream repository into anything else, so a misconfiguration fails loudly instead of mispublishing.

### One-time setup in the fork

1. **Enable Actions.** Forks ship with workflows disabled - open the _Actions_ tab and confirm.
2. **Add the AWS credentials** under _Settings > Secrets and variables > Actions > Secrets_:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`

   Without them the release still runs, but every S3 upload is skipped with an error - the guard does not catch this, because it only checks repository and folder.

3. **Add the target folder** under _Settings > Secrets and variables > Actions > Variables_:
   - `S3_FOLDER` = `test`

   Both `pre-release.yml` and `release.yaml` export this variable. Leaving it unset makes the release abort, because the fork would fall through to the production folder.

### Running the release

4. Merge the work into `develop` and push it.
5. `git push origin develop:main` - triggers the `pre-release` workflow: version bump, build, `pnpm test`, then one GitHub pre-release per changed package with its zip assets attached.
6. Trigger _release (manual workflow)_ manually on `main` - promotes every pre-release and mirrors the assets to `s3://web-hosting/test/`.

### Verifying the result

The bucket is publicly readable, so the outcome can be checked without credentials. Per released package expect the versioned zip, the `latest` zip, the legacy alias and one `<plugin>-info.json`:

```
curl -sI https://s3-de-central.profitbricks.com/web-hosting/test/ionos-essentials-latest-php7.4.zip
curl -s  https://s3-de-central.profitbricks.com/web-hosting/test/ionos-essentials-info.json | jq .
```

- [x] Every asset attached to the fork's `@ionos-wordpress/latest` release also exists under `test/`, byte for byte and under the same name
- [x] Each PHP variant has its own object, i.e. no variant overwrote another
- [x] The legacy `<plugin>.latest.zip` alias exists
- [x] The `package` field of the S3 `info.json` points at the S3 copy, while the one attached to the GitHub release points at GitHub
- [x] Nothing was written to `ionos-group/`

### What to expect

- `scripts/pre-release.sh:84` runs the full test suite - this is the most likely reason for a long or failing run.
- `scripts/pre-release.sh:77` consumes the pending changesets and pushes version bumps and tags into the fork, so the fork's `develop` diverges from upstream afterwards.
- The production AWS credentials are used, only with `test/` as the target inside the same `web-hosting` bucket. Use separate credentials if that is not acceptable.

## Progress

Added PHPUnit coverage for both resolvers:

- `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/update/tests/phpunit/UpdateTest.php`
- `packages/wp-mu-plugin/ionos-core/ionos-core/update/tests/phpunit/UpdateTest.php`

Both stub `pre_http_request` per-URL and cover: S3 valid JSON (GitHub not queried), S3 non-200, S3 malformed JSON, both sources failing (returns `null`); the ionos-core suite additionally covers a JSON body missing `version`/`package`. All 9 tests pass via `pnpm test:php --php-opts "--filter UpdateTest"`, lint is clean.

## End-to-end fork run (2026-09-18)

Ran on `lgersman/ionos-wordpress` (fork), `S3_FOLDER=test`, per the runbook above.

**Bug found and fixed along the way**: the fork was missing 3 upstream `develop` commits that the pre-release script needed: `8c67af50` (fix a SIGPIPE abort in `changeset.sh version` - `grep -q`'s early exit under `set -o pipefail` aborted the whole script with exit 141), `7c3cf0f5` and `23cc4aa0` (run tests in production mode and build before testing). Cherry-picked onto the fork's disposable `main` branch only - never onto a branch that could reach the upstream PR - so this repo's `develop`/PR branches need no changes; the fix already exists upstream.

Also found and fixed: the fork's `S3_FOLDER` GitHub Actions repository variable had `\r\n\n` embedded in its value (reset to a clean `test`).

**Result**: `pre-release` created `@ionos-wordpress/essentials@1.7.1` and `@ionos-wordpress/ionos-core@0.5.1` pre-releases; `release (manual workflow)` promoted both into `@ionos-wordpress/latest` and mirrored to S3.

Verified directly (no assumptions):

- All 8 expected S3 objects (versioned zip, `-latest-` zip, legacy `.latest.zip` alias, `-info.json`, per package) return `200` anonymously.
- Zip sizes match byte-for-byte between the GitHub release asset and its S3 twin (essentials: 854771 bytes both; core: 64586 bytes both) - no PHP variant overwrote another.
- Each `info.json`'s `package` field points at its own source: S3 info.json → S3 zip URL, GitHub release's info.json → GitHub download URL. Sizes differ only by the URL length (essentials: 736 vs 690 bytes; core: 442 vs 396 bytes), confirming the two flavours are otherwise identical.
- Downloaded and unzipped the actual shipped `ionos-essentials.php`: its `Update URI` header correctly resolved the `__S3_FOLDER__` placeholder to `https://s3-de-central.profitbricks.com/web-hosting/test/ionos-essentials-info.json` - the build-time placeholder injection and the release-time upload agree on the folder, exactly as designed.
- `ionos-wpdev-caddy` correctly skipped (private, never released).
- Nothing written to `ionos-group/`: confirmed via authenticated `list-objects-v2` before and after - only pre-existing entries there, none of this run's assets.
- `feat/replace-github-s3` (the real branch for the eventual upstream PR) stayed untouched throughout - all 4 changesets still unconsumed, no version bumps. Every mutating commit (the 3 cherry-picks, plus `changeset version`'s own `chore(release)` commit) landed only on the fork's disposable `main`.

This closes the loop end-to-end: a real WordPress install resolving its update from S3 anonymously would get a package URL that is also on S3, both publicly reachable, matching production behavior.
