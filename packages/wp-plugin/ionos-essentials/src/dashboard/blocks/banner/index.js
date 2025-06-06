import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit.js';
import metadata from './block.json';
import './banner-style.scss';

registerBlockType(metadata.name, {
  edit: Edit,
  save: () => null, // server-side rendering doesnt need a save function
});
