import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe(
  'essentials:dashboard page should be loaded without javascript console errors',
  {
    tag: ['@dashboard'],
  },
  () => {
    test('ensure no errors are listed in javascript console', async ({ page, admin }) => {
      const errors = [];
      page.on(
        'console',
        (_) =>
          // only log console error messages
          _.type() === 'error' &&
          // ignore favicon.ico errors (because its not defined in our setup)
          _?._event?.location?.url !== 'http://localhost:8889/favicon.ico' &&
          errors.push(_)
      );

      await admin.visitAdminPage('/');

      // .toHaveLength(0) is best practice according to playwright docs
      await expect(errors).toHaveLength(0);
    });
  }
);
