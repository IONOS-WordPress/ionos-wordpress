---
# 3pr5
title: Phase 8 — Other cleanup
status: completed
type: epic
priority: normal
created_at: 2026-08-03T10:59:47Z
updated_at: 2026-08-17T13:21:52Z
parent: dav1
blocked_by:
  - zr06
---

Goal: opportunistic tooling cleanup, bundled here since Phases 1-7 already touch most of
scripts/*.sh and the pnpm toolchain.

## Tasks

Split into child beans (scope was fuzzy enough to warrant separating the jq/Node cleanup, the pnpm
major-version upgrade, and the changesets research into independently-completable units):

- jq → inline Node.js opportunistic cleanup (child bean)
- pnpm major-version upgrade (child bean)
- Evaluate replacing @changesets/cli with pnpm's native workflow (child bean)

## Exit criteria

No behavior change — existing pnpm scripts and the changeset workflow continue to work
identically from a developer's perspective.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.

## Summary of Changes

All three child beans resolved: jv38 (jq cleanup - evaluated, no change warranted), hgik (pnpm major-version upgrade - shipped, verified in CI), hnjy (changesets vs pnpm-native evaluation - recommendation: keep @changesets/cli).
