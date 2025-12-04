import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { execTestCLI } from '../../../../../../../../playwright/wp-env';

test.describe(
  'MCP',
  {
    tag: ['@mcp'],
  },
  () => {
    test.beforeAll(async () => {
      execTestCLI('wp plugin delete wordpress-mcp');
      execTestCLI('wp --quiet option delete wordpress_mcp_settings');
      execTestCLI('wp user application-password delete 1 --all');
    });

    test('Get MCP snippet', async ({ admin, page }) => {
      const errors = [];
      page.on('console', (msg) => {
        if (msg.type() === 'error') {
          errors.push(msg.text());
        }
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
