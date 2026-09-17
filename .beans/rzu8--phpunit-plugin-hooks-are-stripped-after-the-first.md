---
# rzu8
title: 'PHPUnit: plugin hooks are stripped after the first test, so REST routes are not dispatchable'
status: todo
type: task
priority: normal
created_at: 2026-09-17T07:28:27Z
updated_at: 2026-09-17T07:28:27Z
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

- [ ] Pick an approach and implement it
- [ ] Drop the skip guard in OptionSetEndpointTest so all its dispatch tests run
- [ ] Drop the duplicated route registration + @TODO in LoopTest
