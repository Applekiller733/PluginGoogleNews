<?php
/**
 * Server-side render for the Follow on Google Buttons block.
 *
 * @package FollowOnGoogle
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'fog_sanitize_color' ) ) {
	/**
	 * Sanitize a user-supplied CSS color.
	 *
	 * Accepts hex (#fff, #ffffff, #ffffffff), rgb()/rgba(), and hsl()/hsla().
	 * Anything else falls back to the provided default. This keeps arbitrary
	 * strings out of the inline style attribute.
	 *
	 * @param mixed  $value    Raw color value.
	 * @param string $fallback Safe default color.
	 * @return string Sanitized color or the fallback.
	 */
	function fog_sanitize_color( $value, $fallback ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( '' === $value ) {
			return $fallback;
		}

		// Hex: 3, 4, 6, or 8 digits.
		if ( preg_match( '/^#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
			return $value;
		}

		// rgb() / rgba() / hsl() / hsla() with digits, commas, %, spaces, dots only.
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\)$/i', $value ) ) {
			return $value;
		}

		return $fallback;
	}
}

if ( ! function_exists( 'fog_sanitize_int_range' ) ) {
	/**
	 * Clamp an integer to a range, falling back when out of bounds or invalid.
	 *
	 * @param mixed $value    Raw value.
	 * @param int   $min      Minimum allowed.
	 * @param int   $max      Maximum allowed.
	 * @param int   $fallback Default when invalid.
	 * @return int Sanitized integer.
	 */
	function fog_sanitize_int_range( $value, $min, $max, $fallback ) {
		if ( ! is_numeric( $value ) ) {
			return $fallback;
		}
		$value = (int) $value;
		if ( $value < $min || $value > $max ) {
			return max( $min, min( $max, $value ) );
		}
		return $value;
	}
}

if ( ! function_exists( 'fog_sanitize_allowed' ) ) {
	/**
	 * Return the value only if it is in an allowlist, else the fallback.
	 *
	 * @param mixed  $value    Raw value.
	 * @param array  $allowed  Allowed values.
	 * @param string $fallback Default.
	 * @return string Sanitized value.
	 */
	function fog_sanitize_allowed( $value, $allowed, $fallback ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}

if ( ! function_exists( 'fog_build_button_style' ) ) {
	/**
	 * Build a sanitized inline style string for one button from its style object.
	 *
	 * @param array $style    Raw style attribute values.
	 * @param array $defaults Per-button default style.
	 * @return string Inline CSS (already value-sanitized; escape on output).
	 */
	function fog_build_button_style( $style, $defaults ) {
		$style = is_array( $style ) ? $style : array();

		$bg          = fog_sanitize_color( isset( $style['bgColor'] ) ? $style['bgColor'] : '', $defaults['bgColor'] );
		$text        = fog_sanitize_color( isset( $style['textColor'] ) ? $style['textColor'] : '', $defaults['textColor'] );
		$font_size   = fog_sanitize_int_range( isset( $style['fontSize'] ) ? $style['fontSize'] : '', 8, 72, $defaults['fontSize'] );
		$font_weight = fog_sanitize_allowed(
			isset( $style['fontWeight'] ) ? (string) $style['fontWeight'] : '',
			array( '100', '200', '300', '400', '500', '600', '700', '800', '900', 'normal', 'bold' ),
			$defaults['fontWeight']
		);
		$b_width = fog_sanitize_int_range( isset( $style['borderWidth'] ) ? $style['borderWidth'] : '', 0, 12, $defaults['borderWidth'] );
		$b_color = fog_sanitize_color( isset( $style['borderColor'] ) ? $style['borderColor'] : '', $defaults['borderColor'] );
		$b_style = fog_sanitize_allowed(
			isset( $style['borderStyle'] ) ? $style['borderStyle'] : '',
			array( 'solid', 'dashed', 'dotted', 'double', 'none' ),
			$defaults['borderStyle']
		);

		$declarations = array(
			'background-color:' . $bg,
			'color:' . $text,
			'font-size:' . $font_size . 'px',
			'font-weight:' . $font_weight,
			'border-width:' . $b_width . 'px',
			'border-color:' . $b_color,
			'border-style:' . $b_style,
		);

		return implode( ';', $declarations ) . ';';
	}
}

if ( ! function_exists( 'fog_url_matches_prefix' ) ) {
	/**
	 * Check whether a URL begins with an allowed prefix.
	 *
	 * Comparison is case-insensitive on the scheme/host but otherwise exact on
	 * the prefix. An empty URL or empty prefix is treated as invalid.
	 *
	 * @param string $url    The URL to test.
	 * @param string $prefix The required leading substring.
	 * @return bool True when the URL starts with the prefix.
	 */
	function fog_url_matches_prefix( $url, $prefix ) {
		$url    = is_string( $url ) ? trim( $url ) : '';
		$prefix = is_string( $prefix ) ? $prefix : '';

		if ( '' === $url || '' === $prefix ) {
			return false;
		}

		// Case-insensitive comparison of the prefix portion.
		return 0 === strncasecmp( $url, $prefix, strlen( $prefix ) );
	}
}

/**
 * Required URL prefix for each button type.
 *
 * Adjust these in one place if Google changes its URL formats. A button whose
 * saved URL does not begin with its prefix will not be rendered on the front
 * end (an admin-only notice is shown to logged-in editors instead).
 */
$fog_url_prefixes = array(
	'is-news'      => 'https://news.google.com/publications/',
	'is-discover'  => 'https://profile.google.com/cp/',
	'is-preferred' => 'https://www.google.com/preferences/source?q=',
);


$fog_style_defaults = array(
	'is-news'      => array(
		'bgColor'     => '#1a73e8',
		'textColor'   => '#ffffff',
		'fontSize'    => 15,
		'fontWeight'  => '600',
		'borderWidth' => 0,
		'borderColor' => '#1a73e8',
		'borderStyle' => 'solid',
	),
	'is-discover'  => array(
		'bgColor'     => '#202124',
		'textColor'   => '#ffffff',
		'fontSize'    => 15,
		'fontWeight'  => '600',
		'borderWidth' => 0,
		'borderColor' => '#202124',
		'borderStyle' => 'solid',
	),
	'is-preferred' => array(
		'bgColor'     => '#ffffff',
		'textColor'   => '#1a73e8',
		'fontSize'    => 15,
		'fontWeight'  => '600',
		'borderWidth' => 1,
		'borderColor' => '#dadce0',
		'borderStyle' => 'solid',
	),
);

/**
 * Build the list of buttons to render based on attributes.
 * Each entry: show flag, url, label, modifier class, and raw style object.
 */
$fog_buttons = array(
	array(
		'show'  => ! empty( $attributes['showNews'] ),
		'url'   => isset( $attributes['newsUrl'] ) ? $attributes['newsUrl'] : '',
		'label' => isset( $attributes['newsLabel'] ) ? $attributes['newsLabel'] : '',
		'mod'   => 'is-news',
		'name'  => __( 'Google News', 'follow-on-google' ),
		'style' => isset( $attributes['newsStyle'] ) ? $attributes['newsStyle'] : array(),
	),
	array(
		'show'  => ! empty( $attributes['showDiscover'] ),
		'url'   => isset( $attributes['discoverUrl'] ) ? $attributes['discoverUrl'] : '',
		'label' => isset( $attributes['discoverLabel'] ) ? $attributes['discoverLabel'] : '',
		'mod'   => 'is-discover',
		'name'  => __( 'Google Discover', 'follow-on-google' ),
		'style' => isset( $attributes['discoverStyle'] ) ? $attributes['discoverStyle'] : array(),
	),
	array(
		'show'  => ! empty( $attributes['showPreferred'] ),
		'url'   => isset( $attributes['preferredUrl'] ) ? $attributes['preferredUrl'] : '',
		'label' => isset( $attributes['preferredLabel'] ) ? $attributes['preferredLabel'] : '',
		'mod'   => 'is-preferred',
		'name'  => __( 'Preferred source', 'follow-on-google' ),
		'style' => isset( $attributes['preferredStyle'] ) ? $attributes['preferredStyle'] : array(),
	),
);

// A visitor should never see a notice; only users who can edit content should.
$fog_is_editor = function_exists( 'current_user_can' ) && current_user_can( 'edit_posts' );

// Partition the enabled buttons into those with a valid URL and those whose
// URL is set but fails prefix validation (shown only as an admin notice).
$fog_active   = array();
$fog_rejected = array();

foreach ( $fog_buttons as $fog_button ) {
	if ( ! $fog_button['show'] ) {
		continue;
	}

	$fog_url    = trim( (string) $fog_button['url'] );
	$fog_prefix = isset( $fog_url_prefixes[ $fog_button['mod'] ] ) ? $fog_url_prefixes[ $fog_button['mod'] ] : '';

	if ( '' === $fog_url ) {
		// No URL set at all: nothing to render, no error to report.
		continue;
	}

	if ( fog_url_matches_prefix( $fog_url, $fog_prefix ) ) {
		$fog_active[] = $fog_button;
	} else {
		// URL provided but does not match the required prefix: reject it.
		$fog_rejected[] = array(
			'name'   => $fog_button['name'],
			'prefix' => $fog_prefix,
		);
	}
}

// If there is nothing valid to show and no notice to display, render nothing.
if ( empty( $fog_active ) && ( empty( $fog_rejected ) || ! $fog_is_editor ) ) {
	return '';
}

$fog_alignment = isset( $attributes['alignment'] ) ? $attributes['alignment'] : 'flex-start';
$fog_new_tab   = ! empty( $attributes['openInNewTab'] );

// Allowed justify-content values, guarded against unexpected input.
$fog_allowed_alignments = array( 'flex-start', 'center', 'flex-end' );
if ( ! in_array( $fog_alignment, $fog_allowed_alignments, true ) ) {
	$fog_alignment = 'flex-start';
}

// Wrapper attributes let WordPress apply alignment, spacing, and custom classes.
$fog_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'fog-buttons',
		'style' => 'justify-content:' . esc_attr( $fog_alignment ) . ';',
	)
);

$fog_target = $fog_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
?>
<div
	<?php
	// get_block_wrapper_attributes() returns pre-escaped attribute markup.
	echo $fog_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
>
	<?php if ( $fog_is_editor && ! empty( $fog_rejected ) ) : ?>
		<div class="fog-admin-notice" role="note">
			<strong><?php echo esc_html__( 'Follow on Google Buttons:', 'follow-on-google' ); ?></strong>
			<?php echo esc_html__( 'These buttons are hidden because their URLs are not valid Google links. Only you (as an editor) can see this notice.', 'follow-on-google' ); ?>
			<ul>
				<?php foreach ( $fog_rejected as $fog_bad ) : ?>
					<li>
						<?php
						echo wp_kses(
							sprintf(
								/* translators: 1: button name, 2: required URL prefix. */
								__( '%1$s must start with %2$s', 'follow-on-google' ),
								'<strong>' . esc_html( $fog_bad['name'] ) . '</strong>',
								'<code>' . esc_html( $fog_bad['prefix'] ) . '</code>'
							),
							array(
								'strong' => array(),
								'code'   => array(),
							)
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php
	foreach ( $fog_active as $fog_button ) :
		$fog_defaults     = $fog_style_defaults[ $fog_button['mod'] ];
		$fog_button_style = fog_build_button_style( $fog_button['style'], $fog_defaults );
		?>
		<a
			class="fog-button <?php echo esc_attr( $fog_button['mod'] ); ?>"
			href="<?php echo esc_url( $fog_button['url'] ); ?>"
			style="<?php echo esc_attr( $fog_button_style ); ?>"
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, safe string.
			echo $fog_target;
			?>
		>
			<span class="fog-button__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="18" height="18" focusable="false" role="presentation">
					<path fill="currentColor" d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Z" opacity=".15" />
					<path fill="currentColor" d="M12 6.5a5.5 5.5 0 1 0 5.4 6.6h-5.4v-2.1h7.6c.1.5.1 1 .1 1.5 0 4.1-2.7 7-6.9 7A5.6 5.6 0 0 1 6.4 12 5.6 5.6 0 0 1 12 6.5c1.5 0 2.8.5 3.8 1.5l-1.5 1.5a3.3 3.3 0 0 0-2.3-.9Z" />
				</svg>
			</span>
			<span class="fog-button__label"><?php echo esc_html( $fog_button['label'] ); ?></span>
		</a>
	<?php endforeach; ?>
</div>
