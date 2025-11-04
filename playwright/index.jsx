// TODO: Remove the eslint-disable-next-line when eslint is able to resolve @storybook/react
// eslint-disable-next-line import/no-unresolved
import { setProjectAnnotations } from '@storybook/react';
import previewAnnotations from '../.storybook/preview';

setProjectAnnotations(previewAnnotations);

import './../.storybook/style.css';
