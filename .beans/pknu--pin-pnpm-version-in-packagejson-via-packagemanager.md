---
# pknu
title: Pin pnpm version in package.json via packageManager field
status: completed
type: task
priority: normal
created_at: 2026-09-07T09:26:01Z
updated_at: 2026-09-07T09:30:51Z
---

Make package.json the authoritative pnpm version pin so the version is self-enforcing outside the devcontainer too.

- [x] Add "packageManager": "pnpm@12.3.4" to package.json
- [x] Drop the Dockerfile-grep step in .github/workflows/release.yaml (action-setup@v4 reads packageManager)
- [x] Add a drift assertion to lint:pnpm so Dockerfile ENV PNPM_VERSION and package.json cannot diverge

## Summary of Changes

- `package.json`: added `"packageManager": "pnpm@12.3.4"`. Verified the effect - the host's
  pnpm 11.22.0 now self-switches to 12.3.4 inside the repo, so the pin is enforced outside
  the devcontainer too (pnpm's `managePackageManagerVersions` is on by default).
- `.github/workflows/release.yaml`: dropped the "Read pnpm version" step that grepped
  `ENV PNPM_VERSION` out of the Dockerfile. `pnpm/action-setup@v4` is now called with no
  `version` input so it reads `packageManager` from package.json.
- `scripts/lint.sh` (`ionos.wordpress.pnpm`, run by `pnpm lint` / `lint:pnpm`): added a drift
  assertion comparing package.json's pin to the Dockerfile's `ENV PNPM_VERSION`. Needed because
  package.json silently *wins* at runtime, which would otherwise make the Dockerfile ENV
  cosmetic on drift. Uses the repo's `filename:line` error notation for vscode task jumps.

### Side effect worth knowing

Adding the field changes the lockfile: pnpm 12 records the pin as `packageManagerDependencies`
and locks pnpm's own per-platform binaries (`@pnpm/exe.*`) with integrity hashes, as a second
leading YAML document in `pnpm-lock.yaml` (+101 lines). `pnpm install --frozen-lockfile` FAILS
until the lockfile is regenerated, so `pnpm-lock.yaml` must be committed together with
package.json or CI breaks. Bonus: the pnpm binary is now supply-chain verified.

### Verification

- `pnpm install --frozen-lockfile` passes; lockfile is stable across repeated installs.
- Drift guard negative-tested both ways: a mismatched version and a removed field each fail
  with an actionable message and non-zero exit.
- `pnpm lint` and `pnpm build` both pass in full.

### Note

`managePackageManagerVersions: false` in pnpm-workspace.yaml would keep the field as
documentation only (no auto-switch, no network fetch) if the self-switch is ever unwanted -
e.g. on a runner without registry access.
