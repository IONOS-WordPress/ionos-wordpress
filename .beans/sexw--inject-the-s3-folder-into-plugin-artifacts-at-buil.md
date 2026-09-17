---
# sexw
title: Inject the S3 folder into plugin artifacts at build time
status: completed
type: task
priority: high
created_at: 2026-09-16T12:39:48Z
updated_at: 2026-09-17T10:31:43Z
parent: ig4m
---

The `Update URI` header and the S3 base URL inside each plugin have to know which S3 folder they are served from (`ionos-group` in production, `test` during the test phase).

## Todos

- [x] Introduce the placeholder `__S3_FOLDER__` in the `Update URI` header and in the S3 URL constant of every in-scope plugin - moved to the plugin beans, so each commit stays coherent: a header pointing at the S3 host without the matching resolver would leave the plugin unable to update
- [x] Extend `scripts/build.sh` to replace `__S3_FOLDER__` with `$S3_FOLDER` in the staged plugin directory, next to the existing `Requires PHP` rewrite
- [x] Cover the main plugin file, `readme.txt` and any PHP file holding the S3 URL constant
- [x] Verify `scripts/lint.sh` still accepts the placeholder form (it only asserts that `Update URI:` is present and non-empty)
- [x] Add a short section to `docs/2-build.md` describing the placeholder

## Notes

`scripts/build.sh` already rewrites the `Requires PHP` header per PHP target variant; the same `sed` step is the natural place for this.

Caveat to keep in mind: the header is baked during the pre-release workflow while the S3 upload happens in the release workflow. Both runs must see the same `S3_FOLDER` value, otherwise the shipped plugins point at a folder the release did not write to.

## Decision : no guard in build.sh

Carrying the repository identity guard into `scripts/build.sh` was considered and rejected. `scripts/pre-release.sh` has no dedicated release build - it just runs `pnpm test`, which builds through the very same `build.sh` every developer uses. A guard there would abort ordinary builds, including the integration pipeline on feature branches, in every fork that has not set the repository variable.

The protection that matters sits at publish time: nothing reaches users except through `scripts/release.sh`, which is guarded. A build with a mismatched folder only produces artifacts that are never published. The constants therefore stay in `release.sh` instead of being extracted.

`S3_FOLDER` reaches CI through the `S3_FOLDER` repository variable, exported by both `pre-release.yml` and `release.yaml`, so the build and the upload read the same value by construction.

## Summary of Changes

`scripts/build.sh` substitutes `__S3_FOLDER__` with `$S3_FOLDER` right after the plugin sources are staged into `dist/<plugin>-<version>/`. That is upstream of everything else: rector copies each PHP target variant from this directory and the zips are built from those copies, so one substitution covers every shipped artifact regardless of which `--use` steps run.

Rather than listing files to patch, it substitutes in whatever contains the placeholder (`grep -rlZ --binary-files=without-match`), so a plugin can put the S3 URL wherever it likes without touching the build script.

Verified: the code section of `build.sh` parses (the file as a whole never has, because of the `###help-message` block after its `exit`); substitution works across nested files, skips binaries, and a second run with no remaining matches does not abort under `set -eo pipefail`.

`scripts/lint.sh` needs no change - its `Update URI` check only asserts the field is present and non-empty, which the placeholder form satisfies.

`docs/2-build.md` documents the step in the build workflow list.
