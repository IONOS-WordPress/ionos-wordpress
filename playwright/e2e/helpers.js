import { request } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

export async function createRequestUtils(config) {
  const { storageState, baseURL } = config.projects[0].use;
  const storageStatePath = typeof storageState === 'string' ? storageState : undefined;
  const requestContext = await request.newContext({ baseURL });
  const requestUtils = new RequestUtils(requestContext, { storageStatePath });
  return { requestContext, requestUtils };
}

export default createRequestUtils;
