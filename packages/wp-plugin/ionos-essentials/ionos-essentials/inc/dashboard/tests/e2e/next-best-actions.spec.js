import { restoreDbOnce, test, expect } from '../../../../../../../../playwright/e2e/fixtures';
import { execTestCLI } from '../../../../../../../../playwright/exec-test-cli';
test.beforeAll(restoreDbOnce);

test.describe(
  'essentials:dashboard next-best-actions block',
  {
    tag: ['@dashboard', '@nba'],
  },
  () => {
    // the ionos_nba_* options are all absent in the restored snapshot, so only the two
    // deviations from it are set here.
    test.beforeAll(async () => {
      execTestCLI(`
        # set essentials welcome overlay already clicked away
        wp --quiet user meta update 1 ionos_essentials_welcome true
        # simulate extendify onboarding already done (the snapshot has it at 3)
        wp --quiet option update extendify_attempted_redirect_count 4
      `);
    });

    test('test dismissing an option ', async ({ admin, page }) => {
      await admin.visitAdminPage('/');
      let body = page.locator('body');

      let dismissAnchor = body.locator('.ionos_finish_setup');
      await expect(dismissAnchor).toHaveCount(1);

      // dismissing persists the state and then reloads the dashboard itself, on an 800ms
      // timer (see src/dashboard/index.js). wait for that reload to land instead of
      // racing it - navigating below while it is still pending aborts our navigation
      // with net::ERR_ABORTED, which is exactly what this test did under CI load.
      await Promise.all([page.waitForEvent('load'), dismissAnchor.click()]);

      // show dashboard and ensure "create-page" action is not more available
      await admin.visitAdminPage('/');
      body = page.locator('body');
      dismissAnchor = body.locator('.ionos_finish_setup');
      await expect(dismissAnchor).toHaveCount(0);
    });
  }
);
