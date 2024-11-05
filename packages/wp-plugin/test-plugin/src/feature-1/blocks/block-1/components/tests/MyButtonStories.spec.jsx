import { createTest } from '@storybook/react/experimental-playwright'
import { test as base, expect } from '@playwright/experimental-ct-react';

import stories from './MyButton.stories.portable.js';

import MyButton from '../MyButton.jsx';

const test = createTest(base)

test('Link story', async ({ mount, page }) => {
  await mount(<stories.Link />);

  /*
  <button type="button" disabled="" class="components-button is-link">Link disabled Button</button>
  */

  const buttonLocator = await page.locator('button[type="button"]');

  expect(buttonLocator, 'button was found').toBeTruthy();

  expect(await buttonLocator.evaluate( element => element.disabled ), 'button should be disabled').toBeTruthy();
  // same same as the line above
  expect(buttonLocator).toBeDisabled();

  expect(buttonLocator, "button should have class 'is-link'").toHaveClass(/is-link/);

  expect( buttonLocator.click(), 'button should not be clickable').rejects.toThrowError();
});
