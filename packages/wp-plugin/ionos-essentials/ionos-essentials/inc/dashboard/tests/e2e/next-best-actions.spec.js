import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe('essentials:dashboard next-best-actions block', () => {
  test.beforeAll(async () => {
    execTestCLI(`
      # reset nba options
      wp option delete ionos_nba_status ionos_essentials_nba_setup_completed ionos_essentials_loop_nba_actions_shown
      # set essentials welcome overlay already clicked away
      wp user meta update 1 ionos_essentials_welcome true
      # simulate extendify onboarding already done
      wp option update extendify_attempted_redirect_count 4
    `);
  });

  test('test dismissing an option ', async ({ admin, page }) => {
    await admin.visitAdminPage('/');
    let body = page.locator('body');

    let dismissAnchor = body.locator('.ionos_finish_setup');
    await expect(dismissAnchor).toHaveCount(1);
    await dismissAnchor.click();

    // show dashboard and ensure "create-page" action is not more available
    await admin.visitAdminPage('/');
    body = page.locator('body');
    dismissAnchor = body.locator('.ionos_finish_setup');
    await expect(dismissAnchor).toHaveCount(0);
  });
});
