---
# fmkv
title: Fix line-length (>120 char) violations introduced in this PR
status: todo
type: task
priority: low
created_at: 2026-08-17T13:40:19Z
updated_at: 2026-08-17T13:40:19Z
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
