import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { execTestCLI } from '../../../../../../../playwright/wp-env.js';

test.beforeAll(async () => {
  try {
    execTestCLI(`
      wp user update admin --user_pass='\${WP_ENV_TEST_ADMIN_PASSWORD}'
      wp user meta delete admin ionos_compromised_credentials_check_leak_detected_v2
      wp option delete IONOS_SECURITY_FEATURE_OPTION
    `);
  } catch (ex) {}
});

async function login(page) {
  // Normally we use the wp-env standard user, but we need to create a new user for testing security
  // as the wp-env user has a leaked password
  await page.goto('/wp-admin');
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'g0lasch0815!');
  await page.click('[name="wp-submit"]');

  // Click the Confirm-email button, if it exists
  try {
    await page.waitForSelector('button:has-text("Log In")', { timeout: 5000 });
    await page.click('button:has-text("Log In")');
  } catch (error) {
    // Silence is golden.
  }
}

test('prevent log in with e-mail', async ({ page }) => {
  // Login with email
  await page.goto('/wp-admin');
  await page.fill('#user_login', 'wordpress@example.com');
  await page.fill('#user_pass', 'g0lasch0815!');
  await page.click('[name="wp-submit"]');

  await expect(page.locator('.notice-error')).toHaveCount(1);
});

test('warning of no ssl', async ({ page }) => {
  await login(page);

  await expect(page.locator('.ionos-ssl-check')).toHaveCount(1);
});

test('disallow xml rpc', async ({ request }) => {
  const requestBody = `<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
<methodName>wp.getUsersBlogs</methodName>
<params>
<param><value>admin</value></param>
<param><value>password</value></param>
</params>
</methodCall>`;

  const response = await request.post('/xmlrpc.php', {
    data: requestBody,
  });

  const body = await response.text();

  await expect(body).toContain('<name>faultCode</name>');
});
