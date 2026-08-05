---
# 3pr5
title: Phase 8 — Other cleanup
status: todo
type: task
priority: normal
created_at: 2026-08-03T10:59:47Z
updated_at: 2026-08-03T11:07:20Z
parent: dav1
blocked_by:
  - zr06
---

Goal: opportunistic tooling cleanup, bundled here since Phases 1-7 already touch most of
scripts/*.sh and the pnpm toolchain.

## Tasks

- [ ] Where rewriting scripts/*.sh (Phases 2-4) makes a bash block noticeably shorter or more
      readable by dropping in inline Node.js instead — especially JSON reading/writing, which
      bash/jq makes awkward — use `node -e '...'` (or a short co-located .mjs helper) rather than
      continuing to shell out to jq. Not a wholesale rewrite of every script into Node: apply
      this opportunistically, only where it actually shortens or clarifies the logic already
      being touched by this migration
- [ ] Migrate to the latest pnpm release; while doing so, evaluate replacing @changesets/cli
      (currently invoked via scripts/changeset.sh, see package.json:15,67) with pnpm's own
      built-in changeset/publish workflow, to drop an external dependency if pnpm's native
      support covers the repo's current bump-type/package-name/multi-package needs

## Exit criteria

No behavior change — existing pnpm scripts and the changeset workflow continue to work
identically from a developer's perspective.

See docs/agent/wp-env-to-alpine-migration-plan.md for full context.
