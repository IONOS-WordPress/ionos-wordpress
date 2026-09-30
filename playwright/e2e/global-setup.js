/* eslint-disable-next-line import/named */
import { request } from '@playwright/test';
import { copyFileSync, existsSync } from 'fs';

import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

import { dumpTestDb, execTestCLI } from '../exec-test-cli';
import { STORAGE_STATE_PATH, STORAGE_STATE_SNAPSHOT_PATH } from './storage-state';

async function globalSetup(config) {
  const { baseURL } = config.projects[0].use;

  // theme/plugin activation below fires WordPress's fire-and-forget wp-cron loopback request,
  // which keeps writing to wp_options (e.g. via register_uninstall_hook()'s read-modify-write of
  // the 'uninstall_plugins' option) after the *triggering* REST call has already returned. Left
  // enabled, that lingering request can still be running when dumpTestDb()/restoreTestDb() below
  // dump or reload wp_options moments later, corrupting the snapshot with a duplicate-key error
  // on an unrelated later test (the exact flaky failure this fixes). Tests trigger any cron they
  // actually need explicitly via `wp cron event run`, so disabling auto-spawn here is safe.
  execTestCLI(`wp --quiet config set DISABLE_WP_CRON true --raw --type=constant`);

  const requestContext = await request.newContext({ baseURL });

  const requestUtils = new RequestUtils(requestContext, { storageStatePath: STORAGE_STATE_PATH });

  // Authenticate and save the storageState to disk.
  await requestUtils.setupRest();

  // Reset the test environment before running the tests.
  await Promise.all([
    requestUtils.activateTheme('twentytwentyfive'),
    // hack: only activate the essentials plugin if it's not already part of the mu-plugins
    existsSync('packages/wp-mu-plugin/stretch-extra/stretch-extra/plugins/ionos-essentials')
      ? Promise.resolve()
      : requestUtils.activatePlugin('essentials'),
    // // Disable this test plugin as it's conflicting with some of the tests.
    // // We already have reduced motion enabled and Playwright will wait for most of the animations anyway.
    // requestUtils.deactivatePlugin(
    // 	'gutenberg-test-plugin-disables-the-css-animations'
    // ),
    // requestUtils.deleteAllPosts(),
    // requestUtils.deleteAllBlocks(),
    // requestUtils.resetPreferences(),
  ]);

  await requestContext.dispose();

  // snapshot the database (and the login cookies matching it) now that they're in the exact
  // state every e2e test should start from - playwright/e2e/fixtures.js restores both together
  // before each test, since a spec calling requestUtils.setupRest() rotates the DB session
  // token AND rewrites STORAGE_STATE_PATH, and only the DB half of that is undone by a plain
  // DB restore.
  dumpTestDb();
  copyFileSync(STORAGE_STATE_PATH, STORAGE_STATE_SNAPSHOT_PATH);
}

export default globalSetup;
