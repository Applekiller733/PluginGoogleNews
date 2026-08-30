<?php
/**
 * Global settings page.
 *
 * Stores one option (nfb_settings) using the shared config shape from
 * includes/config.php. These settings drive the auto-insert feature, so a site
 * owner configures the buttons once instead of per page.
 *
 * @package NewsFollowButtons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NFB_OPTION_NAME = 'nfb_settings';

/**
 * Get the stored global settings, merged over defaults.
 *
 * @return array Sanitized config.
 */
function nfb_get_settings() {
	$stored = get_option( NFB_OPTION_NAME, array() );
	return nfb_sanitize_config( is_array( $stored ) ? $stored : array() );
}

/**
 * Register the option with the Settings API.
 *
 * @return void
 */
function nfb_register_settings() {
	register_setting(
		'nfb_settings_group',
		NFB_OPTION_NAME,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nfb_sanitize_config',
			'default'           => nfb_get_default_config(),
		)
	);
}
add_action( 'admin_init', 'nfb_register_settings' );

/**
 * Add the settings page under the Settings menu.
 *
 * @return void
 */
function nfb_add_settings_page() {
	add_options_page(
		__( 'Publio Follow Buttons', 'publio-follow-buttons-google-news-discover' ),
		__( 'Publio Follow', 'publio-follow-buttons-google-news-discover' ),
		'manage_options',
		'publio-follow-buttons-google-news-discover',
		'nfb_render_settings_page'
	);
}
add_action( 'admin_menu', 'nfb_add_settings_page' );

/**
 * Enqueue the media picker and admin script on our settings page only.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function nfb_enqueue_admin_assets( $hook ) {
	if ( 'settings_page_publio-follow-buttons-google-news-discover' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'nfb-admin',
		plugins_url( 'admin/settings.js', NFB_PLUGIN_FILE ),
		array( 'jquery', 'wp-color-picker' ),
		NFB_VERSION,
		true
	);

	wp_enqueue_style(
		'nfb-admin',
		plugins_url( 'admin/settings.css', NFB_PLUGIN_FILE ),
		array( 'wp-color-picker' ),
		NFB_VERSION
	);

	wp_localize_script(
		'nfb-admin',
		'nfbAdminL10n',
		array(
			'chooseIcon'  => __( 'Choose icon', 'publio-follow-buttons-google-news-discover' ),
			'useIcon'     => __( 'Use this icon', 'publio-follow-buttons-google-news-discover' ),
			'defaultIcon' => __( 'Default Google icon', 'publio-follow-buttons-google-news-discover' ),
			'palette'     => nfb_get_color_palette(),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'nfb_enqueue_admin_assets' );

/**
 * Swatches offered under every color picker.
 *
 * The first four are the palette the default buttons are built from, so the
 * common tweaks are one click away.
 *
 * @return string[] Hex colors.
 */
function nfb_get_color_palette() {
	return array(
		'#1a73e8',
		'#202124',
		'#ffffff',
		'#dadce0',
		'#f8f9fa',
		'#34a853',
		'#ea4335',
		'#fbbc04',
	);
}

/**
 * Render one button's fieldset on the settings page.
 *
 * @param string $key      Button key (news|discover|preferred).
 * @param string $title    Section heading.
 * @param array  $button   Current values.
 * @param string $prefix   Required URL prefix, shown as help text.
 * @return void
 */
function nfb_render_button_fields( $key, $title, $button, $prefix ) {
	$name_base = NFB_OPTION_NAME . '[buttons][' . $key . ']';
	$icon_id   = absint( $button['iconId'] );
	$icon_url  = $icon_id ? wp_get_attachment_image_url( $icon_id, 'thumbnail' ) : '';
	?>
	<fieldset class="nfb-button-fieldset">
		<h3><?php echo esc_html( $title ); ?></h3>

		<p>
			<label>
				<input type="checkbox"
					name="<?php echo esc_attr( $name_base ); ?>[enabled]"
					value="1" <?php checked( $button['enabled'] ); ?> />
				<?php esc_html_e( 'Show this button', 'publio-follow-buttons-google-news-discover' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $key ); ?>-url"><strong><?php esc_html_e( 'URL', 'publio-follow-buttons-google-news-discover' ); ?></strong></label><br />
			<input type="url" class="regular-text" id="<?php echo esc_attr( $key ); ?>-url"
				name="<?php echo esc_attr( $name_base ); ?>[url]"
				value="<?php echo esc_attr( $button['url'] ); ?>" /><br />
			<span class="description">
				<?php
				printf(
					/* translators: %s: required URL prefix. */
					esc_html__( 'Must start with %s', 'publio-follow-buttons-google-news-discover' ),
					'<code>' . esc_html( $prefix ) . '</code>'
				);
				?>
			</span>
		</p>

		<p>
			<label for="<?php echo esc_attr( $key ); ?>-label"><strong><?php esc_html_e( 'Button label', 'publio-follow-buttons-google-news-discover' ); ?></strong></label><br />
			<input type="text" class="regular-text" id="<?php echo esc_attr( $key ); ?>-label"
				name="<?php echo esc_attr( $name_base ); ?>[label]"
				value="<?php echo esc_attr( $button['label'] ); ?>" />
		</p>

		<p class="nfb-icon-field">
			<strong><?php esc_html_e( 'Icon', 'publio-follow-buttons-google-news-discover' ); ?></strong><br />
			<span class="nfb-icon-preview">
				<?php if ( $icon_url ) : ?>
					<img src="<?php echo esc_url( $icon_url ); ?>" alt="" />
				<?php else : ?>
					<em><?php esc_html_e( 'Default Google icon', 'publio-follow-buttons-google-news-discover' ); ?></em>
				<?php endif; ?>
			</span>
			<input type="hidden" class="nfb-icon-id"
				name="<?php echo esc_attr( $name_base ); ?>[iconId]"
				value="<?php echo esc_attr( $icon_id ); ?>" />
			<button type="button" class="button nfb-choose-icon"><?php esc_html_e( 'Choose icon', 'publio-follow-buttons-google-news-discover' ); ?></button>
			<button type="button" class="button nfb-clear-icon"><?php esc_html_e( 'Use default', 'publio-follow-buttons-google-news-discover' ); ?></button>
		</p>

		<?php
		$style_fields = array(
			'bgColor'     => __( 'Background color', 'publio-follow-buttons-google-news-discover' ),
			'textColor'   => __( 'Text color', 'publio-follow-buttons-google-news-discover' ),
			'borderColor' => __( 'Border color', 'publio-follow-buttons-google-news-discover' ),
		);

		// Feeds each picker's "Default" button with this button's own defaults.
		$default_config = nfb_get_default_config();
		$default_style  = $default_config['buttons'][ $key ]['style'];
		?>
		<div class="nfb-style-grid">
			<?php
			foreach ( $style_fields as $field => $field_label ) :
				$field_id = $key . '-' . strtolower( $field );
				?>
				<p class="nfb-color-field">
					<label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $field_label ); ?></strong></label><br />
					<input type="text" class="nfb-color-input" id="<?php echo esc_attr( $field_id ); ?>"
						name="<?php echo esc_attr( $name_base ); ?>[style][<?php echo esc_attr( $field ); ?>]"
						value="<?php echo esc_attr( $button['style'][ $field ] ); ?>"
						data-default-color="<?php echo esc_attr( $default_style[ $field ] ); ?>"
						placeholder="#000000" />
				</p>
			<?php endforeach; ?>

			<p>
				<label><strong><?php esc_html_e( 'Font size (px)', 'publio-follow-buttons-google-news-discover' ); ?></strong></label><br />
				<input type="number" min="8" max="72"
					name="<?php echo esc_attr( $name_base ); ?>[style][fontSize]"
					value="<?php echo esc_attr( $button['style']['fontSize'] ); ?>" />
			</p>

			<p>
				<label><strong><?php esc_html_e( 'Font weight', 'publio-follow-buttons-google-news-discover' ); ?></strong></label><br />
				<select name="<?php echo esc_attr( $name_base ); ?>[style][fontWeight]">
					<?php foreach ( array( '300', '400', '500', '600', '700', '800' ) as $weight ) : ?>
						<option value="<?php echo esc_attr( $weight ); ?>" <?php selected( $button['style']['fontWeight'], $weight ); ?>>
							<?php echo esc_html( $weight ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label><strong><?php esc_html_e( 'Border width (px)', 'publio-follow-buttons-google-news-discover' ); ?></strong></label><br />
				<input type="number" min="0" max="12"
					name="<?php echo esc_attr( $name_base ); ?>[style][borderWidth]"
					value="<?php echo esc_attr( $button['style']['borderWidth'] ); ?>" />
			</p>

			<p>
				<label><strong><?php esc_html_e( 'Border style', 'publio-follow-buttons-google-news-discover' ); ?></strong></label><br />
				<select name="<?php echo esc_attr( $name_base ); ?>[style][borderStyle]">
					<?php foreach ( array( 'solid', 'dashed', 'dotted', 'double', 'none' ) as $bstyle ) : ?>
						<option value="<?php echo esc_attr( $bstyle ); ?>" <?php selected( $button['style']['borderStyle'], $bstyle ); ?>>
							<?php echo esc_html( $bstyle ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>
	</fieldset>
	<?php
}

/**
 * Render the settings page.
 *
 * @return void
 */
function nfb_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = nfb_get_settings();
	$prefixes = nfb_get_url_prefixes();
	$names    = nfb_get_button_names();
	?>
	<div class="wrap nfb-settings">
		<h1><?php esc_html_e( 'Publio Follow Buttons', 'publio-follow-buttons-google-news-discover' ); ?></h1>

		<p class="description">
			<?php esc_html_e( 'These settings control the buttons that are inserted automatically. Blocks you place manually keep their own settings.', 'publio-follow-buttons-google-news-discover' ); ?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'nfb_settings_group' ); ?>

			<h2><?php esc_html_e( 'Automatic insertion', 'publio-follow-buttons-google-news-discover' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable', 'publio-follow-buttons-google-news-discover' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( NFB_OPTION_NAME ); ?>[autoInsert][enabled]"
								value="1" <?php checked( $settings['autoInsert']['enabled'] ); ?> />
							<?php esc_html_e( 'Automatically add the buttons to content', 'publio-follow-buttons-google-news-discover' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Position', 'publio-follow-buttons-google-news-discover' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( NFB_OPTION_NAME ); ?>[autoInsert][position]">
							<option value="before" <?php selected( $settings['autoInsert']['position'], 'before' ); ?>>
								<?php esc_html_e( 'Before the content', 'publio-follow-buttons-google-news-discover' ); ?>
							</option>
							<option value="after" <?php selected( $settings['autoInsert']['position'], 'after' ); ?>>
								<?php esc_html_e( 'After the content', 'publio-follow-buttons-google-news-discover' ); ?>
							</option>
							<option value="both" <?php selected( $settings['autoInsert']['position'], 'both' ); ?>>
								<?php esc_html_e( 'Both before and after', 'publio-follow-buttons-google-news-discover' ); ?>
							</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Apply to', 'publio-follow-buttons-google-news-discover' ); ?></th>
					<td>
						<?php
						$public_types = get_post_types( array( 'public' => true ), 'objects' );
						foreach ( $public_types as $type ) :
							if ( 'attachment' === $type->name ) {
								continue;
							}
							?>
							<label style="display:block;margin-bottom:4px;">
								<input type="checkbox"
									name="<?php echo esc_attr( NFB_OPTION_NAME ); ?>[autoInsert][postTypes][]"
									value="<?php echo esc_attr( $type->name ); ?>"
									<?php checked( in_array( $type->name, $settings['autoInsert']['postTypes'], true ) ); ?> />
								<?php echo esc_html( $type->labels->name ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Layout', 'publio-follow-buttons-google-news-discover' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Horizontal alignment', 'publio-follow-buttons-google-news-discover' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( NFB_OPTION_NAME ); ?>[layout][alignment]">
							<?php
							$alignments = array(
								'flex-start'    => __( 'Left', 'publio-follow-buttons-google-news-discover' ),
								'center'        => __( 'Center', 'publio-follow-buttons-google-news-discover' ),
								'flex-end'      => __( 'Right', 'publio-follow-buttons-google-news-discover' ),
								'space-between' => __( 'Spread across the row', 'publio-follow-buttons-google-news-discover' ),
							);
							foreach ( $alignments as $value => $align_label ) :
								?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['layout']['alignment'], $value ); ?>>
									<?php echo esc_html( $align_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Alignment applies to every row, so a button pushed onto a second row follows the same alignment.', 'publio-follow-buttons-google-news-discover' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Wrapping', 'publio-follow-buttons-google-news-discover' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( NFB_OPTION_NAME ); ?>[layout][allowWrap]"
								value="1" <?php checked( $settings['layout']['allowWrap'] ); ?> />
							<?php esc_html_e( 'Allow buttons to wrap onto multiple rows', 'publio-follow-buttons-google-news-discover' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Uncheck to force all buttons onto a single row.', 'publio-follow-buttons-google-news-discover' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'If a single row overflows', 'publio-follow-buttons-google-news-discover' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( NFB_OPTION_NAME ); ?>[layout][wrapOverflow]">
							<option value="scroll" <?php selected( $settings['layout']['wrapOverflow'], 'scroll' ); ?>>
								<?php esc_html_e( 'Keep full size and scroll horizontally', 'publio-follow-buttons-google-news-discover' ); ?>
							</option>
							<option value="shrink" <?php selected( $settings['layout']['wrapOverflow'], 'shrink' ); ?>>
								<?php esc_html_e( 'Shrink the buttons to fit', 'publio-follow-buttons-google-news-discover' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Only applies when wrapping is turned off.', 'publio-follow-buttons-google-news-discover' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Links', 'publio-follow-buttons-google-news-discover' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( NFB_OPTION_NAME ); ?>[layout][openInNewTab]"
								value="1" <?php checked( $settings['layout']['openInNewTab'] ); ?> />
							<?php esc_html_e( 'Open links in a new tab', 'publio-follow-buttons-google-news-discover' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Buttons', 'publio-follow-buttons-google-news-discover' ); ?></h2>
			<?php
			foreach ( array( 'news', 'discover', 'preferred' ) as $key ) {
				nfb_render_button_fields( $key, $names[ $key ], $settings['buttons'][ $key ], $prefixes[ $key ] );
			}
			?>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
