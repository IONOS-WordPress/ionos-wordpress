import { restoreDbOnce, test, expect } from '../../../../../../../../playwright/e2e/fixtures';
import { execTestCLI } from '../../../../../../../../playwright/exec-test-cli';
test.beforeAll(restoreDbOnce);

test.describe(
  'essentials:dashboard security options',
  {
    tag: ['@dashboard', '@security'],
  },
  () => {
    // IONOS_SECURITY_FEATURE_OPTION already holds every flag enabled in the restored snapshot
    // (identical to inc/security/index.php's IONOS_SECURITY_FEATURE_OPTION_DEFAULT), which is
    // what the toggle assertion below needs.
    test.beforeAll(async () => {
      execTestCLI(`
        # set essentials welcome overlay already clicked away
        wp --quiet user meta update 1 ionos_essentials_welcome true
        # simulate extendify onboarding already done (the snapshot has it at 3)
        wp --quiet option update extendify_attempted_redirect_count 4
      `);
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
