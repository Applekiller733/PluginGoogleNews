/**
 * Registers the Follow on Google Buttons block on the client.
 *
 * @package FollowOnGoogle
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import metadata from './block.json';

import './style.scss';
import './editor.scss';

/**
 * This block uses server-side (dynamic) rendering via render.php,
 * so no save function is needed — save returns null.
 */
registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
