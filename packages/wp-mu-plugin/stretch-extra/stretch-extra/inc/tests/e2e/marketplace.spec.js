import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../playwright/wp-env';

test.describe(
  'stretch-extra:marketplace',
  {
    tag: ['@stretch-extra', '@marketplace'],
  },
  () => {
    test.beforeAll(async () => {
      execTestCLI(`
        # reset stretch-extra theme option
        wp option delete stretch_extra_extendable_theme_dir_initialized
        # prevent auto initialization of stretch-extra provisioned plugins
        wp --quiet option update IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION '[]' --format=json
        # reset deleted custom plugins
        wp --quiet option update IONOS_CUSTOM_DELETED_PLUGINS_OPTION '[]' --format=json
        # marketplace is only be active on group brand ionons
        wp --quiet option set ionos_group_brand 'ionos'
        # disable ionos-core marketplace to avoid duplicate marketplace implementation
        wp --quiet option set ionos_core_disable_marketplace '1'
      `);
    });

    test('ionos tab is present', async ({ admin, page }) => {
      await admin.visitAdminPage('/plugin-install.php');
      await expect(page.locator('.plugin-install-ionos')).toHaveCount(1);
      await expect(page.locator('.plugin-card-01-ext-ion8dhas7-stretch')).toHaveCount(1);
    });
  }
);
