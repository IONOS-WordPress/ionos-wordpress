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
        # set popup after timestamp to a far future date to prevent popups during e2e tests
        wp --quiet user meta update 1 ionos_popup_after_timestamp ${Math.MAX_SAFE_INTEGER}
        # set essentials welcome overlay already clicked away
        wp --quiet user meta update 1 ionos_essentials_welcome true
        # simulate extendify onboarding already done
        wp --quiet option update extendify_attempted_redirect_count 4
        # reset maintenance mode
        wp --quiet option delete ionos_essentials_maintenance_mode
      `);
    });

    test.afterAll(() => execTestCLI(`wp --quiet option delete ionos_essentials_maintenance_mode`));

    test('maintenance mode is enabled', async ({ admin, page, requestUtils }) => {
      await admin.visitAdminPage('/');
      await page.getByRole('button', { name: 'Tools' }).click();
      await page.locator('#ionos_essentials_maintenance_mode').click();

      // Still no redirection to the maintenance page, because we are logged in
      await page.goto('/');
      let body = await page.locator('body');
      await expect(body).toHaveText(/Blog/);

      await page.goto('/wp-login.php?action=logout');

      /*
        important : we log out and afterwards we ultimatively restore the login state in any cases
                    (=> finally block) to ensure further tests are not affected by our log out action
      */
      try {
        await page.click('text=log out');

        await page.goto('/');
        body = await page.locator('body');

        // @TODO: do we need this timeout here to make the test pass reliably?
        // commented it out for now, seems to work without it
        // await page.waitForTimeout(1000);

        await expect(page).toHaveTitle('Construction');
      } finally {
        // restore login state for further tests
        // (see https://github.com/WordPress/gutenberg/blob/trunk/packages/e2e-test-utils-playwright/src/test.ts#L124 for all fixtures available in the test context)
        await requestUtils.setupRest();
      }

      // CAVEAT : you are NOT logged in at this point. but at the next test start, the logged-in state is restored
      // so if you want to test something that requires a logged-in user, you need to do that in follow up tests
      // or do a re-login by "clicking" programmatically the login link and logging in again
      // (via wp-login.php)
    });

    /*
    // Example tests for iterated login/logout cycles

    test('test already logged in', async ({ admin, page, requestUtils }) => {
      // assert we already are logged in
      await admin.visitAdminPage('/');
      await page.getByRole('button', { name: 'Tools' }).click();

      // logout
      await page.goto('/wp-login.php?action=logout');
      await page.click('text=log out');

      // browser gets re logged in
      await requestUtils.setupRest();
    });

    test('test already logged in again', async ({ admin, page, requestUtils }) => {
      // assert we already are logged in
      await admin.visitAdminPage('/');
      await page.getByRole('button', { name: 'Tools' }).click();

      // logout
      await page.goto('/wp-login.php?action=logout');
      await page.click('text=log out');

      // browser gets re logged in
      await requestUtils.setupRest();
    });
  }
  */
  }
);
