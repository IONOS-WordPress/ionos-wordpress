---
# vjbx
title: Base image & registry publishing
status: completed
type: epic
priority: normal
created_at: 2026-08-03T11:07:14Z
updated_at: 2026-08-17T13:21:53Z
parent: ei5p
---

An ionos-wordpress-specific Alpine image forked from /opt/dev/wordpress-docker-image's
Dockerfile, with composer/xdebug/wp-cli bootstrap baked in, published to GHCR as a small
{8.4, 7.4} PHP-version matrix.

See docs/agent/wp-env-to-alpine-migration-plan.md, Phase 1.

## Summary of Changes

Child task mrwg (Phase 1 - base image) completed.
