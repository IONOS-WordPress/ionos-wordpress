import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'essentials:dashboard ionos-essentials-dashboard-admin will use wp-admin language',
  {
    tag: ['@dashboard', '@i18n'],
  },
  () => {
    test('/dashboard uses wp-admin language', async ({ admin, page }) => {
      execTestCLI(`wp --quiet user meta delete 1 ionos_essentials_welcome || true`);

      const targetLanguageName = 'de_DE';

      // Select the new language and save changes
      await test.step(`Change site language to ${targetLanguageName}`, async () => {
        await admin.visitAdminPage('/options-general.php');
        const currentLanguage = await page.inputValue('#WPLANG');

        if (currentLanguage === targetLanguageName) {
          return;
        }

        await page.selectOption('#WPLANG', targetLanguageName);
        await page.click('#submit');

        // Wait for the success message element
        await expect(page.locator('#setting-error-settings_updated')).toBeVisible();
      });

      await test.step(`check dashboard welcome banner in german`, async () => {
        await admin.visitAdminPage('/');

        const body = await page.locator('body');

        const title = await body.locator('#essentials-welcome_block h2.headline');
        await expect(title).toHaveText(/Willkommen/i);
      });

      // Revert language change for other tests
      await test.step(`Revert site language to English`, async () => {
        await admin.visitAdminPage('/options-general.php');
        const currentLanguage = await page.inputValue('#WPLANG');

        if (currentLanguage === 'en') {
          return;
        }

        // Select the default (=>"English (United States)") from the dropdown
        await page.locator('#WPLANG').selectOption('');
        await page.click('#submit');

        // Wait for the success message element
        await expect(page.locator('#setting-error-settings_updated')).toBeVisible();
      });

      await test.step(`check dashboard welcome banner in english`, async () => {
        await admin.visitAdminPage('/');

        const body = await page.locator('body');

        const title = await body.locator('#essentials-welcome_block h2.headline');
        await expect(title).toHaveText(/Welcome/i);
      });
    });
  }
);
