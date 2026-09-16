---
# 116s
title: 'secondary-theme-dir.spec.js: stale /var/www/html path breaks e2e test'
status: completed
type: bug
priority: high
created_at: 2026-08-17T13:37:52Z
updated_at: 2026-08-18T07:55:06Z
parent: qi52
---

packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/tests/e2e/secondary-theme-dir.spec.js:43 still calls:

    execTestCLI('find /var/www/html/wp-content/themes -maxdepth 1 -type d -name "*" | sort')

'/var/www/html' was the old wp-env container's document root. Every other part of this PR moved the WordPress root to '/htdocs' (see scripts/includes/_docker-mounts.sh's mounts and phpunit/bootstrap.php's ABSPATH). This file's import was migrated (playwright/wp-env -> playwright/exec-test-cli) but the hardcoded path inside it was never updated.

## Impact

Running pnpm test:e2e against this spec's 'installable' test executes 'find' against a path that doesn't exist in the ionos-wordpress-test container. 'find' exits non-zero with "No such file or directory", and execSync (which throws on non-zero exit by default) crashes instead of returning theme directory names - the test fails outright.

Worth checking why this isn't currently surfacing as a CI failure on PR #910 (possibly this spec is being skipped, or the tag isn't included in the CI run's filter - worth confirming either way).

## Fix

Change '/var/www/html/wp-content/themes' to '/htdocs/wp-content/themes' (or derive it the same way other specs/scripts do).

## Location

packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/tests/e2e/secondary-theme-dir.spec.js:43

## Summary of Changes

Fixed the stale path: `/var/www/html/wp-content/themes` -> `/htdocs/wp-content/themes` in `secondary-theme-dir.spec.js:43`.

## Correction to the bean's stated impact

The bean predicted execSync would throw/crash on find's non-zero exit. Verified this isn't what actually happens: the command is `find ... | sort`, and the container's `sh` has no `pipefail`, so the pipeline's exit code is `sort`'s (0), not `find`'s (1) - `execSync` never throws. The real impact was a silent false-negative: `find` errored to stderr with empty stdout, and `expect(themeDirs).not.toContain(TEST_THEME_SLUG)` vacuously passed on the empty string regardless of whether the theme was actually (mis)installed under `/htdocs`. The test was passing for the wrong reason, not failing outright.

## Verification

`pnpm test:e2e secondary-theme-dir.spec.js`: 3/3 passed, now genuinely checking the real WordPress themes directory.
