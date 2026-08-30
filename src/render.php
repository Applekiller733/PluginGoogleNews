<?php
/**
 * Server-side render for the Publio Follow Buttons block.
 *
 * This file maps the block's flat attributes onto the shared config shape and
 * delegates to nfb_render_buttons(), so a block instance and an auto-inserted
 * instance produce identical markup.
 *
 * @package NewsFollowButtons
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nfb_config = array(
	'buttons' => array(
		'news'      => array(
			'enabled' => ! empty( $attributes['showNews'] ),
			'url'     => isset( $attributes['newsUrl'] ) ? $attributes['newsUrl'] : '',
			'label'   => isset( $attributes['newsLabel'] ) ? $attributes['newsLabel'] : '',
			'iconId'  => isset( $attributes['newsIconId'] ) ? $attributes['newsIconId'] : 0,
			'style'   => isset( $attributes['newsStyle'] ) ? $attributes['newsStyle'] : array(),
		),
		'discover'  => array(
			'enabled' => ! empty( $attributes['showDiscover'] ),
			'url'     => isset( $attributes['discoverUrl'] ) ? $attributes['discoverUrl'] : '',
			'label'   => isset( $attributes['discoverLabel'] ) ? $attributes['discoverLabel'] : '',
			'iconId'  => isset( $attributes['discoverIconId'] ) ? $attributes['discoverIconId'] : 0,
			'style'   => isset( $attributes['discoverStyle'] ) ? $attributes['discoverStyle'] : array(),
		),
		'preferred' => array(
			'enabled' => ! empty( $attributes['showPreferred'] ),
			'url'     => isset( $attributes['preferredUrl'] ) ? $attributes['preferredUrl'] : '',
			'label'   => isset( $attributes['preferredLabel'] ) ? $attributes['preferredLabel'] : '',
			'iconId'  => isset( $attributes['preferredIconId'] ) ? $attributes['preferredIconId'] : 0,
			'style'   => isset( $attributes['preferredStyle'] ) ? $attributes['preferredStyle'] : array(),
		),
	),
	'layout'  => array(
		'alignment'    => isset( $attributes['alignment'] ) ? $attributes['alignment'] : 'flex-start',
		'allowWrap'    => ! isset( $attributes['allowWrap'] ) || ! empty( $attributes['allowWrap'] ),
		'wrapOverflow' => isset( $attributes['wrapOverflow'] ) ? $attributes['wrapOverflow'] : 'scroll',
		'openInNewTab' => ! empty( $attributes['openInNewTab'] ),
	),
);

// Sanitize the layout first so the container style is safe to embed in the
// wrapper attributes WordPress generates for us.
$nfb_clean  = nfb_sanitize_config( $nfb_config );
$nfb_styles = nfb_build_container_style( $nfb_clean['layout'] );

$nfb_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'nfb-buttons',
		'style' => $nfb_styles,
	)
);

echo nfb_render_buttons( $nfb_config, $nfb_wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- nfb_render_buttons() escapes all output internally.
