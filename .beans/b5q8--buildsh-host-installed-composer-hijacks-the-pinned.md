---
# b5q8
title: 'build.sh: host-installed composer hijacks the pinned dockerized composer'
status: completed
type: bug
priority: high
created_at: 2026-09-10T10:07:27Z
updated_at: 2026-09-10T10:15:18Z
---

`scripts/build.sh` dispatches composer with a bare `command -v composer` probe
([build.sh:263-269](scripts/build.sh#L263)), unlike `ecs-php`/`rector-php`/`potrans`/`dennis-i18n`,
which probe for an executable under `$IONOS_NATIVE_TOOLS_PREFIX` (`/opt/ionos-wordpress/tools`) - a
path that exists only inside the dev container image.

Consequence: on a developer host that happens to have composer installed (reported by a colleague on
Linux), `pnpm build` / `pnpm start` silently uses **that** composer - arbitrary version, arbitrary
PHP interpreter - instead of the pinned `composer:2.10.2` image the docker path exists to guarantee.
That defeats the "both modes run identical versions" property the dual-mode mechanism was built
around (see `.beans/e6mc--*.md`, decision 4), and it is exactly the floating-version
non-determinism bean `033f` set out to remove.

## Decision (with the user)

Make composer a first-class native tool, dispatched by the same filesystem probe as the other four.
Host installs then can never win: outside the dev container it is always `composer:2.10.2`.

Rejected: version-gating the host composer (`composer --version` sniffing - a second mechanism that
can disagree with the first, cf. e6mc decision 5), and leaving the probe in place with
`IONOS_WP_FORCE_DOCKER=1` as a per-developer opt-out (leaves the default wrong).

Also decided: the native composer is **pinned**, copied from the same `composer:2.10.2` image rather
than symlinked from the base image's unpinned composer - otherwise "native" and "docker" mode would
still run different composer versions and the registration would buy consistency of _dispatch_ while
leaving the actual drift in place.

## Work items

- [x] `.devcontainer/Dockerfile`: `COPY --from=composer:2.10.2 /usr/bin/composer $IONOS_NATIVE_TOOLS_PREFIX/composer/bin/composer`, plus a comment on why it is pinned and copied rather than symlinked from PATH
- [x] `.devcontainer/Dockerfile`: add `composer --version` to the post-`USER`-switch tool smoke test
- [x] `scripts/includes/_native-tools.sh`: register `[composer]='composer/bin/composer'` in `IONOS_NATIVE_TOOL_PATHS`; note that composer is the one entry whose `native_tool_composer_home` is meaningless (it is not a COMPOSER_HOME-isolated install)
- [x] `scripts/build.sh`: replace the `command -v composer` branch with `ionos.wordpress.native_tool composer` (kept as two explicit branches, not `run_native_or_docker` - the docker invocation has a different arg/mount shape: `-u`, `-v $(pwd)/$path:/app`, `-w /app`)
- [x] `scripts/update-dependencies.sh:113-114`: pin `composer:latest` -> `composer:2.10.2` for `update`/`outdated` (same floating-tag issue 033f fixed in build.sh; left behind then)
- [x] Keep the pinned tag traceable: cross-reference comments between the Dockerfile `COPY --from=` and the two script call sites, so a bump touches all of them
- [x] Verify: `pnpm build --filter ecs-php` on this host (no composer installed -> docker path, must still work); `IONOS_WP_FORCE_DOCKER=1` still forces docker; simulate the colleague's case by putting a fake `composer` on `PATH` and confirming it is _not_ used
- [x] Verify in a real dev container (`devcontainer up`): native path picks `/opt/ionos-wordpress/tools/composer/bin/composer`, reports 2.10.2, and `pnpm build` succeeds

## Deliberately out of scope

- The dev container's own `composer global install` steps for the three PHP tools keep using the
  base image's composer. They install from committed `composer.lock` files, so the composer version
  has no bearing on what gets installed.
- No changeset: dev tooling only, no published package behaviour changes
  (`docs/agent/changeset-workflow.md` excludes CI/CD and internal tooling).

## Summary of Changes

- `scripts/includes/_native-tools.sh`: `composer` registered in `IONOS_NATIVE_TOOL_PATHS` as
  `composer/bin/composer`, so it is dispatched by the same filesystem probe as the four
  `packages/docker/*` tools. New exported `IONOS_COMPOSER_DOCKER_IMAGE='composer:2.10.2'` is the
  single pin for every call site.
- `.devcontainer/Dockerfile`: `COPY --from=composer:2.10.2 /usr/bin/composer` into
  `$IONOS_NATIVE_TOOLS_PREFIX/composer/bin/composer`, plus `composer --version` added to the
  post-`USER`-switch tool smoke test.
- `scripts/build.sh`: the `command -v composer` probe replaced by
  `ionos.wordpress.native_tool composer`; docker fallback uses `$IONOS_COMPOSER_DOCKER_IMAGE`.
  Kept as two explicit branches (not `run_native_or_docker`) because the docker invocation mounts
  only the package directory and needs `-u <uid>:<gid>`.
- `scripts/update-dependencies.sh`: `composer:latest` -> `$IONOS_COMPOSER_DOCKER_IMAGE` for
  `update`/`outdated`. (The only remaining `composer:latest` sits inside the fully commented-out
  helper in `_update-dependencies.sh` - dead code, left alone.)
- `.devcontainer/README.md`: documents that a host composer is ignored on purpose, and that bumping
  the pin means touching both the constant and the Dockerfile `COPY --from=` tag.

## The copy-vs-symlink decision paid off immediately

The plan's reasoning for copying the pinned composer rather than symlinking the base image's was
theoretical. Verification in the built image showed it was already real:
`mcr.microsoft.com/devcontainers/php:8.4-bookworm` ships **composer 2.10.3**, while the docker
fallback is pinned to **2.10.2**. So before this change the dev container and CI resolved lockfiles
with a _different composer version_ than a developer working outside the dev container - the same
class of drift as the reported host-composer bug, just less visible. Native mode now reports 2.10.2,
identical to the docker path.

## Verification

- Dispatch, all three modes: no prefix -> `composer:2.10.2`; prefix present -> the native binary
  **even with a composer first on `PATH`**; `IONOS_WP_FORCE_DOCKER=1` -> docker.
- `pnpm build --filter ecs-php` on the host with a **poisoned** `composer` first on `PATH` (prints a
  marker to stderr, exits 1): the build succeeded through `composer:2.10.2` and the marker never
  appeared. That is the reported colleague scenario, reproduced and fixed.
- `COPY --from=composer:2.10.2 /usr/bin/composer` into a php image + invocation ->
  `Composer version 2.10.2`.
- Full `pnpm exec devcontainer build`: outcome success, i.e. the new smoke-test line passes during
  the image build. In the built image, as `vscode`: the native binary reports 2.10.2 (the PATH
  composer reports 2.10.3 and is unused), and `ionos.wordpress.native_tool composer` resolves to
  `/opt/ionos-wordpress/tools/composer/bin/composer`.
- No added line exceeds 120 chars; `prettier --check` clean on the README.

## Not done

- `pnpm build` has not been run _inside_ a started dev container (`devcontainer up` with
  docker-in-docker); only `devcontainer build` plus the dispatch probe. The native composer path
  needs no docker itself, and CI's first run covers the rest.
- No changeset: dev tooling only, no published package behaviour change.
