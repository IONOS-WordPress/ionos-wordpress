---
# 2qpm
title: Add 'pnpm purge-registry' to delete published ghcr container packages
status: completed
type: feature
created_at: 2026-08-07T08:17:47Z
updated_at: 2026-08-07T08:17:47Z
---

`pnpm purge-registry` deletes the container packages this repository publishes to
ghcr.io - the dev container image plus one per `packages/docker/*` workspace package -
including every version they hold.

Whole packages, not versions: the legacy dev container packages carried their timestamp
in the package _name_ (one package per change), and GHCR version-pruning cannot reach
those. See [[a7r9]], which moves that timestamp into the tag so the problem stops
growing.

## Design

- target set is derived from this repository, never "everything the org owns":
  `<repo>-devcontainer`, the legacy `<repo>-<timestamp>-devcontainer` name pattern,
  `IMAGE_REPOSITORY`'s basename (wordpress-alpine publishes as `wordpress-alpine-dev`),
  and one entry per `packages/docker/*` directory. Everything else is reported as `skip`
- dry run is the default; `--yes` is required to delete. Irreversible, so the safe
  outcome had to be the one you get by typing the short command
- owner resolved from `package.json`'s `repository.url` rather than hardcoded, so a fork
  purges its own packages; org and user accounts use different endpoints
- auth reuses `scripts/gh-cli.sh` for the "is gh installed" diagnostics, but checks
  `GH_TOKEN` itself first - `gh-cli.sh` exits 0 when the token is missing, which a
  caller cannot distinguish from success

## Summary of Changes

- `scripts/purge-registry.sh` (new), wired up as `pnpm purge-registry`
- documented under `# purge-registry` in `docs/3-tools.md`, including the classic-PAT
  scope requirement (`read:packages` + `delete:packages`)

Verified against a stubbed GitHub API: matches both legacy timestamped dev container
packages, skips an unrelated org package and a `...-devcontainer-lookalike` near-miss,
and the dry run deletes nothing. Not verified against the live registry - no token with
`read:packages` was available in the session.

Note: `potrans` is in the target set because it is a `packages/docker/*` package,
although nothing publishes it today. Harmless, and correct if that ever changes.
