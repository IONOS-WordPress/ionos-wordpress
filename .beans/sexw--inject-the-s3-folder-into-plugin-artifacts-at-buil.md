---
# sexw
title: Inject the S3 folder into plugin artifacts at build time
status: todo
type: task
priority: high
created_at: 2026-09-16T12:39:48Z
updated_at: 2026-09-16T12:39:48Z
parent: ig4m
---

The `Update URI` header and the S3 base URL inside each plugin have to know which S3 folder they are served from (`ionos-group` in production, `test` during the test phase).

## Todos

- [ ] Introduce the placeholder `__S3_FOLDER__` in the `Update URI` header and in the S3 URL constant of every in-scope plugin
- [ ] Extend `scripts/build.sh` to replace `__S3_FOLDER__` with `$S3_FOLDER` in the staged plugin directory, next to the existing `Requires PHP` rewrite
- [ ] Cover the main plugin file, `readme.txt` and any PHP file holding the S3 URL constant
- [ ] Verify `scripts/lint.sh` still accepts the placeholder form (it only asserts that `Update URI:` is present and non-empty)
- [ ] Add a short section to `docs/2-build.md` describing the placeholder

## Notes

`scripts/build.sh` already rewrites the `Requires PHP` header per PHP target variant; the same `sed` step is the natural place for this.

Caveat to keep in mind: the header is baked during the pre-release workflow while the S3 upload happens in the release workflow. Both runs must see the same `S3_FOLDER` value, otherwise the shipped plugins point at a folder the release did not write to.
