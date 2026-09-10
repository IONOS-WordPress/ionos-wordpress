import { restoreDbOnce, test, expect } from '../../../../../../../playwright/e2e/fixtures';
import { execTestCLI } from '../../../../../../../playwright/exec-test-cli';
test.beforeAll(restoreDbOnce);

test.describe(
  'stretch-extra:marketplace',
  {
    tag: ['@stretch-extra', '@marketplace'],
  },
  () => {
    // both lines deviate from the restored snapshot on purpose - the stretch-extra mu-plugin has
    // already provisioned itself in it. The group brand this marketplace needs (ionos) is set by
    // the AFTER_START script, so it no longer has to be set here.
    test.beforeAll(async () => {
      execTestCLI(`
        # allow re-initialization of the extendable theme dir
        wp option delete stretch_extra_extendable_theme_dir_initialized
        # prevent auto initialization of stretch-extra provisioned plugins
        wp --quiet option update IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION '[]' --format=json
      `);
    });

    test('ionos tab is present', async ({ admin, page }) => {
      await admin.visitAdminPage('/plugin-install.php');
      await expect(page.locator('.plugin-install-ionos')).toHaveCount(1);
      await expect(page.locator('.plugin-card-ionos-essentials')).toHaveCount(1);
    });
  }
);
