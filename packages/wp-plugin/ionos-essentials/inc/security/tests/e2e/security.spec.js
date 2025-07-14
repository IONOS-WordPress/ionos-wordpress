import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { execTestCLI } from '../../../../../../../playwright/wp-env.js';

test.beforeAll(async () => {
  try {
    execTestCLI(`
      wp user update admin --user_pass=g0lasch0815!
      wp user meta delete admin ionos_compromised_credentials_check_leak_detected_v2
    `);
  } catch (ex) {}
});

// async function login (page) {
//   // Normally we use the wp-env standard user, but we need to create a new user for testing security
//   // as the wp-env user has a leaked password
//   await page.goto('/wp-admin')
//   await page.fill('#user_login', 'testUserWP')
//   await page.fill('#user_pass', 'password_748932A')
//   await page.click('[name="wp-submit"]')

//   // Click the Confirm-email button, if it exists
//   try {
//     await page.waitForSelector('button:has-text("Bestätigen")', { timeout: 5000 });
//     await page.click('button:has-text("Bestätigen")');
//   } catch (error) {
//     // Silence is golden.
//   }
// }

test('prevent log in with e-mail', async ({ page }) => {
  // Login with email
  await page.goto('/wp-admin');
  await page.fill('#user_login', 'you@example.com');
  await page.fill('#user_pass', 'password');
  await page.click('[name="wp-submit"]');

  expect(await page.title()).toContain('Log In');
});

// test('warning of no ssl', async ({ page }) => {
//   await login(page)

//   await expect(page.locator('.ionos-ssl-check')).toHaveCount(1)
// })

// test('disallow xml rpc', async ({ request }) => {
//   const requestBody = `<?xml version="1.0" encoding="UTF-8"?>
// <methodCall>
// <methodName>wp.getUsersBlogs</methodName>
// <params>
// <param><value>admin</value></param>
// <param><value>password</value></param>
// </params>
// </methodCall>`

//   const response = await request.post('/xmlrpc.php', {
//     data: requestBody
//   })

//   const body = await response.text()

//   expect(body).toContain('<name>faultCode</name>')
// })

// test('wpscan', async ({ page }) => {
//   await login(page)

//   await page.goto( '/wp-admin/admin.php?page=ionos_security&tab=wpscan')
//   expect(await page.textContent('text=Malware Dummy Plugin')).toBeTruthy()

//   await page.goto( '/wp-admin/tools.php')
//   expect(await page.textContent('text=Weitere Informationen')).toBeTruthy()

//   await page.goto( '/wp-admin/index.php')
//   expect(await page.textContent('text=Letzter Scan')).toBeTruthy()
// })

// test('wpscan before install', async ({ page }) => {
//   await login(page)

//   await page.goto( '/wp-admin/plugin-install.php?tab=ionos')
//   await page.click('[data-slug=woocommerce-german-market-light]')

//   expect(await page.textContent('text=Der Sicherheits-Scan ergab keine Probleme.')).toBeTruthy()
// })
