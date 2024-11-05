import { composeStories } from '@storybook/react'

import * as stories from '../stories/MyButton.stories.js';

// Output an object that maps 1:1 to your stories, now in portable components
export default composeStories(stories);
