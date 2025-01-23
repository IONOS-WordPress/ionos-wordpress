// playwright config for wp-env based e2e tests
import { defineConfig } from '@playwright/test';

import baseConfig from '@wordpress/scripts/config/playwright.config.js';

const config = defineConfig({
  ...baseConfig,
  testMatch: '**/wp-plugin/**/tests/e2e/*.spec.js',
  testDir: '.',
  webServer: {
    ...baseConfig.webServer,
    command: 'pnpm start',
  },
  outputDir: './playwright/e2e/.test-results',
  use: {
    ...baseConfig.use,
    storageState: './playwright/e2e/.storage-states/admin.json',
    // @TODO: as of now wp-scripts uses a different version of playwright
    // causing not to use the already downloaded chrome browser of storybook
    // thats why we inject it here manually
    launchOptions: {
      executablePath: process.env.PLAYWRIGHT_CHROME_PATH,
    },
  },
  globalSetup: './playwright/e2e/global-setup.js',
});

export default config;
