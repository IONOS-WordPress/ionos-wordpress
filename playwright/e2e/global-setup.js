import { request } from '@playwright/test';

import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

/* eslint-disable-next-line import/named */
import {createRequestUtils} from './helpers'


async function globalSetup(config) {

  const { requestContext, requestUtils } = await createRequestUtils(config);

  // Authenticate and save the storageState to disk.
  await requestUtils.setupRest();

  // Reset the test environment before running the tests.
  await Promise.all([
    requestUtils.activateTheme('twentytwentyfive'),
    // @FIXME: activate all plugins
    requestUtils.activatePlugin('essentials'),
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

   const myFunc = async (name) => {
    console.log(`Hello from globalSetup function, ${name}!`);
  };

  return { hello: 'world', func: myFunc };
}

export default globalSetup;
