---
# hgik
title: Upgrade pnpm to latest major version
status: completed
type: task
priority: normal
created_at: 2026-08-17T10:25:11Z
updated_at: 2026-08-17T11:06:05Z
parent: 3pr5
---

Repo is pinned to pnpm 9.15.9; latest is 11.x (two major version bumps). Investigate breaking changes across lockfile format, workspace resolution, and any pnpm CLI flags used in scripts/*.sh and CI workflows before upgrading. No behavior change expected from a developer's perspective once done.

## Research findings (pnpm 9.15.9 -> 11.x)

**Version pinning is centralized** - only `.devcontainer/Dockerfile:71` (`ENV PNPM_VERSION 9.15.9`)
needs to change; `release.yaml` already reads the version from that file dynamically, and no other
workflow/action pins a version independently.

**Node.js**: pnpm 11 requires Node 22+. This repo already runs Node 24.18/24.19
(`.npmrc: use-node-version=24.18.0`, `node --version` -> v24.19.0 locally) - not a blocker.

**Breaking changes that matter here**:

1. **Lifecycle scripts are blocked by default since pnpm 10** (security default). Any dependency
   relying on a postinstall/preinstall script (eg. `@playwright/browser-chromium`'s browser
   download) will silently stop running its install script unless explicitly allowlisted via
   `pnpm.onlyBuiltDependencies` (pnpm 10) / the new `allowBuilds` setting (pnpm 11, replaces
   `onlyBuiltDependencies` and related settings entirely). This repo currently has **no
   onlyBuiltDependencies/allowBuilds configured** - upgrading needs `pnpm approve-builds` run once
   to populate the allowlist, or e2e/build steps will fail confusingly (missing browser binary,
   etc.) with no clear error pointing at the cause.
2. **`.npmrc` restructuring (pnpm 11)**: pnpm-specific settings move out of `.npmrc` into a YAML
   file (`pnpm-workspace.yaml`); `.npmrc` keeps only registry/auth settings. Nearly everything in
   this repo's `.npmrc` is pnpm-specific (`save-exact`, `store-dir`, `script-shell`,
   `link-workspace-packages`, `save-workspace-protocol`, `prefer-workspace-packages`,
   `update-notifier`, `loglevel`, `use-node-version`, `enable-pre-post-scripts`) and needs migrating.
   A `pnpm-v10-to-v11` codemod exists (https://app.codemod.com/registry/pnpm-v10-to-v11) and covers
   most of this mechanically.
3. **`npm_config_*` env vars no longer read** (pnpm 11) - grep found no `npm_config_` usage in
   `scripts/*.sh` or CI workflows, so likely not a hit, but worth a final check during the actual
   upgrade.
4. **Store format changes twice** (JSON-per-package index -> new index in pnpm 10 -> single SQLite
   db in pnpm 11). `.pnpm-store` is committed to `.gitignore` and restored via
   `.github/shared/actions/pnpm-store-cache` keyed on `pnpm-lock.yaml`'s hash - a version bump will
   naturally invalidate/rebuild the cache once, no code change needed there, but first post-upgrade
   CI runs will be slower until the cache warms up.
5. **Lockfile format**: current lockfile is `lockfileVersion: '9.0'`. Confirming the exact target
   lockfile version pnpm 11 writes needs a real `pnpm install` with the new binary - not done here
   since that's the upgrade step itself, not research.
6. **Removed settings**: `managePackageManagerVersions`, `packageManagerStrict`,
   `packageManagerStrictVersion` are gone (replaced by `pmOnFail`) - not currently used in this
   repo's config, so no migration needed there.

**No workspace package (`packages/**/package.json`) defines its own postinstall/preinstall/prepare
script** - the risk is entirely third-party dependency lifecycle scripts, which is exactly what
pnpm 10+'s new default blocks.

## Recommended approach (not yet implemented)

1. Bump `.devcontainer/Dockerfile`'s `PNPM_VERSION` to latest 11.x.
2. Run `pnpm approve-builds` locally to populate `pnpm.onlyBuiltDependencies` (or `allowBuilds` if
   targeting 11.x's new setting directly) with whatever the tool flags as needing scripts.
3. Run the `pnpm-v10-to-v11` codemod (or migrate `.npmrc` by hand) to move pnpm-specific settings
   into `pnpm-workspace.yaml`.
4. Regenerate `pnpm-lock.yaml`, run `pnpm install`, `pnpm build`, `pnpm test:e2e` (specifically to
   catch a silently-skipped Playwright browser download) locally before pushing.
5. Push on a branch and validate full CI (lint, build+test, devcontainer build) before merging -
   same "can't be reproduced in the sandbox" caveat noted in sibling migration phases (see zr06).

## Summary of Changes

- Bumped `.devcontainer/Dockerfile`'s `ENV PNPM_VERSION` from `9.15.9` to `11.22.0` (latest stable;
  `12.0.0-rc.6` exists but is a release candidate, not targeted). `release.yaml` already reads this
  value dynamically, so no other workflow needed a version bump.
- Ran `pnpm approve-builds --all` under pnpm 11, which populated `pnpm-workspace.yaml`'s new
  `allowBuilds` key with the 8 dependencies pnpm 10+ blocks by default: `@parcel/watcher`,
  `@playwright/browser-chromium`, `@swc/core`, `core-js`, `core-js-pure`, `esbuild`,
  `fs-ext-extra-prebuilt`, `unrs-resolver`. Without this, `@playwright/browser-chromium`'s install
  script (which downloads the browser binary used by `pnpm test:e2e`) would have silently stopped
  running.
- Migrated every pnpm-specific `.npmrc` setting into `pnpm-workspace.yaml` as camelCase keys
  (confirmed empirically per-key via `pnpm config get <key>`, since `pnpm.io/settings` doesn't
  enumerate a full mapping table): `save-exact` -> `saveExact`, `enable-pre-post-scripts` ->
  `enablePrePostScripts`, `use-node-version` -> `useNodeVersion`, `update-notifier` ->
  `updateNotifier`, `prefer-workspace-packages` -> `preferWorkspacePackages`, `store-dir` ->
  `storeDir`, `script-shell` -> `scriptShell`, `link-workspace-packages` -> `linkWorkspacePackages`,
  `save-workspace-protocol` -> `saveWorkspaceProtocol`. One exception: `loglevel` stays lowercase
  (not `logLevel`) - the editor's YAML schema flagged `logLevel` as an invalid property, and
  `pnpm.io/settings/cli#loglevel` confirms the correct key is `loglevel`. `.npmrc` is now deleted
  entirely (no auth/registry settings needed, and nothing else in the repo references the file -
  confirmed by removing it and re-running `pnpm config get storeDir` / `pnpm install
--frozen-lockfile`, both unaffected).
- Updated `.github/shared/actions/pnpm-store-cache/action.yaml`'s description, which referenced the
  old `.npmrc` `store-dir` setting - this action's caching strategy would have silently broken
  (falling back to the global store, uncached) if `storeDir` hadn't been migrated correctly, since
  it caches the literal `.pnpm-store` path.

## Verification performed

All done under an actual pnpm 11.22.0 binary (via `corepack install -g pnpm@11.22.0`), not just the
config migration in isolation:

- `pnpm install` (both plain and `--frozen-lockfile`, matching what CI runs): clean, no warnings, no
  `pnpm-lock.yaml` diff (lockfile format unchanged between pnpm 9 and pnpm 11).
- Confirmed the store-dir fix actually works: `pnpm store path` reports `.pnpm-store/v11` (workspace-
  local), not the global `~/.local/share/pnpm/store` it silently fell back to before the
  `pnpm-workspace.yaml` migration.
- `pnpm lint`: fully green (one pre-existing prettier issue on this session's own new bean files,
  unrelated to the upgrade, fixed via `lint-fix:prettier`).
- `pnpm build`: full workspace build succeeded (Rector, plugin packaging, wordpress-alpine Docker
  image build). One incidental `.pot` file timestamp/version regen reverted (unrelated to this bean,
  same class of noise documented in bean 5vrh).
- `pnpm test:php`: 15/15 PHPUnit tests pass.
- `pnpm test:e2e`: 28/28 Playwright tests pass, using the real Chromium binary downloaded via the
  now-approved `@playwright/browser-chromium` install script - directly exercises the exact failure
  mode this bean's research flagged as the top risk.

**Not done** (same caveat as sibling migration phases, eg. zr06): a real GitHub Actions run. This
can't be reproduced in the sandbox - devcontainer build, CI cache warm/cold behavior with the new
store path, and `release.yaml`'s dynamic version pickup should be validated via an actual CI run on
this branch before merging.

## Note for native Linux users (not using the dev container)

Once this is merged into `develop`, anyone running the repo's tooling directly on a native Linux
host (outside the dev container) needs to update their local pnpm install to match the new pinned
version (`.devcontainer/Dockerfile`'s `PNPM_VERSION`, now `11.22.0`):

    curl -fsSL https://get.pnpm.io/install.sh | env PNPM_VERSION=11.22.0 sh -

Without this, a native install running the old pnpm 9.x against the migrated `pnpm-workspace.yaml`/
`allowBuilds` config won't recognize the new settings (they didn't exist in pnpm 9), and dependency
install scripts (eg. Playwright's browser download) will silently run unrestricted again instead of
going through the intended allowlist - not a hard failure, but a behavior drift from what CI and the
dev container now do.
