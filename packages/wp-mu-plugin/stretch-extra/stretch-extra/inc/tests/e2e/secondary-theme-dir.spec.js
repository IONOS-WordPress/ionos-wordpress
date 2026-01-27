import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../playwright/wp-env';

const RESET_IONOS_STRETCH_OPTIONS_CLI_COMMANDS = `
  # reset stretch-extra theme option
  wp option delete stretch_extra_extendable_theme_dir_initialized
`;

const TEST_THEME_SLUG = 'extenable';

test.describe(
  'stretch-extra:secondary-theme-dir',
  {
    tag: ['@stretch-extra', '@secondary-theme-dir'],
  },
  () => {
    test.beforeAll(async () => {
      execTestCLI(`
        wp plugin deactivate ionos-essentials
        ${RESET_IONOS_STRETCH_OPTIONS_CLI_COMMANDS}
        # is needed here to allow re-initialization of the extendable theme dir
        # since first wp call is made by requestUtils.activateTheme('twentytwentyfive'),
        # in a real world scenario this would only be needed once
        wp option delete stretch_extra_extendable_theme_dir_initialized
        wp option delete IONOS_CUSTOM_DELETED_THEMES_OPTION
        wp theme activate twentytwentyfive
      `);
    });

    test.afterAll(async () => {
      execTestCLI(`
        ${RESET_IONOS_STRETCH_OPTIONS_CLI_COMMANDS}
        # reactivate ionos-essentials plugin
        wp plugin activate ionos-essentials
      `);
    });

    test('deletable', async ({ admin, page }) => {
      await admin.visitAdminPage('/themes.php');

      page.once('dialog', async (dialog) => {
        await dialog.accept();
      });

      await page.locator(`.theme[data-slug=${TEST_THEME_SLUG}]`).click();
      await page.locator('a.delete-theme').click();
      await admin.visitAdminPage('/themes.php');
      await expect(page.locator(`.theme[data-slug=${TEST_THEME_SLUG}]`)).toHaveCount(0);
    });

    test('installable', async ({ admin, page }) => {
      await admin.visitAdminPage(`/theme-install.php?theme=${TEST_THEME_SLUG}`);
      await page.locator('.wp-full-overlay-header a.theme-install').click();
      await page.waitForTimeout(1000);

      await admin.visitAdminPage('/themes.php');
      await expect(page.locator(`.theme[data-slug=${TEST_THEME_SLUG}]`)).toBeVisible();

      // Verify theme is not installed in the standard theme directory
      const themeDirs = execTestCLI('find /var/www/html/wp-content/themes -maxdepth 1 -type d -name "*" | sort');
      expect(themeDirs).not.toContain(TEST_THEME_SLUG);
    });

    test('no update message', async ({ admin, page }) => {
      await admin.visitAdminPage('/themes.php');
      await expect(page.locator(`.theme[data-slug=${TEST_THEME_SLUG}] .update-message`)).toHaveCount(0);
    });
  }
);
