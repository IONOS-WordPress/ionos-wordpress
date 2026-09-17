---
# rzu8
title: 'PHPUnit: plugin hooks are stripped after the first test, so REST routes are not dispatchable'
status: completed
type: task
priority: normal
created_at: 2026-09-17T07:28:27Z
updated_at: 2026-09-17T07:36:07Z
---

## Problem

Plugin hooks registered at plugin load time are stripped after the first test of a run:
the plugin is first loaded by `\activate_plugin()` inside a test `setUp()`, which happens
_after_ `WP_UnitTestCase` snapshots `$wp_filter`. The restore in `tearDown()` then removes
every hook the plugin added, and `require_once` will not re-run the file, so the hooks are
gone for the remainder of the run.

Consequence: REST routes registered in anonymous `rest_api_init` callbacks are only
dispatchable in the first test of a run.

- `inc/loop/tests/phpunit/LoopTest.php:41` works around it by re-registering its route in the
  test, duplicating the route definition.
- `inc/dashboard/tests/phpunit/OptionSetEndpointTest.php` skips its dispatch tests when the
  route is absent, so only one of them actually exercises the endpoint per run.

## Options

- Load the plugins under test on `muplugins_loaded` from `phpunit/bootstrap.php` (the
  commented-out `_manually_load_plugin()` scaffold is already there), so plugin hooks predate
  the snapshot. Repo-wide fix; needs a check that the other suites still pass.
- Or re-fire plugin bootstrapping in a shared base test case.

## Todo

- [x] Pick an approach and implement it
- [x] Drop the skip guard in OptionSetEndpointTest so all its dispatch tests run
- [x] Drop the duplicated route registration + @TODO in LoopTest

## Summary of Changes

`phpunit/bootstrap.php` now requires every workspace plugin main file
(`wp-content/plugins/<dir>/<dir>.php`) on `muplugins_loaded`, replacing the commented-out
`_manually_load_plugin()` scaffold. Plugin hooks are therefore in place before
`WP_UnitTestCase` takes its hook snapshot, so they survive the restore in `tearDown()` and
REST routes stay dispatchable for the whole run.

Follow-ups this unblocked:

- `inc/dashboard/tests/phpunit/OptionSetEndpointTest.php` no longer skips its dispatch tests.
- `inc/loop/tests/phpunit/LoopTest.php` no longer re-registers its route in `setUp()`; the
  `@TODO` and the duplicated `register_rest_route()` call are gone, so the test now exercises
  the route the plugin actually registers.

Verification: `pnpm test:php` 36/36, no skips (was 10 skipped); each class also passes in
isolation; `pnpm lint` passes.
