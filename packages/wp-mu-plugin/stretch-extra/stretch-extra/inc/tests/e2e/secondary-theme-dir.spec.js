import { restoreDbOnce, test, expect } from '../../../../../../../playwright/e2e/fixtures';
import { execTestCLI } from '../../../../../../../playwright/exec-test-cli';
test.beforeAll(restoreDbOnce);

const TEST_THEME_SLUG = 'extendable';

test.describe(
  'stretch-extra:secondary-theme-dir',
  {
    tag: ['@stretch-extra', '@secondary-theme-dir'],
  },
  () => {
    // No setup of its own: the restored snapshot already has twentytwentyfive active and
    // stretch_extra_extendable_theme_dir_initialized set. This file used to delete
    // IONOS_CUSTOM_DELETED_THEMES_OPTION here, which looked load-bearing because the tests failed
    // without it - that was a race, not the option (see the waits below).
    test('deletable', async ({ admin, page }) => {
      await admin.visitAdminPage('/themes.php?search=' + TEST_THEME_SLUG);

      page.once('dialog', async (dialog) => {
        await dialog.accept();
      });

      await page.locator(`.theme[data-slug=${TEST_THEME_SLUG}]`).click();

      // Deleting a theme goes over admin-ajax (WP's updates.js intercepts the link), so the click
      // resolves long before the server has recorded anything. Wait for that response instead of
      // racing it: navigating to themes.php too early renders a page that still lists the theme,
      // and toHaveCount below only retries the locator, never the navigation - so the assertion
      // can never recover from it.
      await Promise.all([
        page.waitForResponse(
          (response) =>
            response.url().includes('admin-ajax.php') &&
            (response.request().postData() ?? '').includes('action=delete-theme')
        ),
        page.locator('a.delete-theme').click(),
      ]);

      await admin.visitAdminPage('/themes.php');
      await expect(page.locator(`.theme[data-slug=${TEST_THEME_SLUG}]`)).toHaveCount(0);
    });

    test('installable', async ({ admin, page }) => {
      await admin.visitAdminPage(`/theme-install.php?theme=${TEST_THEME_SLUG}`);

      // installing is admin-ajax too (see inc/secondary-theme-dir.php's wp_ajax_install-theme),
      // so wait for the response rather than sleeping a fixed second and hoping
      await Promise.all([
        page.waitForResponse(
          (response) =>
            response.url().includes('admin-ajax.php') &&
            (response.request().postData() ?? '').includes('action=install-theme')
        ),
        page.locator('.wp-full-overlay-header a.theme-install').click(),
      ]);

      await admin.visitAdminPage('/themes.php');
      await expect(page.locator(`.theme[data-slug=${TEST_THEME_SLUG}]`)).toBeVisible();

      // Verify theme is not installed in the standard theme directory
      const themeDirs = execTestCLI('find /htdocs/wp-content/themes -maxdepth 1 -type d -name "*" | sort');
      expect(themeDirs).not.toContain(TEST_THEME_SLUG);
    });

    test('no update message', async ({ admin, page }) => {
      await admin.visitAdminPage('/themes.php');
      await expect(page.locator(`.theme[data-slug=${TEST_THEME_SLUG}] .update-message`)).toHaveCount(0);
    });
  }
);
