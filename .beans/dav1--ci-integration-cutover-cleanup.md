---
# dav1
title: CI integration, cutover & cleanup
status: todo
type: epic
created_at: 2026-08-03T11:07:14Z
updated_at: 2026-08-03T11:07:14Z
parent: zmd6
blocked_by:
    - b55y
---

CI pulls the prebuilt GHCR image instead of running wp-env in a docker-in-docker devcontainer,
wp-env is removed entirely, and opportunistic tooling cleanup (Node-over-jq, pnpm/changesets)
lands alongside.

See docs/agent/wp-env-to-alpine-migration-plan.md, Phases 6-8.
