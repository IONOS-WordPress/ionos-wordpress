---
# ode2
title: Add missing file header to playwright/exec-test-cli.js
status: todo
type: task
priority: low
created_at: 2026-08-17T13:40:19Z
updated_at: 2026-08-17T13:40:19Z
parent: qi52
---

New file playwright/exec-test-cli.js has no top-of-file header comment describing its contents, as required by AGENTS.md ("Every PHP / JS / CSS file should contain a header describing its contents").

The file starts directly with 'import { execSync } from 'child_process';'. The only comments present explain the CONTAINER_NAME fallback logic mid-file, not the module's overall purpose.

## Fix

Add a short header comment describing what this module does (drives wp-cli commands inside the ephemeral test container from Playwright specs, replacing the old playwright/wp-env.js).

## Location

playwright/exec-test-cli.js:1
