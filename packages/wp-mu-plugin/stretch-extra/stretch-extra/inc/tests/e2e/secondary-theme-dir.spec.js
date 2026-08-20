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
    // The active theme (twentytwentyfive) and stretch_extra_extendable_theme_dir_initialized are
    // already what this file needs in the restored snapshot, so neither is set here any more.
    // IONOS_CUSTOM_DELETED_THEMES_OPTION is the one thing that differs between an e2e-only run
    // (absent) and one that follows phpunit (present, but an empty array), and the 'deletable'
    // test below only passes when it is absent - established by bisecting the original hook, not
    // explained: inc/secondary-theme-dir.php reads it as get_option(..., []) throughout, which
    // ought to make the two indistinguishable. Do not drop this line without re-running
    // `pnpm run test --use php --use e2e`; an e2e-only run stays green either way.
    test.beforeAll(async () => {
      execTestCLI(`
        wp option delete IONOS_CUSTOM_DELETED_THEMES_OPTION
      `);
    });

    test('deletable', async ({ admin, page }) => {
      await admin.visitAdminPage('/themes.php?search=' + TEST_THEME_SLUG);

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
      const themeDirs = execTestCLI('find /htdocs/wp-content/themes -maxdepth 1 -type d -name "*" | sort');
      expect(themeDirs).not.toContain(TEST_THEME_SLUG);
    });

    test('no update message', async ({ admin, page }) => {
      await admin.visitAdminPage('/themes.php');
      await expect(page.locator(`.theme[data-slug=${TEST_THEME_SLUG}] .update-message`)).toHaveCount(0);
    });
  }
);
