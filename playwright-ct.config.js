// playwright config for storybook and react component testing
import { defineConfig, devices } from '@playwright/experimental-ct-react';

/**
 * @see https://playwright.dev/docs/test-configuration
 */
const config = defineConfig({
  testMatch: '**/*.spec.jsx',
  testDir: './packages',
  /* The base directory, relative to the config file, for snapshot files created with toMatchSnapshot and toHaveScreenshot. */
  snapshotDir: './playwright/storybook/__snapshots__',
  /* Maximum time one test can run for. */
  timeout: 10 * 1000,
  /* Run tests in files in parallel */
  fullyParallel: false,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  retries: 0,
  // Opt out of parallel tests
  workers: 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [['dot']],

  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',

    /* Port to use for Playwright component endpoint. */
    ctPort: 3100,

    /* keep only videos for failed tests */
    video: 'off',

    launchOptions: {
      // https://playwright.dev/docs/test-use-options#more-browser-and-context-options
      // slow down the tests to make queries more stable
      slowMo: 50,
    },
  },
  outputDir: './playwright/storybook/.test-results',
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        channel: 'chromium',
      },
    },
  ],
});

export default config;
