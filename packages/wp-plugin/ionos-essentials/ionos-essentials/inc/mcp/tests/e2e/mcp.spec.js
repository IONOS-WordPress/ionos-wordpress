import { restoreDbOnce, test, expect } from '../../../../../../../../playwright/e2e/fixtures';
import { execTestCLI } from '../../../../../../../../playwright/exec-test-cli';
test.beforeAll(restoreDbOnce);

test.describe(
  'MCP',
  {
    tag: ['@mcp'],
  },
  () => {
    // filesystem state, which the per-file database restore deliberately does not cover: the
    // test installs wordpress-mcp through the UI, so a retry would otherwise start with the
    // previous attempt's plugin already installed and active. The database side of that install
    // (wordpress_mcp_settings, the generated application password) needs no reset - the restored
    // snapshot has neither.
    test.beforeAll(async () => {
      execTestCLI('wp plugin delete wordpress-mcp');
    });

    test('Get MCP snippet', async ({ admin, page, baseURL }) => {
      const errors = [];
      page.on('console', (msg) => {
        if (msg.type() !== 'error') {
          return;
        }

        // only errors coming from the site under test say anything about the snippet.
        // the dashboard also pulls third party assets (e.g. the inpagelayer css from
        // frontend-services.ionos.com), and a failure to reach those makes this
        // assertion depend on outbound network access and on somebody else's uptime
        // rather than on our own code - it is what made this test flaky.
        const source = msg.location()?.url ?? '';
        if (source !== '' && !source.startsWith(baseURL)) {
          return;
        }

        errors.push(msg.text());
      });

      await admin.visitAdminPage('?page=ionos#tools');
      await page.locator('#ionos-essentials-mcp').click();

      await expect(page.locator('code')).toHaveText(/WP_API_PASSWORD/);

      // Test if plugin is installed via WP CLI
      const pluginListOutput = execTestCLI('wp plugin list --format=json');
      const plugins = JSON.parse(pluginListOutput);
      const mcpPlugin = plugins.find((plugin) => plugin.name === 'wordpress-mcp');
      expect(mcpPlugin).toBeTruthy();
      expect(mcpPlugin.status).toBe('active');

      // Make sure there are no console errors. This is to catch any issues with loading the snippet.
      await expect(errors).toEqual([]);
    });
  }
);
