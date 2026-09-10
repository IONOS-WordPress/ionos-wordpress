---
# 47l4
title: Assert the version pins duplicated in .devcontainer/Dockerfile still agree
status: completed
type: task
priority: normal
created_at: 2026-09-10T10:26:12Z
updated_at: 2026-09-10T10:32:41Z
---

Several version pins exist twice on purpose: once where `scripts/*` or a `packages/docker/*` image
reads them, and once hardcoded in `.devcontainer/Dockerfile`. The duplication is unavoidable - the
dev container image is built by the devcontainer CLI / VS Code / `devcontainers/ci`, none of which
source the repo's `.env` files or a shell include, and `devcontainer.json` `build.args` can only
interpolate `${localEnv:…}` (the developer's shell), not a file in the repo. So a shared variable
cannot reach that Dockerfile.

What _is_ missing is an assertion that the pairs still agree. Nothing fails today if one half is
bumped and the other is forgotten, and the failure mode is silent: both paths keep working, they
just stop being the same tool. Found live while fixing [[b5q8]] - the dev container's base image
ships composer **2.10.3** while the docker fallback is pinned to **2.10.2**, so the dev container
and CI had been resolving lockfiles with a different composer than an outside developer.

## Proposed check

A new linter function in `scripts/lint.sh`, wired into the existing `--use` dispatch (`all|pins`),
report-only (there is nothing sensible for `--fix` to do - a pin bump is a decision). Pure
grep/sed, no docker and no network, so it costs milliseconds and runs everywhere `pnpm lint` runs,
including CI.

Drive it from a table of pairs so adding a future pin is one line, and make every failure name both
locations and both values.

### Pairs to assert

1. **composer** - `$IONOS_COMPOSER_DOCKER_IMAGE` (`scripts/includes/_native-tools.sh`) vs. the tag
   in `.devcontainer/Dockerfile`'s `COPY --from=composer:<tag> /usr/bin/composer`. These must match
   or the native and dockerized composer stop being the same binary (the [[b5q8]] guarantee).
2. **dennis** - `DENNIS_VERSION` in `packages/docker/dennis-i18n/.env` vs. the `ARG DENNIS_VERSION=`
   default in `.devcontainer/Dockerfile`. Already documented as "keep in sync" in a comment; this
   turns the comment into a test.
3. **PHP interpreter** - `PHP_VERSION` in each of `packages/docker/{ecs-php,rector-php,potrans}/.env`
   vs. the tag in `.devcontainer/Dockerfile`'s
   `FROM mcr.microsoft.com/devcontainers/php:<version>-bookworm`. `.beans/e6mc--*.md` decision 3
   requires all four to be the identical interpreter version (currently 8.4); nothing enforces it.

## Work items

- [x] Add `ionos.wordpress.version_pins()` to `scripts/lint.sh` with the three pairs above, driven
      by a declarative table
- [x] Wire it into the `USE`/`--use` dispatch (`all|pins`) and the `###help-message` block
- [x] Verify by deliberately breaking each of the three pins one at a time (must go red, with a
      message naming both files and both values), then restoring
- [x] Verify `pnpm lint` stays green on a clean tree and that the check adds no measurable runtime
- [x] Replace the "keep in sync by hand" comments at the three sites with a pointer to the check, so
      a reader knows the sync is enforced rather than hoped for

## Rejected alternative

Moving the composer pin into the repository's `.env`. It reaches `scripts/build.sh` and
`scripts/update-dependencies.sh` (both source `.env` via `ionos.wordpress.load_env`) but not
`.devcontainer/Dockerfile`, so the hand-sync would remain while the two halves drifted further
apart - and `.env.local` (git-ignored, sourced after `.env`) would additionally turn the pin into a
per-developer override, silently changing dependency resolution: exactly the class of bug [[b5q8]]
removed.

Follow-up of b5q8.

## Summary of Changes

`scripts/lint.sh` gained two functions and a new `--use pins` linter (part of `all`, so plain
`pnpm lint` runs it):

- `ionos.wordpress.assert_version_pin <what> <file> <regex> <mirror-regex>` compares one pin in its
  own file against its literal in `$IONOS_VERSION_PIN_MIRROR` (`.devcontainer/Dockerfile` - every
  duplicated pin's second home, so it is a constant rather than a per-row column). Both regexes
  capture with `grep -P`'s `\K`, and errors use the `file:line` notation the vscode tasks need.
- `ionos.wordpress.version_pins()` calls it once per pin and is wired into the `USE` dispatch and
  the `###help-message` block.

Pins covered: composer (`$IONOS_COMPOSER_DOCKER_IMAGE` vs `COPY --from=composer:<tag>`), dennis
(`packages/docker/dennis-i18n/.env` vs `ARG DENNIS_VERSION=`), and the PHP interpreter
(`PHP_VERSION` in each of the three tool `.env` files vs the `FROM …/php:<version>-bookworm` tag).

The "keep in sync by hand" comments at all four sites now say that the lint enforces it, instead of
just asking the reader to remember.

## Two things worth knowing

1. **An unmatched pattern is a failure, not a pass.** If a file is restructured so a regex stops
   matching, the check reports "cannot find the <what> version pin" and fails. A pin check that
   silently passes because it no longer finds anything would be worse than no check.
2. **`ionos.wordpress.log_error` takes exactly one message argument** - its `$2` is a stacktrace
   index, and passing a second message fragment produces a mangled "second parameter must be a
   number" error instead of the intended message. Found by testing the failure paths, not by
   reading. Both messages are now composed into a single variable first.

`ionos.wordpress.pnpm()`'s existing pnpm pin check (package.json `packageManager` vs
`ENV PNPM_VERSION`) was deliberately left where it is: same shape, but it reads its value through
node rather than by pattern.

## Verification

- Clean tree: green, and `--use pins` triggers no docker image build (it matches none of the
  `DOCKER_BUILD_FILTER_TOOLS` patterns). Whole linter runs in ~0.15s.
- Each pin broken one at a time - composer tag, `ARG DENNIS_VERSION`, `PHP_VERSION` in
  `rector-php/.env` - fails with both locations, both values and a `file:line` anchor; tree restored
  byte-identically afterwards.
- Renaming `IONOS_COMPOSER_DOCKER_IMAGE` (simulating a refactor) triggers the "matches nothing"
  branch rather than passing.
- `pnpm lint` with every linter except i18n: green (the only prettier complaint was the two new
  bean files themselves - `.beans/*.md` is prettier-covered; both formatted).
- `devcontainer build` re-run after the Dockerfile comment additions: still succeeds.

## Incident during this work

While testing the failure paths I restored files with `git checkout <file>`, which discarded the
**uncommitted** `b5q8` changes in `.devcontainer/Dockerfile` and `scripts/includes/_native-tools.sh`.
Re-applied from the session's own patches and re-verified (native composer registration, the
`COPY --from=` line, the smoke-test probe, the pin constant), and the remaining break-tests used file
backups instead of git. Nothing was lost in the end, but it is the reason the dev container image was
built twice.
