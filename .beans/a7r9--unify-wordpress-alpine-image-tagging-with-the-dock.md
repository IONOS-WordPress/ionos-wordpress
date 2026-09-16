---
# a7r9
title: Unify all published images on one <image>:<tag> mechanism
status: completed
type: task
priority: normal
created_at: 2026-08-07T08:02:43Z
updated_at: 2026-08-07T08:19:24Z
---

`packages/docker/wordpress-alpine` is published under a different tag scheme than the
other `packages/docker/*` sub-projects, and the tag is computed inline in three
places instead of by the shared composite action.

| package                            | tag today                                              | tag after                                                       |
| ---------------------------------- | ------------------------------------------------------ | --------------------------------------------------------------- |
| rector-php / ecs-php / dennis-i18n | `:2026-08-05-11-10-25` (shared action)                 | unchanged                                                       |
| wordpress-alpine                   | `:<git-tree-hash>-php8.4` (inline `git rev-parse`, 3x) | `:2026-08-05-11-10-25-php8.4` (shared action)                   |
| devcontainer                       | timestamp in the repository _name_                     | unchanged                                                       |
| potrans                            | not published                                          | unchanged - CI never invokes it, only `pnpm lint-fix:i18n` does |

Repository layout stays as it is: one GHCR repository per package, many
content-derived tags. That already isolates branches from each other (verified: no
cross-branch overwrite path exists today) - this change is about having _one_ tagging
scheme in the repo rather than two, and _one_ implementation of it rather than four.

## Design

- new `.github/shared/scripts/docker-subproject-image-tag.sh <path>` is the single
  implementation: `git log -1 --format=%cd --date=format:%Y-%m-%d-%H-%M-%S -- <path>`,
  erroring out on an empty result (a path with no commit history, or a shallow clone,
  would otherwise silently yield an image ref ending in `:`)
- `docker-subproject-image-name` composite action delegates to that script
- wordpress-alpine tag = `<date-tag>-php<php-version>`; the `-php` suffix stays because
  one commit publishes several PHP variants
- `build-wordpress-alpine-image.yaml` needs `fetch-depth: 0` - the date tag reads
  `git log` history, which the default depth-1 checkout does not have (the tree hash
  it uses today did not need it)

Behaviour change to be aware of: the tag becomes history-derived rather than
content-derived. Reverting a Dockerfile change back to a previously published state
now yields a _new_ tag and one extra build, instead of re-hitting the old image.
That is exactly how rector-php/ecs-php/dennis-i18n already behave.

## Todo

- [x] add `.github/shared/scripts/docker-subproject-image-tag.sh` with the empty-tag guard
- [x] make `docker-subproject-image-name/action.yaml` delegate to it
- [x] `build-wordpress-alpine-image.yaml`: use the action, add `fetch-depth: 0`, drop the inline `git rev-parse`
- [x] `integration.yaml`: replace the 30-line inline wordpress-alpine pull with a call to the shared pull script
- [x] `scripts/test.sh`: use the shared script for the `PHP_VERSION_OVERRIDE` pull path
- [x] move the ghcr retry loop into the shared pull script (was duplicated inline)\n- [x] devcontainer: timestamp moves from the repository name into the tag\n- [x] fix pre-release.yml checking out at depth 1 (empty devcontainer tag -> silent full rebuild every run)\n- [x] update the comments/docs that describe the tag as a content hash
- [x] changeset - deliberately none: nothing inside a published package changed, only how CI names and pulls the images, so a version bump would be changelog noise (confirmed with the user)

## Summary of Changes

Every image published from this repository now uses one `<image>:<tag>` mechanism.

**Tag** - `.github/shared/scripts/docker-subproject-image-tag.sh` is the single
implementation, called by the `docker-subproject-image-name` composite action from the
workflows and directly from `scripts/test.sh`. It errors out on an empty result instead
of yielding a ref ending in `:`.

| image                            | before                                              | after                                   |
| -------------------------------- | --------------------------------------------------- | --------------------------------------- |
| devcontainer                     | `.../ionos-wordpress-<ts>-devcontainer` (`:latest`) | `.../ionos-wordpress-devcontainer:<ts>` |
| wordpress-alpine                 | `.../wordpress-alpine-dev:<tree-hash>-php8.4`       | `.../wordpress-alpine-dev:<ts>-php8.4`  |
| rector-php, ecs-php, dennis-i18n | `.../rector-php:<ts>`                               | unchanged                               |

**Pull/push** - the ~30-line inline wordpress-alpine pull in `integration.yaml` is gone,
replaced by one call to `docker-subproject-image-pull.sh`, which absorbed the ghcr retry
loop so every image shares it. `scripts/test.sh` keeps its own pull path on purpose: it
fetches a non-default PHP variant and must not clobber the default variant's local
`:latest` tag - but it resolves the tag through the shared script.

**Two bugs found on the way**

- `pre-release.yml` checked out at depth 1, so its devcontainer tag came out empty and
  `cacheFrom` never hit - a full devcontainer rebuild on every release run. Now
  `fetch-depth: 0`.
- `devcontainers/ci` appends `:latest` to an `imageName` that already carries a tag, so
  the devcontainer action exposes repository and tag separately and the workflow passes
  `imageName` + `imageTag`.

**Verification** - the tag script was run against the real repo (`.devcontainer` ->
`2026-08-07-09-53-53`, `rector-php` -> `2026-08-03-13-39-57`, bogus path -> exit 1);
all touched YAML parses and all touched shell passes `bash -n`. The workflow wiring
itself was not run in CI.

**Expected one-off cost** - every image gets a new tag, so the first run after merge
rebuilds the devcontainer, wordpress-alpine and the tool images once. Previously
published images stay in the registry under their old names; `pnpm purge-registry`
(see [[2qpm]]) clears them.
