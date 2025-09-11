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
    requestUtils.activatePlugin('essentials'),
  ]);

  await requestContext.dispose();
}

export default globalSetup;
