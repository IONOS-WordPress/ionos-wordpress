//
// shared e2e test/expect - import test/expect/restoreDbOnce from here instead of
// @wordpress/e2e-test-utils-playwright directly in every *.spec.js file, and call
// `test.beforeAll(restoreDbOnce)` as the very FIRST statement in the file (before any
// test.describe/test.beforeAll of your own), so specs don't leak WordPress state into one
// another.
//
// this restores the database snapshot taken by global-setup.js once per file, not once per
// test: several specs deliberately build up state across the tests in one file (e.g.
// secondary-theme-dir.spec.js's 'deletable' test sets up what 'installable' asserts on;
// welcome.spec.js's dismiss test sets up what 'still closed' asserts on) via their own
// test.beforeAll. Restoring before every single test would wipe that out too.
//
// this MUST be a plain test.beforeAll call at the top of each file, not a fixture (not even an
// auto fixture): test.beforeAll hooks run once per file, in a phase that precedes ALL
// test-scoped fixtures (including auto ones) for that file's first test - a fixture can never
// run before a file's own test.beforeAll. Registering our restore as a root-level
// test.beforeAll instead works because Playwright runs parent-suite hooks before child-suite
// hooks (the file itself is the outer suite, a test.describe inside it the inner one), so this
// always fires before that file's own test.describe-scoped beforeAll.
//

import { copyFileSync } from 'fs';

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { restoreTestDb } from '../exec-test-cli';
import { STORAGE_STATE_PATH, STORAGE_STATE_SNAPSHOT_PATH } from './storage-state';

export function restoreDbOnce() {
  // a spec calling requestUtils.setupRest() rotates the DB session token and rewrites
  // STORAGE_STATE_PATH - restore both together, or the next file's browser context can
  // present a cookie the just-restored DB no longer recognizes ("Not logged in").
  restoreTestDb();
  copyFileSync(STORAGE_STATE_SNAPSHOT_PATH, STORAGE_STATE_PATH);
}

export { test, expect };
