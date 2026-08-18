---
# hegd
title: Fix missing executable bit on wordpress-alpine update-dependencies.sh
status: completed
type: bug
priority: normal
created_at: 2026-08-18T11:20:43Z
updated_at: 2026-08-18T11:20:47Z
---

pnpm update-dependencies --pnpm-opts '--latest' failed with exit 126 / Permission denied because packages/docker/wordpress-alpine/scripts/update-dependencies.sh lacked the executable bit, unlike its sibling scripts.

## Summary of Changes

- Ran `chmod +x packages/docker/wordpress-alpine/scripts/update-dependencies.sh` to match its sibling scripts.
- Verified the fix by running `pnpm update-dependencies --pnpm-opts '--latest'`; the workspace's dependency updates (root package.json, ecs-php, potrans, rector-php, pnpm-lock.yaml, pnpm-workspace.yaml) were applied as a result of running the command to validate the fix.
