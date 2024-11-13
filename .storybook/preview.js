import '@wordpress/components/build-style/style.css';
import './style.css';

/** @type { import('@storybook/react').Preview } */
const preview = {
  parameters: {
    // controls: {
    //   matchers: {
    //     color: /(background|color)$/i,
    //     date: /Date$/i,
    //   },
    // },
  },
  // Enables auto-generated documentation for all stories (https://storybook.js.org/docs/writing-docs/autodocs)
  tags: ['autodocs'],
};

export default preview;
