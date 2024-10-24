import { fn } from '@storybook/test';

import MyButton from './../MyButton.jsx';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories#default-export
export default {
  title: 'test-plugin/feature-1/block-1/components/Button',
  component: MyButton,
  parameters: {
    /*
      Optional parameter to center the component in the Canvas. More info: https://storybook.js.org/docs/configure/story-layout
      `layout` accepts these options:

      - `centered`: center the component horizontally and vertically in the Canvas
      - `fullscreen`: allow the component to expand to the full width and height of the Canvas
      - `padded`: _(default)_ Add extra padding around the component in the Canvas
    */
    layout: 'centered',
  },
  // This component will have an automatically generated Autodocs entry: https://storybook.js.org/docs/writing-docs/autodocs
  tags: ['autodocs'],
  // More on argTypes: https://storybook.js.org/docs/api/argtypes
  argTypes: {
    disabled: {
      control: {
        type: 'number',
        min: 0,
        max: 100,
        step: 10,
      },
     },
     label: {
      control: {
        type: 'string'
      },
     },
     variant: {
      control: {
        type: 'string',
        control: 'radio',
        options: ['primary', 'secondary', 'tertiary', 'link']
      },
    },
  },
  // Use `fn` to spy on the onClick arg, which will appear in the actions panel once invoked: https://storybook.js.org/docs/essentials/actions#action-args
  args: { onClick: fn() },
};

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Primary = {
  args: {
    variant : 'primary',
    label: 'Primary Button',
  },
};

export const Secondary = {
  args: {
    variant : 'secondary',
    label: 'Secondary Button',
  },
};

export const Tertiary = {
  args: {
    variant : 'tertiary',
    disabled: true,
    label: 'Tertiary disabled Button',
  },
};

export const Link = {
  args: {
    variant : 'link',
    disabled: true,
    label: 'Link disabled Button',
  },
};
