import { test, expect } from '@playwright/experimental-ct-react';

import MyButton from './../MyButton.jsx';

test('MyButton component', async ({ mount, page }) => {
  let clicked = 0;
  const onClick = () => clicked++;

  await mount(<MyButton variant={'link'} onClick={onClick} />);

  const buttonLocator = await page.locator('button[type="button"]');

  expect(buttonLocator, 'button was found').toBeTruthy();

  expect(await buttonLocator.evaluate((element) => !element.disabled), 'button should be enabled').toBeTruthy();
  // same same as the line above
  expect(buttonLocator).toBeEnabled();

  expect(buttonLocator, "button should not have class 'is-link'").not.toHaveClass(/is-primary/);

  expect(clicked).toBe(0);
  await buttonLocator.click();
  expect(clicked).not.toBe(0);
  expect(clicked).toBe(1);
});
