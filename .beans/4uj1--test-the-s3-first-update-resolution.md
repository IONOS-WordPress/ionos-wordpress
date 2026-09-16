---
# 4uj1
title: Test the S3-first update resolution
status: todo
type: task
priority: normal
created_at: 2026-09-16T12:40:17Z
updated_at: 2026-09-16T12:52:55Z
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
