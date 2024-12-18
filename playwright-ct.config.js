const { defineConfig, devices } = require('@playwright/experimental-ct-react');

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
  testMatch: '*.spec.jsx',
  testDir: '.',
  /* The base directory, relative to the config file, for snapshot files created with toMatchSnapshot and toHaveScreenshot. */
  snapshotDir: './playwright/__snapshots__',
  /* Maximum time one test can run for. */
  timeout: 10 * 1000,
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  // Opt out of parallel tests on CI.
  workers: process.env.CI ? 1 : undefined,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    process.env.CI ? ['dot'] : ['list', { printSteps: true }],
    ['html', { outputFolder: './playwright/.playwright-report' }],
  ],

  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',

    /* Port to use for Playwright component endpoint. */
    ctPort: 3100,

    /* keep only videos for failed tests */
    video: 'retain-on-failure',

    launchOptions: {
      // https://playwright.dev/docs/test-use-options#more-browser-and-context-options
      // slow down the tests to make queries more stable
      slowMo: 50,
    },
  },
  outputDir: './playwright/.test-results',
});
