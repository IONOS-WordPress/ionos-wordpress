---
# nskf
title: test.sh PHP syntax-check gate silently passes when docker run fails
status: completed
type: bug
priority: high
created_at: 2026-08-17T13:37:34Z
updated_at: 2026-08-18T07:56:34Z
parent: qi52
---

The target-PHP-version transpiled-plugin syntax check in scripts/test.sh (~line 348) inverts its exit status with a leading '!':

    ! cat <<EOL | docker run ... php:${TARGET_PHP_VERSION}-cli /bin/bash - | grep -v '^No syntax errors'
    ...
    EOL
    if [[ $? -ne 0 ]]; then exit 1; fi

Under 'pipefail', if 'docker run' fails outright (image unpullable, no network, docker daemon unreachable, bad version tag), it produces no stdout. 'grep -v' then matches zero lines and exits 1 (no match found) - the pipeline's exit status. The leading '!' flips that 1 to 0, so the '$? -ne 0' check never fires: the gate is treated as "no syntax errors" even though php -l never ran.

## Reproduction

Run 'pnpm build'/'pnpm test' with the php:<version>-cli image unpullable (offline, bad tag, registry outage) - the target-PHP-version compatibility gate passes silently instead of failing the build.

## Fix

Don't rely on grep's match/no-match exit code as a proxy for "syntax ok". Check docker run's own exit code explicitly, and treat "no output at all" as a hard failure rather than success.

## Location

scripts/test.sh:348-355

## Summary of Changes

Replaced the `! cat <<EOL | docker run ... | grep -v '^No syntax errors'` / `$? -ne 0` pattern (which inverted the pipeline's exit status and could never detect docker itself failing) with:

1. Capture the pipeline's output via command substitution and `docker run`'s own exit status via `$?` immediately after.
2. Explicitly fail if that status is non-zero, OR the output is empty (docker/php produced nothing), OR any output line doesn't start with 'No syntax errors' (a real php -l error).

## Verification

- `pnpm test:php`: passes end-to-end (15/15 PHPUnit, all 4 packages' syntax checks report 'No syntax errors detected' as before).
- Simulated a docker-run failure (bad/unpullable image tag): confirmed the new logic now catches it ($SYNTAX_CHECK_STATUS=125) and would exit 1, whereas the old inverted-grep logic would have silently treated it as success.
