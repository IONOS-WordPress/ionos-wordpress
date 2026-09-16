---
# e6mc
title: 'Dual-mode CLI tools: native inside devcontainer/CI, docker image outside'
status: completed
type: feature
priority: normal
created_at: 2026-08-07T08:55:40Z
updated_at: 2026-08-07T10:21:59Z
parent: 1qc9
---

Install `ecs-php`, `rector-php`, `potrans` and `dennis-i18n` natively into the devcontainer image,
and dispatch to the existing docker images only when the native tool is absent - so developers who
work outside the devcontainer keep working unchanged.

This is the "merge the images into the devcontainer" idea, but merging their _contents_ rather than
the images themselves. See the parent for why merging the images literally does not work.

## Why not bake the images themselves

The docker-in-docker feature mounts `/var/lib/docker` as a named volume
(`dind-var-lib-docker-<devcontainerId>`, visible in the `docker run` line of any
`devcontainer-shell-run` step), so anything baked into the image at that path is shadowed. And
`dockerd` cannot run during `docker build` anyway - it needs `--privileged`, which buildx does not
grant. The only working route would be baking `docker save` tarballs and `docker load`-ing them on
container start, which doubles the bytes and adds load time to every start.

All four tools are just CLIs, so installing them natively sidesteps all of that: three
`composer install`s and one `pipx install`.

## Decisions taken

All four resolved with the user - no open decisions remain.

1. **All four tools** get dual mode (not a subset).
2. **No CI rot guard.** Once CI takes the native path, nothing in automation exercises the four
   docker images anymore; they become dev-only and unverified. Accepted deliberately - to be
   documented, not mitigated. Outside-devcontainer developers will be the ones who discover
   breakage.
3. **Bump the devcontainer base to PHP 8.4** (`mcr.microsoft.com/devcontainers/php:8.4-bookworm`,
   confirmed to exist) and run all three PHP tools natively on it. Re-pin the `.env` files of
   `ecs-php` (currently 8.5) and `potrans`/`rector-php` (already 8.4) to 8.4 so the docker images
   and the native installs use the identical interpreter - zero drift, one PHP to maintain.
4. **The native install resolves from the tool's tracked `composer.lock`** - `composer install`,
   never `composer update`. Same file the Dockerfile copies, so both paths run identical tool
   versions. This is what makes dual mode safe for a _linter_: otherwise "passes locally, fails in
   CI".
5. **Detection probes for the binary, plus an explicit force-docker escape hatch.**
   `command -v` / the vendor bin path decides, and `IONOS_WP_FORCE_DOCKER=1` forces the docker path
   on demand. The probe behaves identically in CI, in the devcontainer, and on a host that happens
   to have the tool, and degrades to docker automatically. The override matters precisely _because_
   decision 2 means CI no longer verifies the docker path - it is the only way left to reproduce it.
   Rejected: sniffing `$REMOTE_CONTAINERS` / `$CODESPACES` (not reliably set by `devcontainers/ci`,
   so CI would take the wrong branch), and a baked `ENV IONOS_WP_NATIVE_TOOLS=1` (a second
   mechanism that can disagree with reality).
6. **Pin `dennis` exactly** - `dennis==1.3.0` in both the Dockerfile and the native install,
   replacing today's unbounded `dennis>=${DENNIS_VERSION}`. Removes a drift source that exists
   already, independently of this work.

### Why the PHP 8.4 bump is lower-risk than it looks

Checked during the investigation, all three points verified:

- **The devcontainer's PHP is tooling-only.** PHPUnit runs inside the wordpress-alpine test
  container (`scripts/test.sh:241-366`), and the transpilation-target syntax checks shell out to
  `php:<target>-cli` images. So the devcontainer interpreter does **not** define the project's
  supported-PHP floor - bumping it cannot silently move the PHP 8.3 minimum stated in AGENTS.md.
- **8.4 matches the dev runtime.** `packages/docker/wordpress-alpine/.env` already defaults to
  `ARG_PHP_VERSION=8.4`, so this aligns the devcontainer's PHP with the WordPress the developer
  actually runs against - for the first time.
- **No lockfile blocks it.** Max PHP requirements across the three lockfiles: `ecs-php` `>=7.2`
  (`symplify/easy-coding-standard`), `rector-php` `^7.4|^8.0` (`phpstan/phpstan`), `potrans`
  `>=8.4.1` (4x `symfony/cache`). 8.4 satisfies all three; **8.3 would not** - `potrans` is the
  reason the bump is needed at all, and its `.env` pin of 8.4 was already correct.

Residual risk to validate: ECS/PHP-CS-Fixer behaviour and the analysis parser can vary by
interpreter version. Since the docker images get re-pinned to 8.4 in the same change, both paths
move together - but the 8.5 -> 8.4 move for `ecs-php` is a real behaviour change and needs the
lint-output diff below.

## Work items

Roughly in order; the PHP bump lands first because everything else installs on top of it.

- [x] Bump `.devcontainer/Dockerfile` to `mcr.microsoft.com/devcontainers/php:8.4-bookworm`
- [x] Re-pin `packages/docker/ecs-php/.env` from `PHP_VERSION=8.5` to `8.4` so the image matches the
      devcontainer interpreter (`rector-php`/`potrans` are already 8.4)
- [x] Pin `dennis` exactly: `dennis==1.3.0` in `packages/docker/dennis-i18n/Dockerfile`
- [x] Add the native installs to `.devcontainer/Dockerfile` - `composer install` from each tool's
      tracked `composer.lock` (ecs-php, rector-php, potrans) plus `pipx install dennis==1.3.0`
- [x] Add the dispatch helper (binary probe + `IONOS_WP_FORCE_DOCKER=1` override) and wire it into
      the existing call sites: `ionos.wordpress.ecs` (`scripts/lint.sh`), potrans, dennis, rector
      (`scripts/rector-fix-types.sh`) **and `scripts/build.sh`'s per-plugin per-target-PHP rector
      loop, which the original plan missed** - that one is the only rector call site CI actually
      exercises
- [x] Parameterize the container-absolute path hardcoded in the rector config. Turned out to be
      **wider than planned**: also `packages/docker/ecs-php/ecs-config.php` (3x
      `/composer/vendor/wp-coding-standards/...`), and both rector configs derived their skip lists
      from `__DIR__`, which is the synthetic `/project` root under docker but the real
      `packages/docker/rector-php` natively - replaced with fnmatch patterns
- [x] **Diff native vs docker lint output** - identical on a clean tree and on a deliberately
      violating fixture (after path normalisation). Proven meaningful by a negative control: with a
      wrong `COMPOSER_HOME` the native run dies with `Class "WordPressCS\WordPress\Sniff" not
found`, so a passing run really does mean the wpcs standards loaded
- [x] Verify `pnpm build` still transpiles identically - byte-identical rector output both ways on a
      fixture, and the `vendor/` skip is still honoured in both modes (proves the fnmatch change)
- [x] Strip the now-unused steps from `.github/workflows/integration.yaml`: the `ecs-php`,
      `dennis-i18n` and `rector-php` pulls/pushes and their `docker-subproject-image-name` steps
- [x] Document the docker path as dev-only and unverified by CI, and document
      `IONOS_WP_FORCE_DOCKER=1` as the way to exercise it (`.devcontainer/README.md`)
- [x] ~~Changeset~~ - **not applicable**. `docs/agent/changeset-workflow.md` explicitly excludes
      CI/CD changes and internal tooling from changesets, and no published package's behaviour
      changes (the transpiled plugin output was verified byte-identical). The work item was wrong.

## Verified during investigation

- rector's runtime PHP is decoupled from its transpilation target - the target comes from
  `withDowngradeSets(php74: true)` / `withPhpVersion(PhpVersion::PHP_83)` in the config, not the
  interpreter. Native rector is safe on this axis.
- No `vendor/` directory is tracked for any of the four tools (`git ls-files` -> 0); the ones
  present locally are untracked build artifacts. Every native install is a real `composer install`.
- `mcr.microsoft.com/devcontainers/php:8.4-bookworm` exists in the registry (8.5 does too, if the
  bump ever needs to go further).
- CI never invokes `potrans` - only `pnpm run lint-fix:i18n` (deepl auto-translation) does, and that
  is never run in CI. So the `potrans`-driven PHP 8.4 requirement buys developer parity, not CI
  speed.

## Summary of Changes

All four tools now run natively inside the dev container (and therefore in CI), falling back to
their docker images everywhere else. Dispatch lives in `scripts/includes/_native-tools.sh`, sourced
from `_bootstrap.sh`; it probes for the executable and honours `IONOS_WP_FORCE_DOCKER=1`.

Verified: ECS/rector/php-cs-fixer/phpcs versions byte-identical across both paths, lint output
identical, rector transpilation byte-identical, `vendor/` skip still honoured, and `pnpm lint`
green both on the host (docker path) and inside the image with docker entirely absent (native path).

### Four things the plan did not anticipate

1. **A fifth rector call site.** `scripts/build.sh`'s per-plugin, per-target-PHP loop - the only
   rector invocation CI runs. The plan listed only `scripts/rector-fix-types.sh`.
2. **Config files carried more container-absolute state than the one `require_once`.**
   `ecs-config.php` hardcoded `/composer/vendor/...` three times, and both rector configs built
   their skip lists from `__DIR__`, which silently changes meaning between the two modes. Left
   unfixed, rector would have stopped skipping bundled `vendor/` directories in native mode.
3. **The dev container image tag would have gone stale.** It derived from `.devcontainer` alone, so
   a tool `composer.lock` bump would not have invalidated it - CI would have kept running the
   previous tool versions while the docker images got the new ones, breaking the "identical
   versions" guarantee exactly where nobody would look. `_docker-subproject-image-tag.sh` now takes
   several paths and `devcontainer-image-name` feeds it the four tool directories.
4. **`ecs-php`'s `PHP_VERSION=8.5` was not arbitrary** (the investigation had assumed it was). It
   matched Alpine 3.24's _default_ php, which is what made Alpine's `composer` package work: that
   package declares its extension dependencies against the default php, and hijacks `/usr/bin/php`.
   Moving the image to 8.4 required the same `ln -sf` + composer-wrapper `sed` treatment
   `wordpress-alpine` already documents, plus listing composer's six extensions
   (`phar/curl/iconv/mbstring/openssl/zip`) explicitly for the pinned php.

Also found by testing rather than by review: running the image's tool smoke test as `root` left
`/tmp/rector_cached_files` root-owned, so the first invocation as `vscode` failed with a permission
error. The probes now run after the `USER` switch.

### Incidental cleanup caused by this change

- deleted `.github/shared/scripts/_docker-subproject-image-push.sh` - nothing referenced it any
  more (`wordpress-alpine`, the only remaining published sub-project image, uses
  `docker/build-push-action`)
- dropped the now-unused `DOCKER_SUBPROJECT_IMAGE_REPOSITORY_PREFIX` env from `integration.yaml`
- corrected the stale "one per packages/docker/*" comment in `scripts/purge-registry.sh`. The tool
  images' existing registry packages are deliberately still treated as expected, so the script
  leaves them alone rather than proposing deletion - purging them is a separate decision.

### Deliberately not done

- **No CI rot guard** (decision 2). The four docker images are now exercised by nobody but
  outside-dev-container developers.
- **The tool images' ghcr packages are now frozen**, not deleted. Worth a follow-up decision.

## Post-implementation verification (real dev container, not a fixture)

The fixture-level checks above left one real gap: the rector call site in `scripts/build.sh` - the
only one CI runs - had never been exercised _through build.sh itself_, only standalone. A bare
`docker run` of the image cannot close that, because `build.sh` also shells out to dockerized wp-cli
for i18n and therefore needs the docker-in-docker feature. Closed by driving the actual dev
container via the devcontainer CLI, which is exactly what CI does:

- `devcontainer up` succeeds on the PHP 8.4 base with both features (docker-in-docker, claude-code)
  layered on top of the native tool installs - the feature layers were previously untested.
- `pnpm build --filter ./packages/wp-plugin/ionos-essentials` inside it: native rector, "25 files
  have been changed by Rector", 844K php7.4 zip - identical to the host/docker run.
- **All 192 transpiled `.php` files are byte-identical between the docker-rector and native-rector
  builds** (same sha256 over the concatenated php7.4 tree). This is the strongest evidence in the
  whole bean: the path translation, the `__DIR__` -> fnmatch skip change and the `$COMPOSER_HOME`
  stub resolution all preserve output exactly.
- `pnpm lint` green inside the dev container _with docker available_, confirming the native tools win
  on merit rather than because docker happened to be missing (the earlier test had docker absent, so
  it could have passed for the wrong reason).

### potrans verified too (real deepl calls)

Run with the `DEEPL_API_KEY` from `./.secrets`, via `pnpm lint-fix:i18n`, both ways. All 25 tracked
`.po` files were restored with `git checkout` afterwards; the working tree is back at its baseline
hashes. There were no untranslated strings, so deepl was asked to translate nothing of substance and
quota use was negligible.

Both paths behave **identically**, including failing identically:

```
ERROR: Bad request, message: Tag handling parsing failed, please check input.
'undefined entity: line 1, column 41' (at Line: 1, Column: 34)
```

on `stretch-extra-es_ES.po`, differing only in the vendor path in the stack line
(`/composer/vendor/...` under docker vs `/opt/ionos-wordpress/tools/potrans/vendor/...` natively).
That is the parity result this bean needed - and the failure is **pre-existing, not caused by this
work**: `packages/docker/potrans` is untouched by this change (no Dockerfile and no `.env` edit, it
was already pinned to PHP 8.4), so the docker image is bit-for-bit what `develop` builds.

So `pnpm lint-fix:i18n` is currently broken on `develop` independently of this bean. Root cause not
identified - the obvious hypotheses (HTML entities, the `<br />` markup in the long BeyondSEO msgid)
do not hold up: that file contains no `&` at all. Worth its own bean.

### Still not verified

- **CI itself has not run** - everything above is local. The first push is the real test.
