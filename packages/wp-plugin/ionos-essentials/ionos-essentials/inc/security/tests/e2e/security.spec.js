import { restoreDbOnce, test, expect } from '../../../../../../../../playwright/e2e/fixtures';
test.beforeAll(restoreDbOnce);

test.describe(
  'essentials:dashboard maintenance',
  {
    tag: ['@dashboard', '@maintenance'],
  },
  () => {
    // no setup of our own: restoreDbOnce above already brings back the snapshot, in which
    // scripts/test.sh has reset the admin password and dropped the compromised-credentials
    // meta, and IONOS_SECURITY_FEATURE_OPTION already holds every flag enabled (identical to
    // inc/security/index.php's IONOS_SECURITY_FEATURE_OPTION_DEFAULT).
    test('prevent log in with e-mail', async ({ page, requestUtils }) => {
      // this test needs the login form, so it has to give up the logged-in state the shared
      // storage state provides first. It used to get there as a side effect of a
      // `wp user update admin --user_pass=...` in a beforeAll: changing the password
      // invalidates the existing auth cookie. Dropping the cookie directly says so out loud -
      // requestUtils.setupRest() at the end of the test restores the login state for the
      // tests that follow.
      await page.context().clearCookies();

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
