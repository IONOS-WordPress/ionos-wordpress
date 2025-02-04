import { test, expect } from '@playwright/experimental-ct-react';

import MyButton from './../MyButton.jsx';

test('MyButton component', async ({ mount, page }) => {
  let clicked = 0;
  const onClick = () => clicked++;

  await mount(
    <MyButton
      variant={'link'}
      onClick={onClick}
    />
  );

  const buttonLocator = await page.locator('button[type="button"]');

  await expect(buttonLocator, 'button was found').toBeTruthy();

  await expect(await buttonLocator.evaluate((element) => !element.disabled), 'button should be enabled').toBeTruthy();
  // same same as the line above
  await expect(buttonLocator).toBeEnabled();

  await expect(buttonLocator, "button should not have class 'is-link'").not.toHaveClass(/is-primary/);

  await expect(clicked).toBe(0);
  await buttonLocator.click();
  await expect(clicked).not.toBe(0);
  await expect(clicked).toBe(1);
});
