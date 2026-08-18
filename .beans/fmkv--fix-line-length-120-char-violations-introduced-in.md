---
# fmkv
title: Fix line-length (>120 char) violations introduced in this PR
status: completed
type: task
priority: low
created_at: 2026-08-17T13:40:19Z
updated_at: 2026-08-18T08:50:25Z
parent: qi52
---

AGENTS.md's Code Formatting section caps line length at 120 characters. This PR introduces violations in two files:

1. packages/docker/ecs-php/ecs-config.php:18-19 - two lines from the $vendorDir refactor (replacing hardcoded /composer paths) are 126 and 124 characters:

   PHP_CodeSniffer\Autoload::addSearchPath("$vendorDir/wp-coding-standards/wpcs/WordPress-Extra", "WordPressCS\WordPress-Extra");
    PHP_CodeSniffer\Autoload::addSearchPath("$vendorDir/wp-coding-standards/wpcs/WordPress-Core", "WordPressCS\WordPress-Core");

2. packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/secondary-theme-dir.php:25,36,47 - a repeated comment-wording edit ("... is required to work in local wp-env" -> "... is required to work in the local dev environment") pushed three near-identical comment lines to 121 characters each.

## Fix

Wrap/shorten the affected lines to stay within 120 characters.

## Location

packages/docker/ecs-php/ecs-config.php:18-19
packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/secondary-theme-dir.php:25,36,47

## Summary of Changes

- packages/docker/ecs-php/ecs-config.php:17-19: extracted the repeated `$vendorDir/wp-coding-standards/wpcs` prefix into a \$wpcsDir variable, bringing both violating lines under 120 chars.
- packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/secondary-theme-dir.php: shortened the repeated comment ('is required to work in the local dev environment' -> 'is required in the local dev environment') at all 4 occurrences (lines 25, 36, 47, 57) - the bean only listed 3, but the same wording change from this PR appears a 4th time at line 57 too; fixed all 4. Also incidentally fixed a pre-existing double-space typo in that comment.

## Scope correction

Two other >120-char lines exist in these files (ecs-config.php:34, secondary-theme-dir.php:146) but are pre-existing, unrelated to this PR (confirmed via 'git diff develop...HEAD' - their content is untouched, just shifted to a different line number by unrelated nearby insertions). Left alone per the bean's own scope ('violations introduced in this PR').

## Verification

php -l clean on both files; 'pnpm lint --use php' passes, confirming ecs-config.php's refactored \$wpcsDir extraction still resolves the wpcs standards correctly.
