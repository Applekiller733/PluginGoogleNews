<?php
/**
 * Shared configuration, sanitization, and rendering.
 *
 * Both the block (src/render.php) and the auto-insert filter
 * (includes/auto-insert.php) build a config array of the shape returned by
 * nfb_get_default_config() and pass it to nfb_render_buttons(). Keeping one
 * renderer means the two paths can never drift apart.
 *
 * @package NewsFollowButtons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required URL prefix for each button type.
 *
 * Adjust here if Google changes its URL formats. A button whose URL does not
 * begin with its prefix is never rendered on the front end.
 *
 * @return array<string,string> Map of button key to required prefix.
 */
function nfb_get_url_prefixes() {
	return array(
		'news'      => 'https://news.google.com/publications/',
		'discover'  => 'https://profile.google.com/cp/',
		'preferred' => 'https://www.google.com/preferences/source?q=',
	);
}

/**
 * Human-readable name for each button type, used in admin notices.
 *
 * @return array<string,string> Map of button key to display name.
 */
function nfb_get_button_names() {
	return array(
		'news'      => __( 'Google News', 'news-follow-buttons' ),
		'discover'  => __( 'Google Discover', 'news-follow-buttons' ),
		'preferred' => __( 'Preferred source', 'news-follow-buttons' ),
	);
}

/**
 * The canonical default configuration.
 *
 * This is the single source of truth for the config shape. Block attribute
 * defaults in block.json mirror the per-button values here.
 *
 * @return array Default config.
 */
function nfb_get_default_config() {
	return array(
		'buttons'    => array(
			'news'      => array(
				'enabled' => true,
				'url'     => '',
				'label'   => __( 'Follow on Google News', 'news-follow-buttons' ),
				'iconId'  => 0,
				'style'   => array(
					'bgColor'     => '#1a73e8',
					'textColor'   => '#ffffff',
					'fontSize'    => 15,
					'fontWeight'  => '600',
					'borderWidth' => 0,
					'borderColor' => '#1a73e8',
					'borderStyle' => 'solid',
				),
			),
			'discover'  => array(
				'enabled' => true,
				'url'     => '',
				'label'   => __( 'Follow on Google Discover', 'news-follow-buttons' ),
				'iconId'  => 0,
				'style'   => array(
					'bgColor'     => '#202124',
					'textColor'   => '#ffffff',
					'fontSize'    => 15,
					'fontWeight'  => '600',
					'borderWidth' => 0,
					'borderColor' => '#202124',
					'borderStyle' => 'solid',
				),
			),
			'preferred' => array(
				'enabled' => true,
				'url'     => '',
				'label'   => __( 'Set as preferred source', 'news-follow-buttons' ),
				'iconId'  => 0,
				'style'   => array(
					'bgColor'     => '#ffffff',
					'textColor'   => '#1a73e8',
					'fontSize'    => 15,
					'fontWeight'  => '600',
					'borderWidth' => 1,
					'borderColor' => '#dadce0',
					'borderStyle' => 'solid',
				),
			),
		),
		'layout'     => array(
			'alignment'    => 'flex-start',
			'allowWrap'    => true,
			'wrapOverflow' => 'scroll',
			'openInNewTab' => true,
		),
		'autoInsert' => array(
			'enabled'   => false,
			'position'  => 'after',
			'postTypes' => array( 'post' ),
		),
	);
}

/*
 * -------------------------------------------------------------------------
 * Value sanitizers
 * -------------------------------------------------------------------------
 */

/**
 * Sanitize a user-supplied CSS color.
 *
 * Accepts hex (3/4/6/8 digit), rgb(), rgba(), hsl(), and hsla(). Anything else
 * falls back to the supplied default, so arbitrary strings can never reach the
 * inline style attribute.
 *
 * @param mixed  $value    Raw color value.
 * @param string $fallback Safe default color.
 * @return string Sanitized color.
 */
function nfb_sanitize_color( $value, $fallback ) {
	$value = is_string( $value ) ? trim( $value ) : '';

	if ( '' === $value ) {
		return $fallback;
	}

	if ( preg_match( '/^#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
		return $value;
	}

	if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\)$/i', $value ) ) {
		return $value;
	}

	return $fallback;
}

/**
 * Clamp a value to an integer range.
 *
 * @param mixed $value    Raw value.
 * @param int   $min      Minimum allowed.
 * @param int   $max      Maximum allowed.
 * @param int   $fallback Default when non-numeric.
 * @return int Sanitized integer.
 */
function nfb_sanitize_int_range( $value, $min, $max, $fallback ) {
	if ( ! is_numeric( $value ) ) {
		return $fallback;
	}
	$value = (int) $value;
	return max( $min, min( $max, $value ) );
}

/**
 * Return a value only if it appears in an allowlist.
 *
 * @param mixed  $value    Raw value.
 * @param array  $allowed  Allowed values.
 * @param string $fallback Default.
 * @return string Sanitized value.
 */
function nfb_sanitize_allowed( $value, $allowed, $fallback ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	return in_array( $value, $allowed, true ) ? $value : $fallback;
}

/**
 * Check whether a URL begins with a required prefix.
 *
 * The comparison is anchored at the start and case-insensitive, so lookalike
 * hosts such as news.google.com.example.org are correctly rejected.
 *
 * @param string $url    URL to test.
 * @param string $prefix Required leading substring.
 * @return bool True when the URL starts with the prefix.
 */
function nfb_url_matches_prefix( $url, $prefix ) {
	$url    = is_string( $url ) ? trim( $url ) : '';
	$prefix = is_string( $prefix ) ? $prefix : '';

	if ( '' === $url || '' === $prefix ) {
		return false;
	}

	return 0 === strncasecmp( $url, $prefix, strlen( $prefix ) );
}

/**
 * Sanitize one button's style array against a set of defaults.
 *
 * @param mixed $style    Raw style values.
 * @param array $defaults Defaults for this button.
 * @return array Sanitized style array.
 */
function nfb_sanitize_style( $style, $defaults ) {
	$style = is_array( $style ) ? $style : array();

	return array(
		'bgColor'     => nfb_sanitize_color( isset( $style['bgColor'] ) ? $style['bgColor'] : '', $defaults['bgColor'] ),
		'textColor'   => nfb_sanitize_color( isset( $style['textColor'] ) ? $style['textColor'] : '', $defaults['textColor'] ),
		'fontSize'    => nfb_sanitize_int_range( isset( $style['fontSize'] ) ? $style['fontSize'] : '', 8, 72, $defaults['fontSize'] ),
		'fontWeight'  => nfb_sanitize_allowed(
			isset( $style['fontWeight'] ) ? (string) $style['fontWeight'] : '',
			array( '100', '200', '300', '400', '500', '600', '700', '800', '900', 'normal', 'bold' ),
			$defaults['fontWeight']
		),
		'borderWidth' => nfb_sanitize_int_range( isset( $style['borderWidth'] ) ? $style['borderWidth'] : '', 0, 12, $defaults['borderWidth'] ),
		'borderColor' => nfb_sanitize_color( isset( $style['borderColor'] ) ? $style['borderColor'] : '', $defaults['borderColor'] ),
		'borderStyle' => nfb_sanitize_allowed(
			isset( $style['borderStyle'] ) ? $style['borderStyle'] : '',
			array( 'solid', 'dashed', 'dotted', 'double', 'none' ),
			$defaults['borderStyle']
		),
	);
}

/**
 * Sanitize a whole config array against the defaults.
 *
 * Used both when saving the settings option and when rendering, so untrusted
 * values from either source are normalized identically.
 *
 * @param mixed $config Raw config.
 * @return array Sanitized config.
 */
function nfb_sanitize_config( $config ) {
	$defaults = nfb_get_default_config();
	$config   = is_array( $config ) ? $config : array();
	$clean    = $defaults;

	// Buttons.
	$raw_buttons = isset( $config['buttons'] ) && is_array( $config['buttons'] ) ? $config['buttons'] : array();
	foreach ( $defaults['buttons'] as $key => $button_defaults ) {
		$raw = isset( $raw_buttons[ $key ] ) && is_array( $raw_buttons[ $key ] ) ? $raw_buttons[ $key ] : array();

		$clean['buttons'][ $key ]['enabled'] = ! empty( $raw['enabled'] );
		$clean['buttons'][ $key ]['url']     = isset( $raw['url'] ) ? esc_url_raw( trim( (string) $raw['url'] ) ) : '';
		$clean['buttons'][ $key ]['label']   = isset( $raw['label'] )
			? sanitize_text_field( (string) $raw['label'] )
			: $button_defaults['label'];
		$clean['buttons'][ $key ]['iconId']  = isset( $raw['iconId'] ) ? absint( $raw['iconId'] ) : 0;
		$clean['buttons'][ $key ]['style']   = nfb_sanitize_style(
			isset( $raw['style'] ) ? $raw['style'] : array(),
			$button_defaults['style']
		);
	}

	// Layout.
	$raw_layout                    = isset( $config['layout'] ) && is_array( $config['layout'] ) ? $config['layout'] : array();
	$clean['layout']['alignment']  = nfb_sanitize_allowed(
		isset( $raw_layout['alignment'] ) ? $raw_layout['alignment'] : '',
		array( 'flex-start', 'center', 'flex-end', 'space-between' ),
		$defaults['layout']['alignment']
	);
	$clean['layout']['allowWrap']  = ! empty( $raw_layout['allowWrap'] );
	$clean['layout']['wrapOverflow'] = nfb_sanitize_allowed(
		isset( $raw_layout['wrapOverflow'] ) ? $raw_layout['wrapOverflow'] : '',
		array( 'scroll', 'shrink' ),
		$defaults['layout']['wrapOverflow']
	);
	$clean['layout']['openInNewTab'] = ! empty( $raw_layout['openInNewTab'] );

	// Auto-insert.
	$raw_auto                          = isset( $config['autoInsert'] ) && is_array( $config['autoInsert'] ) ? $config['autoInsert'] : array();
	$clean['autoInsert']['enabled']    = ! empty( $raw_auto['enabled'] );
	$clean['autoInsert']['position']   = nfb_sanitize_allowed(
		isset( $raw_auto['position'] ) ? $raw_auto['position'] : '',
		array( 'before', 'after', 'both' ),
		$defaults['autoInsert']['position']
	);

	$valid_types = get_post_types( array( 'public' => true ) );
	$raw_types   = isset( $raw_auto['postTypes'] ) && is_array( $raw_auto['postTypes'] ) ? $raw_auto['postTypes'] : array();
	$clean_types = array();
	foreach ( $raw_types as $type ) {
		$type = sanitize_key( $type );
		if ( isset( $valid_types[ $type ] ) ) {
			$clean_types[] = $type;
		}
	}
	$clean['autoInsert']['postTypes'] = $clean_types;

	return $clean;
}

/*
 * -------------------------------------------------------------------------
 * Rendering
 * -------------------------------------------------------------------------
 */

/**
 * Build a sanitized inline style string for one button.
 *
 * @param array $style Sanitized style array.
 * @return string Inline CSS declarations.
 */
function nfb_build_button_style( $style ) {
	$declarations = array(
		'background-color:' . $style['bgColor'],
		'color:' . $style['textColor'],
		'font-size:' . (int) $style['fontSize'] . 'px',
		'font-weight:' . $style['fontWeight'],
		'border-width:' . (int) $style['borderWidth'] . 'px',
		'border-color:' . $style['borderColor'],
		'border-style:' . $style['borderStyle'],
	);

	return implode( ';', $declarations ) . ';';
}

/**
 * Build the container inline style from layout options.
 *
 * @param array $layout Sanitized layout array.
 * @return string Inline CSS declarations.
 */
function nfb_build_container_style( $layout ) {
	$declarations = array(
		'justify-content:' . $layout['alignment'],
		'flex-wrap:' . ( $layout['allowWrap'] ? 'wrap' : 'nowrap' ),
	);

	// When wrapping is disabled the row can overflow a narrow container.
	// 'scroll' keeps buttons at full size and allows horizontal scrolling;
	// 'shrink' lets them compress instead.
	if ( ! $layout['allowWrap'] && 'scroll' === $layout['wrapOverflow'] ) {
		$declarations[] = 'overflow-x:auto';
	}

	return implode( ';', $declarations ) . ';';
}

/**
 * Return the markup for a button's icon.
 *
 * Uses a custom media-library image when one is set, otherwise the built-in
 * Google glyph. Custom icons are rendered through wp_get_attachment_image(),
 * which escapes the URL and dimensions for us.
 *
 * @param int $icon_id Attachment ID, or 0 for the default icon.
 * @return string Icon markup.
 */
function nfb_get_icon_markup( $icon_id ) {
	$icon_id = absint( $icon_id );

	if ( $icon_id > 0 && wp_attachment_is_image( $icon_id ) ) {
		$image = wp_get_attachment_image(
			$icon_id,
			'thumbnail',
			false,
			array(
				'class'       => 'nfb-button__icon-img',
				'alt'         => '',
				'aria-hidden' => 'true',
				'loading'     => 'lazy',
			)
		);

		if ( $image ) {
			return '<span class="nfb-button__icon" aria-hidden="true">' . $image . '</span>';
		}
	}

	return '<span class="nfb-button__icon" aria-hidden="true">'
		. '<svg viewBox="0 0 24 24" width="18" height="18" focusable="false" role="presentation">'
		. '<path fill="currentColor" d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Z" opacity=".15" />'
		. '<path fill="currentColor" d="M12 6.5a5.5 5.5 0 1 0 5.4 6.6h-5.4v-2.1h7.6c.1.5.1 1 .1 1.5 0 4.1-2.7 7-6.9 7A5.6 5.6 0 0 1 6.4 12 5.6 5.6 0 0 1 12 6.5c1.5 0 2.8.5 3.8 1.5l-1.5 1.5a3.3 3.3 0 0 0-2.3-.9Z" />'
		. '</svg></span>';
}

/**
 * Render the buttons from a config array.
 *
 * @param array  $config             Raw (unsanitized) config; sanitized here.
 * @param string $wrapper_attributes Optional pre-escaped wrapper attributes
 *                                   from get_block_wrapper_attributes().
 * @return string HTML, or an empty string when there is nothing to show.
 */
function nfb_render_buttons( $config, $wrapper_attributes = '' ) {
	$config   = nfb_sanitize_config( $config );
	$prefixes = nfb_get_url_prefixes();
	$names    = nfb_get_button_names();

	$active   = array();
	$rejected = array();

	foreach ( $config['buttons'] as $key => $button ) {
		if ( ! $button['enabled'] || '' === $button['url'] ) {
			continue;
		}

		if ( nfb_url_matches_prefix( $button['url'], $prefixes[ $key ] ) ) {
			$button['key']  = $key;
			$active[]       = $button;
		} else {
			$rejected[] = array(
				'name'   => $names[ $key ],
				'prefix' => $prefixes[ $key ],
			);
		}
	}

	// Only users who can edit content ever see the diagnostic notice.
	$is_editor = current_user_can( 'edit_posts' );

	if ( empty( $active ) && ( empty( $rejected ) || ! $is_editor ) ) {
		return '';
	}

	$container_style = nfb_build_container_style( $config['layout'] );
	$target          = $config['layout']['openInNewTab'] ? ' target="_blank" rel="noopener noreferrer"' : '';

	if ( '' === $wrapper_attributes ) {
		$wrapper_attributes = 'class="wp-block-news-follow-buttons-buttons nfb-buttons" style="'
			. esc_attr( $container_style ) . '"';
	}

	$shrink_class = ( ! $config['layout']['allowWrap'] && 'shrink' === $config['layout']['wrapOverflow'] )
		? ' is-shrink'
		: '';

	$html = '<div ' . $wrapper_attributes . '>';

	if ( $is_editor && ! empty( $rejected ) ) {
		$html .= '<div class="nfb-admin-notice" role="note"><strong>'
			. esc_html__( 'News Follow Buttons:', 'news-follow-buttons' )
			. '</strong> '
			. esc_html__( 'These buttons are hidden because their URLs are not valid Google links. Only you (as an editor) can see this notice.', 'news-follow-buttons' )
			. '<ul>';
		foreach ( $rejected as $bad ) {
			$html .= '<li>' . wp_kses(
				sprintf(
					/* translators: 1: button name, 2: required URL prefix. */
					__( '%1$s must start with %2$s', 'news-follow-buttons' ),
					'<strong>' . esc_html( $bad['name'] ) . '</strong>',
					'<code>' . esc_html( $bad['prefix'] ) . '</code>'
				),
				array(
					'strong' => array(),
					'code'   => array(),
				)
			) . '</li>';
		}
		$html .= '</ul></div>';
	}

	foreach ( $active as $button ) {
		$html .= '<a class="nfb-button is-' . esc_attr( $button['key'] ) . esc_attr( $shrink_class ) . '"'
			. ' href="' . esc_url( $button['url'] ) . '"'
			. ' style="' . esc_attr( nfb_build_button_style( $button['style'] ) ) . '"'
			. $target . '>'
			. nfb_get_icon_markup( $button['iconId'] )
			. '<span class="nfb-button__label">' . esc_html( $button['label'] ) . '</span>'
			. '</a>';
	}

	$html .= '</div>';

	return $html;
}
