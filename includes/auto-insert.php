<?php
/**
 * Automatic insertion of the buttons into post content.
 *
 * Uses the global settings so a site owner configures the buttons once rather
 * than editing every page.
 *
 * @package NewsFollowButtons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decide whether the current request should receive auto-inserted buttons.
 *
 * the_content runs in more places than people expect (archives, excerpts,
 * feeds, widgets, and other plugins calling apply_filters('the_content', ...)),
 * so each condition here prevents the buttons appearing somewhere unwanted.
 *
 * @param array $settings Sanitized global settings.
 * @return bool Whether to insert.
 */
function nfb_should_auto_insert( $settings ) {
	if ( empty( $settings['autoInsert']['enabled'] ) ) {
		return false;
	}

	// Only full single views: not archives, search results, or home listings.
	if ( ! is_singular() ) {
		return false;
	}

	// Only the main query's main loop, so excerpts and secondary loops are skipped.
	if ( ! in_the_loop() || ! is_main_query() ) {
		return false;
	}

	// Never in RSS/Atom output.
	if ( is_feed() ) {
		return false;
	}

	// Only the post types the site owner selected.
	$post_type = get_post_type();
	if ( ! $post_type || ! in_array( $post_type, $settings['autoInsert']['postTypes'], true ) ) {
		return false;
	}

	return true;
}

/**
 * Ensure the block's front-end stylesheet is loaded for auto-inserted buttons.
 *
 * WordPress enqueues a block's "style" asset only when it detects that block in
 * the content. Auto-inserted markup is plain HTML rather than a parsed block,
 * so nothing triggers that detection and the buttons would render unstyled.
 * Enqueuing here (rather than inside the_content) keeps the stylesheet in the
 * document head and avoids a flash of unstyled content.
 *
 * @return void
 */
function nfb_enqueue_auto_insert_style() {
	$settings = nfb_get_settings();

	if ( empty( $settings['autoInsert']['enabled'] ) || ! is_singular() ) {
		return;
	}

	$post_type = get_post_type();
	if ( ! $post_type || ! in_array( $post_type, $settings['autoInsert']['postTypes'], true ) ) {
		return;
	}

	// The handle WordPress generates for the block's "style" field.
	$handle = function_exists( 'generate_block_asset_handle' )
		? generate_block_asset_handle( 'news-follow-buttons/buttons', 'style' )
		: 'news-follow-buttons-buttons-style';

	if ( wp_style_is( $handle, 'registered' ) ) {
		wp_enqueue_style( $handle );
		return;
	}

	// Fallback: load the compiled stylesheet directly if the block handle is
	// unavailable for any reason (for example, the block failed to register).
	$relative = 'build/style-index.css';
	if ( file_exists( plugin_dir_path( NFB_PLUGIN_FILE ) . $relative ) ) {
		wp_enqueue_style(
			'nfb-buttons',
			plugins_url( $relative, NFB_PLUGIN_FILE ),
			array(),
			NFB_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nfb_enqueue_auto_insert_style' );

/**
 * Append or prepend the buttons to post content.
 *
 * @param string $content Post content.
 * @return string Filtered content.
 */
function nfb_auto_insert_buttons( $content ) {
	// Guard against recursion if something re-applies the filter mid-render.
	static $rendering = false;
	if ( $rendering ) {
		return $content;
	}

	$settings = nfb_get_settings();

	if ( ! nfb_should_auto_insert( $settings ) ) {
		return $content;
	}

	$rendering = true;
	$buttons   = nfb_render_buttons( $settings );
	$rendering = false;

	if ( '' === $buttons ) {
		return $content;
	}

	$position = $settings['autoInsert']['position'];

	if ( 'before' === $position ) {
		return $buttons . $content;
	}

	if ( 'both' === $position ) {
		return $buttons . $content . $buttons;
	}

	return $content . $buttons;
}
add_filter( 'the_content', 'nfb_auto_insert_buttons', 20 );
