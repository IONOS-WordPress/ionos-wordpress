---
# zfhv
title: Update pnpm to latest version (12.3.4)
status: completed
type: task
priority: normal
created_at: 2026-09-07T07:59:34Z
updated_at: 2026-09-07T08:25:51Z
---

Currently using pnpm 11.22.0 (no packageManager pin found in package.json). Latest pnpm is 12.3.4 - a major version bump. Need to evaluate breaking changes (see https://pnpm.io/migration), update pnpm globally/CI images, and verify pnpm-workspace.yaml settings still apply.

## Summary of Changes

Upgraded pnpm 11.22.0 -> 12.3.4.

- `.devcontainer/Dockerfile`: `ENV PNPM_VERSION` bumped to `12.3.4`. This is the single
  source of truth - `.github/workflows/release.yaml` greps this line for `pnpm/action-setup`,
  so CI follows automatically (verified the grep resolves to `12.3.4`).
- `pnpm-workspace.yaml`: `useNodeVersion` -> `nodeVersion` (key renamed in pnpm 12).
  Verified `pnpm config list` reports `nodeVersion: 24.18.0` under 12.3.4.
  Also de-staled the `.npmrc` header comment ("pnpm 11 only reads" -> "since pnpm 11").

### Verification

- `pnpm install --frozen-lockfile` succeeds under 12.3.4 - lockfile format is compatible,
  no migration or re-resolution needed.
- `pnpm lint` passes (only pre-existing `no-console` warnings; pnpm lockfile lint OK).
- `pnpm build` completes for all workspace packages incl. the wordpress-alpine docker image.
- No unexpected tracked-file changes after the full build.

### Notes

- No `packageManager` field exists in `package.json`, so nothing to pin there.
- The devcontainer image must be rebuilt for developers to pick up the new pnpm.
- No changeset: dev tooling only, no shipped plugin/theme code changed.
