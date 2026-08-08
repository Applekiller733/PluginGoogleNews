<?php
/**
 * Plugin Name:       Follow on Google Buttons
 * Plugin URI:        https://example.com/follow-on-google
 * Description:        A customizable block with buttons linking to Google News (Follow), Google Discover, and preferred-source settings. Site owners control which buttons appear, their URLs, and labels.
 * Version:           1.2.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       follow-on-google
 * Domain Path:       /languages
 *
 * @package FollowOnGoogle
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the block using the metadata loaded from block.json.
 *
 * The render callback lives in a separate file for clarity and is referenced
 * from block.json via the "render" property, so no PHP callback is passed here.
 *
 * @return void
 */
function fog_register_block() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'fog_register_block' );
