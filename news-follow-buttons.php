<?php
/**
 * Plugin Name:       News Follow Buttons
 * Plugin URI:        https://github.com/Applekiller733/news-follow-buttons
 * Description:       A customizable block with buttons linking to Google News (Follow), Google Discover, and preferred-source settings. Buttons can also be added to every page automatically from one global setting.
 * Version:           1.3.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Popa Zelu Andrei
 * Author URI:        https://github.com/Applekiller733
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       news-follow-buttons
 * Domain Path:       /languages
 *
 * @package NewsFollowButtons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NFB_VERSION', '1.3.0' );
define( 'NFB_PLUGIN_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'includes/config.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/auto-insert.php';

/**
 * Loads the plugin text domain.
 *
 * Plugins hosted on WordPress.org receive translations automatically from
 * translate.wordpress.org, but this also picks up any .mo files bundled in
 * /languages (useful before language packs exist, or for private builds).
 *
 * Must run on init: WordPress 6.7+ warns when translations are loaded earlier.
 *
 * @return void
 */
function nfb_load_textdomain() {
	load_plugin_textdomain(
		'news-follow-buttons',
		false,
		dirname( plugin_basename( NFB_PLUGIN_FILE ) ) . '/languages'
	);
}
add_action( 'init', 'nfb_load_textdomain' );

/**
 * Registers the block using the metadata loaded from block.json.
 *
 * The render callback lives in src/render.php and is referenced from
 * block.json via the "render" property, so no callback is passed here.
 *
 * @return void
 */
function nfb_register_block() {
	register_block_type( __DIR__ . '/build' );

	/*
	 * Strings inside the editor JavaScript are translated separately from PHP.
	 * Without this call the sidebar labels stay in English even when a
	 * translation exists, because WordPress cannot know which script uses
	 * which text domain.
	 */
	$handle = function_exists( 'generate_block_asset_handle' )
		? generate_block_asset_handle( 'news-follow-buttons/buttons', 'editorScript' )
		: 'news-follow-buttons-buttons-editor-script';

	wp_set_script_translations(
		$handle,
		'news-follow-buttons',
		plugin_dir_path( NFB_PLUGIN_FILE ) . 'languages'
	);
}
add_action( 'init', 'nfb_register_block' );

/**
 * Adds a Settings link on the Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array Modified links.
 */
function nfb_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=news-follow-buttons' ) ),
		esc_html__( 'Settings', 'news-follow-buttons' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'nfb_plugin_action_links' );
