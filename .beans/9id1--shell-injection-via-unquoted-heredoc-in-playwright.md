---
# 9id1
title: Shell injection via unquoted heredoc in playwright/exec-test-cli.js
status: completed
type: bug
priority: critical
created_at: 2026-08-17T13:37:19Z
updated_at: 2026-08-18T07:19:22Z
parent: qi52
---

execTestCLI() in playwright/exec-test-cli.js builds a heredoc with an unquoted delimiter (<<EOF instead of <<'EOF'), so the HOST shell expands $variables/backticks/$(...) in the interpolated wp-cli command before it is sent to the container via 'docker exec'.

Verified live: with a host env var FOO_TEST_VAR set, calling execTestCLI("wp eval 'echo $FOO_TEST_VAR;'") arrives at the container already substituted to the host's value, not passed through literally.

## Impact

Any Playwright spec calling execTestCLI() with a wp-cli command containing a '$' sequence (variable expansion, command substitution, or backticks) gets silently corrupted, emptied, or evaluated against the host's environment instead of the container's - this is a real bug affecting test correctness today, and a shell-injection-shaped footgun going forward (a command built from untrusted/dynamic input could have host-side code execution).

## Fix

Quote the heredoc delimiter: <<'EOF' instead of <<EOF, so the body is passed through to 'sh -' inside the container verbatim, with no host-side expansion.

## Location

playwright/exec-test-cli.js:12-16

## Summary of Changes

Quoted the heredoc delimiter (`<<'EOF'` instead of `<<EOF`) in `playwright/exec-test-cli.js` so the host shell no longer expands `$vars`/backticks/`$(...)` inside the wp-cli command body before it reaches the container. Verified with a repro script that `$FOO_TEST_VAR` now passes through literally instead of being substituted with the host's value.

## Follow-up fix

The 9id1 fix (quoting the heredoc delimiter) initially broke `security.spec.js` (`pnpm test:e2e security.spec.js`): `wp --quiet user update admin --user_pass='\${WP_PASSWORD}'` relied on the *old bug* — the unquoted heredoc let the host shell expand `${WP_PASSWORD}` textually even inside single quotes, before the text reached the container. With the delimiter quoted, nothing expands it anymore, so the password got set literally to the string `${WP_PASSWORD}`.

Fixed by switching that line's quoting to double quotes (`user_pass=\"${WP_PASSWORD}\"`), so the *container's* shell expands `$WP_PASSWORD` from its own environment (set via `--env WP_PASSWORD="$WP_PASSWORD"` in scripts/test.sh:258) instead of relying on host-side leakage. Checked every other `execTestCLI` call site in the repo — this was the only one depending on shell-escaped (`\$`) variables, so no other specs are affected. Verified: `pnpm test:e2e security.spec.js` — 3/3 passed.

File: packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/e2e/security.spec.js:12
