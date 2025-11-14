import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'essentials:dashboard maintenance',
  {
    tag: ['@dashboard', '@maintenance'],
  },
  () => {
    test.beforeAll(async () => {
      execTestCLI(`
        wp --quiet user update admin --user_pass='\${WP_PASSWORD}'
        wp --quiet user meta delete admin ionos_compromised_credentials_check_leak_detected_v2
        wp --quiet option delete IONOS_SECURITY_FEATURE_OPTION
      `);
    });

    test('prevent log in with e-mail', async ({ page, requestUtils }) => {
      // Login with email
      await page.goto('/wp-admin');
      await page.fill('#user_login', 'wordpress@example.com');
      await page.fill('#user_pass', 'g0lasch0815!');
      await page.click('[name="wp-submit"]');

      await expect(page.locator('.notice-error')).toHaveCount(1);

      await requestUtils.setupRest();
    });

    test('warning of no ssl', async ({ page, admin }) => {
      await admin.visitAdminPage('/');

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
  }
);
