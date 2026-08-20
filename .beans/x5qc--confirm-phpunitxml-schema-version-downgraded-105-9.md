---
# x5qc
title: Confirm phpunit.xml schema version (downgraded 10.5 -> 9.6) matches the actually installed PHPUnit
status: completed
type: task
priority: low
created_at: 2026-08-17T13:40:07Z
updated_at: 2026-08-18T08:50:12Z
parent: qi52
---

phpunit/phpunit.xml's xsi:noNamespaceSchemaLocation was changed from https://schema.phpunit.de/10.5/phpunit.xsd to .../9.6/phpunit.xsd as part of this PR, alongside a solid, well-documented improvement to test-discovery paths (now <exclude> for vendor/node_modules).

## Impact

If the actual phpunit/phpunit binary baked into the new wordpress-alpine image is still a 10.x/11.x release (not confirmed pinned to an explicit version visible in the Dockerfile), config validated against the 9.6 schema could silently accept options invalid for the real running version, or IDE-based XML validation (intelephense/PHPStorm) would flag correct 10.x-only config as invalid.

## Fix

Confirm the PHPUnit version actually installed in packages/docker/wordpress-alpine's image (via its composer.lock) and match phpunit.xml's schema reference to it.

## Location

phpunit/phpunit.xml
packages/docker/wordpress-alpine/Dockerfile (composer.lock pinning PHPUnit version)

## Summary of Changes

No code change needed - confirmed the schema version already correctly matches the actually installed PHPUnit.

packages/docker/wordpress-alpine/Dockerfile:150-164 deliberately pins `phpunit/phpunit: ^9.0` (documented: WordPress core's own test harness still calls `PHPUnit\Util\Test::parseTestMethodAnnotations()`, removed in PHPUnit 10+ - WP Trac #59486/#62004). `phpunit/phpunit.xml`'s `9.6` schema reference matches this exactly.

## Verification

`docker exec ionos-wordpress-dev /opt/wp-tests/vendor/bin/phpunit --version`: reports 'PHPUnit 9.6.35 by Sebastian Bergmann and contributors' - confirms the schema reference and the real installed binary agree.
