---
# dav1
title: CI integration, cutover & cleanup
status: completed
type: epic
priority: normal
created_at: 2026-08-03T11:07:14Z
updated_at: 2026-08-17T13:21:52Z
parent: zmd6
blocked_by:
  - b55y
---

CI pulls the prebuilt GHCR image instead of running wp-env in a docker-in-docker devcontainer,
wp-env is removed entirely, and opportunistic tooling cleanup (Node-over-jq, pnpm/changesets)
lands alongside.

See docs/agent/wp-env-to-alpine-migration-plan.md, Phases 6-8.

## Summary of Changes

All child phases resolved: 7wdg (Phase 6 CI integration), zr06 (Phase 7 cutover), 3pr5 (Phase 8 cleanup) all completed.
