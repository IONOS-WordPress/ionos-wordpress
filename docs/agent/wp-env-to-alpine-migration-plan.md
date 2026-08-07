# Migration Plan: wp-env → Custom Alpine Containers

Status: implemented. Phases 1-7 landed on `feat/replace-wpenv` (see PR #910); wp-env is
gone and `pnpm start`/`stop`/`test`/`destroy` run against the wordpress-alpine containers.
Phase 8 (opportunistic tooling cleanup) is not done and is tracked separately.

This document is kept as the design record for that migration - it describes the intended
end state and the reasoning behind it, not remaining work.

Goal: replace `@wordpress/env` (wp-env) with custom Alpine-based Docker containers,
prototyped at `/opt/dev/wordpress-docker-image`, while preserving the `pnpm
start`/`stop`/`test`/`destroy` interface developers and CI already use.

## Locked-in decisions

| Area                          | Decision                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Container topology            | Single all-in-one container per stack (Apache+PHP+MariaDB+SSH+xdebug), like the prototype                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| Dev/test isolation            | Separate stacks — persistent **dev** stack, ephemeral **test** stack                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| Dev/test lifecycle            | `pnpm start` only starts the dev container. `pnpm test:*` spins up a fresh test container on demand and **always** tears it down afterward, pass or fail                                                                                                                                                                                                                                                                                                                                                                                                      |
| E2E target                    | Playwright runs against the same ephemeral test-stack container used for PHPUnit                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| Xdebug                        | Always-on (baked in, no toggle)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Mount generation              | Dynamic auto-discovery of `packages/wp-plugin/*`, `wp-theme/*`, `wp-mu-plugin/*`, ported from current `start.sh` logic                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| Prod-build testing            | `TEST_PRODUCTION=true`-equivalent preserved, unchanged, as a boolean dist-vs-source mount switch                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| PHP/WP version                | Default version (PHP 8.4) driven by `.env` — a deliberate bump from current `.wp-env.json`'s `8.3`; a small prebuilt matrix — `{8.4, 8.3}` — is published by the Phase 1 image workflow so the minimum-supported-version test path never pays a local build. Originally `7.4`, revised to `8.3` (AGENTS.md's actual stated minimum) after real CI runs showed `7.4` required workarounds source code was never meant to support (named-argument rewrites, a str_starts_with() polyfill) and broke third-party plugin compatibility (Automattic/wordpress-mcp) |
| CI custom-PHP-version testing | `PHP_VERSION_OVERRIDE=<php-version>`: distinct from `TEST_PRODUCTION`, lets CI (or a developer) run the test container against PHP 8.3 (the project's stated minimum supported version) by pulling its prebuilt tag — no local image build, since this path runs on every PR update and locally, not just occasionally — see Phase 5                                                                                                                                                                                                                          |
| phpMyAdmin                    | Dropped                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| CLI convention                | Keep `pnpm start/stop/test/destroy` interface; rewrite `scripts/*.sh` internals to drive Docker instead of wp-env                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| Image distribution            | Published to a container registry, rebuilt/published by a path-filtered workflow on Dockerfile/entrypoint changes. Registry/repo is configurable, not hardcoded (see below)                                                                                                                                                                                                                                                                                                                                                                                   |
| Registry configuration        | `IMAGE_REGISTRY` (e.g. `ghcr.io`) and `IMAGE_REPOSITORY` (e.g. `ionos-wordpress/wordpress-alpine-dev`) read from `.env`, defaulting to GHCR/`ionos-wordpress` if unset; registry auth credentials (e.g. `IMAGE_REGISTRY_USERNAME`/`IMAGE_REGISTRY_PASSWORD` or a token) read from `.secrets`, never committed, following the existing `scripts/includes/bootstrap.sh` `.env`/`.secrets` loading convention                                                                                                                                                    |
| Bootstrapping                 | Baked into image/entrypoint (composer polyfills, xdebug/APCu config, wp-cli bootstrap, `.vscode/launch.json` generation) instead of a lifecycle script                                                                                                                                                                                                                                                                                                                                                                                                        |
| Custom hook                   | Support a user-supplied `AFTER_START` script via `.env`, executed as `php` user, with `doas` available for root-level actions                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| Rollout                       | Hard cutover — remove wp-env once the new setup passes validation                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |

## Open risks and their resolutions

1. **WordPress core ref format**. `.env`'s `WP_ENV_CORE="WordPress/WordPress#7.0"` is
   parsed by wp-env (`parse-source-string.js`) as a git source: shallow-clone
   `https://github.com/WordPress/WordPress.git`, `git fetch origin 7.0 --tags`,
   `git checkout 7.0` — a full git checkout at a tag/branch in the mirror repo, not a
   release tarball. The prototype's `docker-entrypoint.sh` only does
   `wp core download --version="$WORDPRESS_VERSION"` (release tarball), so
   `owner/repo#ref` values aren't equivalent input today.
   **Resolution**: branch the entrypoint's core-provisioning step on the shape of
   `WORDPRESS_VERSION`: if it matches `^[0-9.]+$`, keep the existing
   `wp core download --version=...` path; if it matches `owner/repo#ref`, shallow-clone
   `https://github.com/<owner>/<repo>.git --depth 1 --branch <ref>` into a temp dir and
   copy its tree (minus `.git`) into `/htdocs`, then continue with the normal
   `wp core install`/db-update flow. Keep this inside the existing shared
   version-keyed core-cache locking logic so repeat starts don't re-clone.
2. **mu-plugins dual-mapping**. Current `.wp-env.json` (generated by `start.sh`'s
   `mu_plugins()`) maps each mu-plugin as a top-level loader `.php` file _and_ its
   subdirectory separately — required because WordPress's mu-plugin auto-loader only
   executes top-level `.php` files in `wp-content/mu-plugins/`. wp-env supports this via
   per-file JSON mappings; Docker bind-mounts operate on whole directories, and the
   prototype's `docker-compose.yml` mounts all of `wp-content/mu-plugins` as one unit
   with no per-package logic.
   **Resolution**: move the "flatten loader + subdir" logic from wp-env's JSON mapping
   format into a host-side prepare step (a `mu-plugins()`-equivalent in the new mount
   generator, run before `docker compose up`, akin to the prototype's
   `prepare-mounts.sh`): for each `packages/wp-mu-plugin/<name>/`, symlink its
   `<name>.php` loader and `<name>/` subdir (if present) into a generated
   `./mnt/compose-<stack>/wp-content/mu-plugins/` directory, then bind-mount that single
   generated directory into the container — container side stays a simple one-directory
   mount, matching the prototype's existing pattern.
3. **Permission workarounds**. The current `chmod -R a+w` (`start.sh`, `destroy.sh`) and
   delete-not-owned-by-user (`start.sh`) hacks, plus the in-container
   `sudo chmod a+w -R` in `wp-env-after-start.sh`, exist because wp-env's containers
   write into bind-mounted host directories as root/a non-host uid with no uid/gid
   mapping, leaving files the host user can't clean up or overwrite on the next run.
   The prototype's Dockerfile creates its `php` user at build time from
   `HOST_UID`/`HOST_GID` build-args (wired from `$(id -u)`/`$(id -g)` in its Taskfile),
   and runs all file operations as that user via `doas -u php`, so container-written
   files come back owned by the actual host user.
   **Resolution**: adopt the `HOST_UID`/`HOST_GID` build-arg pattern as-is; have
   `scripts/start.sh`/the image build step pass `--build-arg HOST_UID=$(id -u)
--build-arg HOST_GID=$(id -g)` automatically so no manual step is needed, and drop
   all four permission-hack blocks once Phase 2 confirms files come back correctly
   owned. Note this requires an image rebuild if the host UID/GID ever changes (e.g.
   switching machines/users) — acceptable since the image is cheap to rebuild locally
   and CI always builds fresh.
4. **Hardcoded DB credentials**. `Dockerfile:94-103` bakes `mariadb-install-db` and
   `GRANT ALL PRIVILEGES ON wordpress.* TO 'wordpress'@'localhost' IDENTIFIED BY
'password'` into a build-time `RUN` step, so the credential ends up in the image's
   filesystem layers rather than only in a script. wp-env hardcodes `root`/`password`
   too (`db-env.js`), as a runtime env var rather than image content — not a perfect
   match, but MariaDB is never bound to a host-reachable port in either setup (only
   reachable from inside the container/compose network), so exposure is limited to
   "someone with the image" rather than "someone on the network."
   **Resolution**: accept this as-is — keep the literal `wordpress`/`password`
   hardcoded exactly like the prototype (Dockerfile `GRANT` step unchanged), with no
   `.env`/`.secrets` configurability. Not worth the added indirection given the actual
   exposure is already near-zero.

## Phase 1 — Base image, adapted from the prototype

**Goal**: an ionos-wordpress-specific Alpine image building on
`/opt/dev/wordpress-docker-image`'s Dockerfile, published to GHCR.

- Fork the Dockerfile into this repo (e.g. `packages/docker/wordpress-alpine/Dockerfile`),
  default `ARG_PHP_VERSION` to PHP 8.4 (a deliberate bump from current
  `.wp-env.json`'s `8.3`). Keep the build-arg, but shrink the _published_ matrix from
  "every version" down to exactly two tags — `8.4` (default) and `8.3` (the project's
  stated minimum supported version, see `PHP_VERSION_OVERRIDE` below) — since the
  override path runs on every PR and locally, not occasionally, so it must never fall
  back to a local build.
- Bake into entrypoint/image (replacing `wp-env-after-start.sh`):
  - `yoast/phpunit-polyfills` composer install
  - xdebug + APCu ini patch (log-level suppression etc.)
  - `.vscode/launch.json` generation logic (needs to run at container-start time since
    it depends on discovered plugin/theme/mu-plugin paths — keep as a generated-on-host
    step, not in-container, since VS Code reads it from the host repo)
  - wp-cli bootstrap: rewrite structure/flush, brand options, admin password,
    plugin/theme activation
- Extend entrypoint for git-ref core installs (risk #1 above).
- Add `AFTER_START` support exactly like the prototype: optional host script path from
  `.env`, bind-mounted, executed as `php` user via the built-in `doas` (which already
  `keepenv`s), giving root access on request without needing a separate hook mechanism.
- Set up the image publish workflow (`.github/workflows/build-wordpress-alpine-image.yaml`):
  triggered on changes to the Dockerfile/entrypoint/related build context, tags by
  content hash, builds/pushes **both** `ARG_PHP_VERSION` variants (`8.4` default,
  `8.3` legacy, per `PHP_VERSION_OVERRIDE` in Phase 5) as `:<hash>-php8.4` and
  `:<hash>-php8.3` to `${IMAGE_REGISTRY}/${IMAGE_REPOSITORY}` (defaulting to GHCR,
  e.g. `ghcr.io/ionos-wordpress/wordpress-alpine-dev`, if the repo vars aren't overridden).
  Registry/repo come from repo-level Actions variables (mirroring the local `.env`
  keys); registry auth uses repo secrets (mirroring the local `.secrets` keys) — never
  hardcode `ghcr.io` or the repo path in the workflow YAML.
- **Exit criteria**: `docker run` each of the two built image variants manually,
  confirm WP installs, wp-cli/Apache/MariaDB/xdebug all work — equivalent to the
  prototype's `task verify`.

## Phase 2 — Dev stack + dynamic mount generation

**Goal**: `pnpm start`/`pnpm stop`/`pnpm destroy` work against the new container
instead of wp-env, with dev-only single persistent stack.

- Rewrite `scripts/start.sh`: drop `.wp-env.json` generation; instead generate a
  `docker-compose.dev.yml` (or an equivalent `docker run -v ...` arg list) by scanning
  `packages/wp-plugin/*`, `packages/wp-theme/*`, `packages/wp-mu-plugin/*` — reusing
  the existing discovery logic, replacing wp-env's `mappings`/`plugins`/`themes` JSON
  keys with bind-mount entries. Preserve the mu-plugin loader+dir dual-mount pattern
  (risk #2).
- Rewrite `scripts/stop.sh` → `docker stop`/`docker compose stop` on the dev container.
- Rewrite `scripts/destroy.sh` → remove dev container + its `mnt/` data (keep the
  shared version-keyed WP-core cache, matching the prototype's `destroy` task
  behavior).
- **Replace `WP_ENV_HOME`**: today it's a single flat dir (`./wp-env-home`) holding
  wp-env's per-version WordPress install _and_ all container state, which is what
  makes the chmod/not-owned-by-user hacks (risk #3) and the `wp-env status --json`
  readiness probe in `start.sh` necessary. Adopt the prototype's split layout instead
  of a single new "home" var: `./mnt/wordpress-core/<version>` (shared, version-keyed
  WP-core cache — survives `destroy`) and `./mnt/<stack>/` (per-stack overlay:
  `wp-content/{plugins,themes,mu-plugins,uploads}`, `wp-config.php`, `.htaccess` — dev
  and each ephemeral test stack get their own, wiped on `destroy`/teardown). Introduce
  a `MNT_HOME` (default `./mnt`) `.env` var as the root of this layout; readiness
  becomes a plain `docker inspect --format '{{.State.Health.Status}}'`/`docker ps`
  check on the named container instead of parsing `wp-env status --json`.
- **Replace `WP_ENV_CORE`**: today it's `"${WP_ENV_CORE:-WordPress/WordPress#7.0}"`
  (`.env:28`), consumed only by `start.sh:49`'s `.wp-env.json` generation
  (`"core": "${WP_ENV_CORE:-latest}"`) and overridable per-developer in
  `.env.local.example` (`WP_ENV_CORE='WordPress/WordPress#6.9.4'`) — it's wp-env's own
  source-string format (release version _or_ `owner/repo#ref` git ref, risk #1). Rename
  to `WORDPRESS_VERSION` to match the prototype's `docker-entrypoint.sh` env var name
  directly (no more `.wp-env.json` indirection), keep the same default value and the
  same `owner/repo#ref`-or-version format, and keep it overridable via `.env.local` —
  the entrypoint's risk #1 branching handles both shapes.
- Update `.env`: add equivalents for the new
  tooling (image tag, ports, `WP_PASSWORD`, optional `AFTER_START` path),
  plus `IMAGE_REGISTRY`/`IMAGE_REPOSITORY` (defaulted to GHCR/`ionos-wordpress` so a
  fresh clone works without edits). Document `IMAGE_REGISTRY_USERNAME`/
  `IMAGE_REGISTRY_PASSWORD` (or token) as `.secrets`-only keys in `.env.dist`/docs —
  never given defaults, never committed. `scripts/start.sh`/`test.sh` run `docker login
"$IMAGE_REGISTRY"` with those credentials before pulling when they're present, and
  skip login for public/anonymous pulls when they're not.
- Drop the `library/bash chmod -R a+w` and not-owned-by-user cleanup hacks (validate
  they're no longer needed per risk #3).
- Replace `scripts/wp-env.sh` with explicit per-purpose scripts/targets against the new
  container (no more generic wp-env passthrough): `logs` (tail container logs), `enter`
  (`docker exec` a shell into the container), `ssh` (SSH into the container, per the
  always-on SSH server from the locked-in container topology), and `cli` (execute
  wp-cli commands).
- **Exit criteria**: `pnpm start` brings up a working dev site at a fixed port with all
  current plugins/themes/mu-plugins mounted and active, matching today's dev
  experience; `pnpm stop`/`pnpm destroy` behave as expected.

## Phase 3 — PHPUnit on an ephemeral test stack

**Goal**: `pnpm test:php` starts a throwaway test container, runs PHPUnit inside it,
and tears it down unconditionally afterward.

- Rewrite the PHPUnit path in `scripts/test.sh`: start a fresh test-stack container
  (own compose project/name so it can't collide with the dev stack), wrap the run in a
  trap/finally so the container is destroyed on success **and** failure (mirroring the
  prototype's `verify` task pattern).
- Replace the `docker cp`-based approach (pushing `phpunit.xml`/`bootstrap.php`/
  `wp-tests-config.php` in, pulling `vendor/` out) with bind-mounting `phpunit/`
  directly into the container, since composer/phpunit deps are now baked into the
  image (per Phase 1) rather than installed post-start.
- Preserve the `--exclude .../mu-plugins/stretch-extra` filtering and per-file
  `--filter` behavior from current `scripts/test.sh`.
- **Exit criteria**: `pnpm test:php` passes against current test suite, and confirms
  the container is gone afterward regardless of pass/fail.

## Phase 4 — Playwright/E2E against the same ephemeral test stack

**Goal**: `pnpm test:e2e` reuses the Phase 3 ephemeral test container.

- Update `playwright.config.js`: baseURL now points at the ephemeral test stack's
  dynamically assigned (or fixed) port instead of wp-env's hardcoded `localhost:8889`.
- Rewrite `playwright/wp-env.js`'s `execTestCLI` to `docker exec` into the new test
  container (single container now, so no more `tests-cli-1` container-name discovery
  via `wp-env status --json`).
- Preserve `global-setup.js` behavior (RequestUtils auth/storage state, theme/plugin
  activation) — should need no changes beyond the base URL/port.
- Ensure `scripts/test.sh`'s pre-e2e admin-password-reset step still works against the
  new container.
- Tie into the same ephemeral start→run→teardown wrapper from Phase 3: a single
  `pnpm test` invocation brings up **one** shared ephemeral test-stack container and
  runs both PHPUnit and Playwright against it, tearing it down once at the end
  (pass or fail), rather than each spinning up its own.
- **Exit criteria**: `pnpm test:e2e` passes against the current Playwright suite;
  container is torn down after.

## Phase 5 — Production-build (`TEST_PRODUCTION`) parity and CI custom-PHP-version testing

**Goal**: preserve the ability to run the full test suite against transpiled `dist/`
output, and add a way to run tests against a non-default PHP version without
reintroducing a maintained multi-version image matrix.

- Port the current `.wp-env.override.json` mapping-rewrite logic into the Phase 2/3
  mount generator: when `TEST_PRODUCTION=true` is set, point generated bind mounts at
  `packages/wp-plugin/<name>/dist/...` instead of source, and rsync `phpunit/` test
  dirs into the dist folders as today. Purely a source-vs-dist mount switch — unrelated
  to PHP version.
- Add `PHP_VERSION_OVERRIDE=<php-version>` as a separate mechanism: the Dockerfile
  keeps its `ARG_PHP_VERSION` build-arg, and the Phase 1 publish workflow builds/pushes
  it as a small prebuilt matrix — `8.4` (default) and `8.3` (the project's stated
  minimum supported version) — rather than a single tag. When `PHP_VERSION_OVERRIDE=8.3` is set, `scripts/test.sh` pulls the
  matching prebuilt tag (`${IMAGE_REGISTRY}/${IMAGE_REPOSITORY}:<hash>-php8.3`) instead
  of building a local image, and runs the ephemeral test stack against it — no build
  step on the hot path, since this runs on every PR update and locally, not
  occasionally. Values outside the prebuilt matrix aren't supported by this mechanism.
- **Exit criteria**: `TEST_PRODUCTION=true pnpm test` passes, matching current CI
  behavior; `PHP_VERSION_OVERRIDE=8.3 pnpm test` runs the suite against the prebuilt
  PHP 8.3 image with no local build step, locally and in CI.

## Phase 6 — CI integration

**Goal**: GitHub Actions pulls the prebuilt GHCR image instead of running wp-env
inside a docker-in-docker devcontainer.

- Update `.github/workflows/integration.yaml`: the `build` job pulls
  `${IMAGE_REGISTRY}/${IMAGE_REPOSITORY}:<hash>-php8.4` (same repo vars/secrets as the
  Phase 1 publish workflow — no hardcoded `ghcr.io` path) and runs `pnpm run build` +
  `TEST_PRODUCTION=true pnpm run test` against it; a separate CI job (or matrix leg)
  sets `PHP_VERSION_OVERRIDE=8.3` to pull the prebuilt `:<hash>-php8.3` tag from the
  same Phase 1/5 matrix and run the suite against it on every PR update, without
  changing what the default `build` job pulls/publishes.
- Keep the docker-in-docker devcontainer feature as-is — out of scope for this
  migration. Only update port labels/exposed ports in `.devcontainer/devcontainer.json`
  to match the new container's ports (drop 9000/9001 phpmyadmin, adjust 8888/8889 if
  renumbered).
- **Exit criteria**: CI green on a branch, full parity with current `integration.yaml`
  results.

## Phase 7 — Cutover

**Goal**: remove wp-env entirely.

- Delete `.wp-env.json`, `.wp-env.override.json` generation code, `@wordpress/env`
  dependency, `scripts/wp-env-after-start.sh`, `scripts/wp-env-after-destroy.sh`,
  `scripts/wp-env.sh`.
- Update docs: `docs/1-setup.md`, `docs/5-test.md`, `docs/agent/e2e-testing.md`, and
  any AGENTS.md/docs/agent references to wp-env.
- Update `scripts/playground.sh` (currently reads `core`/`phpVersion` from
  `.wp-env.json`) to read from the new config source instead.
- Final regression pass: fresh clone → `pnpm install` → `pnpm start` → `pnpm test`
  end-to-end, plus a full CI run.
- **Exit criteria**: no `wp-env` references remain in the repo (grep-clean), CI green,
  docs updated.

## Phase 8 — Other cleanup

**Goal**: opportunistic tooling cleanup, bundled here since Phases 1-7 already touch
most of `scripts/*.sh` and the pnpm toolchain.

- Where rewriting `scripts/*.sh` (Phases 2-4) makes a bash block noticeably shorter or
  more readable by dropping in inline Node.js instead — especially JSON
  reading/writing, which bash/`jq` makes awkward — use `node -e '...'` (or a short
  co-located `.mjs` helper) rather than continuing to shell out to `jq`. Not a wholesale
  rewrite of every script into Node: apply this opportunistically, only where it
  actually shortens or clarifies the logic already being touched by this migration.
- Migrate to the latest pnpm release; while doing so, evaluate replacing
  `@changesets/cli` (currently invoked via `scripts/changeset.sh`, see
  `package.json:15,67`) with pnpm's own built-in changeset/publish workflow, to drop an
  external dependency if pnpm's native support covers the repo's current bump-type/
  package-name/multi-package needs.
- **Exit criteria**: no behavior change — existing `pnpm` scripts and the changeset
  workflow (see [Changeset Workflow](changeset-workflow.md)) continue to work
  identically from a developer's perspective.

---

Each phase should land as its own PR/changeset so it's independently reviewable and
revertible.
