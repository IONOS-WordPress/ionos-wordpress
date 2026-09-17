---
# 4uj1
title: Test the S3-first update resolution
status: todo
type: task
priority: normal
created_at: 2026-09-16T12:40:17Z
updated_at: 2026-09-17T09:31:20Z
parent: ig4m
---

Verify the new resolution order without waiting for a real release.

## Todos

- [ ] Add PHPUnit tests for the ionos-essentials resolver, stubbing HTTP through the `pre_http_request` filter
- [ ] Cover: S3 answers with valid JSON (GitHub must not be queried), S3 returns a non-200 status (GitHub answers), S3 returns malformed JSON (GitHub answers), both fail (the original `$update` value is returned untouched)
- [ ] Add an equivalent test for the ionos-core resolver
- [ ] Perform one end-to-end run **in a fork**, with `S3_FOLDER=test` in the fork's uncommitted `.env.local`, driving both pre-release and release there; verify the resulting S3 object listing matches the fork's GitHub release assets

## Notes

The end-to-end run is the only way to validate the `build.sh` placeholder injection together with the `release.sh` upload, since both have to agree on the same folder value.

It has to happen in a fork rather than in the main repository: the zips that go to S3 are the same artifacts attached to `@ionos-wordpress/latest`, so a test-phase release in the main repository would ship plugins pointing at the `test` folder to real users. `pre-release.yml` also has no `workflow_dispatch` trigger, so there is no per-run switch available there.

## Runbook : exercising a release in a fork

This validates the S3 half of the pipeline end to end without touching the production folder or the real `@ionos-wordpress/latest` release. `scripts/release.sh` refuses to publish from a fork into `ionos-group` and refuses to publish from the upstream repository into anything else, so a misconfiguration fails loudly instead of mispublishing.

### One-time setup in the fork

1. **Enable Actions.** Forks ship with workflows disabled - open the *Actions* tab and confirm.
2. **Add the AWS credentials** under *Settings > Secrets and variables > Actions > Secrets*:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`

   Without them the release still runs, but every S3 upload is skipped with an error - the guard does not catch this, because it only checks repository and folder.
3. **Add the target folder** under *Settings > Secrets and variables > Actions > Variables*:
   - `S3_FOLDER` = `test`

   Both `pre-release.yml` and `release.yaml` export this variable. Leaving it unset makes the release abort, because the fork would fall through to the production folder.

### Running the release

4. Merge the work into `develop` and push it.
5. `git push origin develop:main` - triggers the `pre-release` workflow: version bump, build, `pnpm test`, then one GitHub pre-release per changed package with its zip assets attached.
6. Trigger *release (manual workflow)* manually on `main` - promotes every pre-release and mirrors the assets to `s3://web-hosting/test/`.

### Verifying the result

The bucket is publicly readable, so the outcome can be checked without credentials. Per released package expect the versioned zip, the `latest` zip, the legacy alias and one `<plugin>-info.json`:

```
curl -sI https://s3-de-central.profitbricks.com/web-hosting/test/ionos-essentials-latest-php7.4.zip
curl -s  https://s3-de-central.profitbricks.com/web-hosting/test/ionos-essentials-info.json | jq .
```

- [ ] Every asset attached to the fork's `@ionos-wordpress/latest` release also exists under `test/`, byte for byte and under the same name
- [ ] Each PHP variant has its own object, i.e. no variant overwrote another
- [ ] The legacy `<plugin>.latest.zip` alias exists
- [ ] The `package` field of the S3 `info.json` points at the S3 copy, while the one attached to the GitHub release points at GitHub
- [ ] Nothing was written to `ionos-group/`

### What to expect

- `scripts/pre-release.sh:84` runs the full test suite - this is the most likely reason for a long or failing run.
- `scripts/pre-release.sh:77` consumes the pending changesets and pushes version bumps and tags into the fork, so the fork's `develop` diverges from upstream afterwards.
- The production AWS credentials are used, only with `test/` as the target inside the same `web-hosting` bucket. Use separate credentials if that is not acceptable.
