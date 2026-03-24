import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'essentials:dashboard security options',
  {
    tag: ['@dashboard', '@security'],
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
        
        # simulate switch page decision
        wp --quiet option update IONOS_ESSENTIALS_ONBOARDING diy
        
        # test specific
        wp --quiet option delete IONOS_SECURITY_FEATURE_OPTION`);
    });

    test('user can set option', async ({ admin, page }) => {
      await admin.visitAdminPage('?page=ionos#tools');
      const body = page.locator('body');
      const toggle = body.locator('#IONOS_SECURITY_FEATURE_OPTION_PEL');

      await expect(toggle).toBeChecked();
      await toggle.click();
      await page.waitForTimeout(3000);
      await page.reload();
      await expect(toggle).not.toBeChecked();
    });
  }
);
