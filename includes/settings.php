<?php
/**
 * Global settings page.
 *
 * Stores one option (fog_settings) using the shared config shape from
 * includes/config.php. These settings drive the auto-insert feature, so a site
 * owner configures the buttons once instead of per page.
 *
 * @package FollowOnGoogle
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FOG_OPTION_NAME = 'fog_settings';

/**
 * Get the stored global settings, merged over defaults.
 *
 * @return array Sanitized config.
 */
function fog_get_settings() {
	$stored = get_option( FOG_OPTION_NAME, array() );
	return fog_sanitize_config( is_array( $stored ) ? $stored : array() );
}

/**
 * Register the option with the Settings API.
 *
 * @return void
 */
function fog_register_settings() {
	register_setting(
		'fog_settings_group',
		FOG_OPTION_NAME,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'fog_sanitize_config',
			'default'           => fog_get_default_config(),
		)
	);
}
add_action( 'admin_init', 'fog_register_settings' );

/**
 * Add the settings page under the Settings menu.
 *
 * @return void
 */
function fog_add_settings_page() {
	add_options_page(
		__( 'Follow on Google Buttons', 'follow-on-google' ),
		__( 'Follow on Google', 'follow-on-google' ),
		'manage_options',
		'follow-on-google',
		'fog_render_settings_page'
	);
}
add_action( 'admin_menu', 'fog_add_settings_page' );

/**
 * Enqueue the media picker and admin script on our settings page only.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function fog_enqueue_admin_assets( $hook ) {
	if ( 'settings_page_follow-on-google' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'fog-admin',
		plugins_url( 'admin/settings.js', FOG_PLUGIN_FILE ),
		array( 'jquery' ),
		FOG_VERSION,
		true
	);

	wp_enqueue_style(
		'fog-admin',
		plugins_url( 'admin/settings.css', FOG_PLUGIN_FILE ),
		array(),
		FOG_VERSION
	);

	wp_localize_script(
		'fog-admin',
		'fogAdminL10n',
		array(
			'chooseIcon'  => __( 'Choose icon', 'follow-on-google' ),
			'useIcon'     => __( 'Use this icon', 'follow-on-google' ),
			'defaultIcon' => __( 'Default Google icon', 'follow-on-google' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'fog_enqueue_admin_assets' );

/**
 * Render one button's fieldset on the settings page.
 *
 * @param string $key      Button key (news|discover|preferred).
 * @param string $title    Section heading.
 * @param array  $button   Current values.
 * @param string $prefix   Required URL prefix, shown as help text.
 * @return void
 */
function fog_render_button_fields( $key, $title, $button, $prefix ) {
	$name_base = FOG_OPTION_NAME . '[buttons][' . $key . ']';
	$icon_id   = absint( $button['iconId'] );
	$icon_url  = $icon_id ? wp_get_attachment_image_url( $icon_id, 'thumbnail' ) : '';
	?>
	<fieldset class="fog-button-fieldset">
		<h3><?php echo esc_html( $title ); ?></h3>

		<p>
			<label>
				<input type="checkbox"
					name="<?php echo esc_attr( $name_base ); ?>[enabled]"
					value="1" <?php checked( $button['enabled'] ); ?> />
				<?php esc_html_e( 'Show this button', 'follow-on-google' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $key ); ?>-url"><strong><?php esc_html_e( 'URL', 'follow-on-google' ); ?></strong></label><br />
			<input type="url" class="regular-text" id="<?php echo esc_attr( $key ); ?>-url"
				name="<?php echo esc_attr( $name_base ); ?>[url]"
				value="<?php echo esc_attr( $button['url'] ); ?>" /><br />
			<span class="description">
				<?php
				printf(
					/* translators: %s: required URL prefix. */
					esc_html__( 'Must start with %s', 'follow-on-google' ),
					'<code>' . esc_html( $prefix ) . '</code>'
				);
				?>
			</span>
		</p>

		<p>
			<label for="<?php echo esc_attr( $key ); ?>-label"><strong><?php esc_html_e( 'Button label', 'follow-on-google' ); ?></strong></label><br />
			<input type="text" class="regular-text" id="<?php echo esc_attr( $key ); ?>-label"
				name="<?php echo esc_attr( $name_base ); ?>[label]"
				value="<?php echo esc_attr( $button['label'] ); ?>" />
		</p>

		<p class="fog-icon-field">
			<strong><?php esc_html_e( 'Icon', 'follow-on-google' ); ?></strong><br />
			<span class="fog-icon-preview">
				<?php if ( $icon_url ) : ?>
					<img src="<?php echo esc_url( $icon_url ); ?>" alt="" />
				<?php else : ?>
					<em><?php esc_html_e( 'Default Google icon', 'follow-on-google' ); ?></em>
				<?php endif; ?>
			</span>
			<input type="hidden" class="fog-icon-id"
				name="<?php echo esc_attr( $name_base ); ?>[iconId]"
				value="<?php echo esc_attr( $icon_id ); ?>" />
			<button type="button" class="button fog-choose-icon"><?php esc_html_e( 'Choose icon', 'follow-on-google' ); ?></button>
			<button type="button" class="button fog-clear-icon"><?php esc_html_e( 'Use default', 'follow-on-google' ); ?></button>
		</p>

		<?php
		$style_fields = array(
			'bgColor'     => __( 'Background color', 'follow-on-google' ),
			'textColor'   => __( 'Text color', 'follow-on-google' ),
			'borderColor' => __( 'Border color', 'follow-on-google' ),
		);
		?>
		<div class="fog-style-grid">
			<?php foreach ( $style_fields as $field => $field_label ) : ?>
				<p>
					<label><strong><?php echo esc_html( $field_label ); ?></strong></label><br />
					<input type="text" class="fog-color-input"
						name="<?php echo esc_attr( $name_base ); ?>[style][<?php echo esc_attr( $field ); ?>]"
						value="<?php echo esc_attr( $button['style'][ $field ] ); ?>"
						placeholder="#000000" />
				</p>
			<?php endforeach; ?>

			<p>
				<label><strong><?php esc_html_e( 'Font size (px)', 'follow-on-google' ); ?></strong></label><br />
				<input type="number" min="8" max="72"
					name="<?php echo esc_attr( $name_base ); ?>[style][fontSize]"
					value="<?php echo esc_attr( $button['style']['fontSize'] ); ?>" />
			</p>

			<p>
				<label><strong><?php esc_html_e( 'Font weight', 'follow-on-google' ); ?></strong></label><br />
				<select name="<?php echo esc_attr( $name_base ); ?>[style][fontWeight]">
					<?php foreach ( array( '300', '400', '500', '600', '700', '800' ) as $weight ) : ?>
						<option value="<?php echo esc_attr( $weight ); ?>" <?php selected( $button['style']['fontWeight'], $weight ); ?>>
							<?php echo esc_html( $weight ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label><strong><?php esc_html_e( 'Border width (px)', 'follow-on-google' ); ?></strong></label><br />
				<input type="number" min="0" max="12"
					name="<?php echo esc_attr( $name_base ); ?>[style][borderWidth]"
					value="<?php echo esc_attr( $button['style']['borderWidth'] ); ?>" />
			</p>

			<p>
				<label><strong><?php esc_html_e( 'Border style', 'follow-on-google' ); ?></strong></label><br />
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
function fog_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = fog_get_settings();
	$prefixes = fog_get_url_prefixes();
	$names    = fog_get_button_names();
	?>
	<div class="wrap fog-settings">
		<h1><?php esc_html_e( 'Follow on Google Buttons', 'follow-on-google' ); ?></h1>

		<p class="description">
			<?php esc_html_e( 'These settings control the buttons that are inserted automatically. Blocks you place manually keep their own settings.', 'follow-on-google' ); ?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'fog_settings_group' ); ?>

			<h2><?php esc_html_e( 'Automatic insertion', 'follow-on-google' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable', 'follow-on-google' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( FOG_OPTION_NAME ); ?>[autoInsert][enabled]"
								value="1" <?php checked( $settings['autoInsert']['enabled'] ); ?> />
							<?php esc_html_e( 'Automatically add the buttons to content', 'follow-on-google' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Position', 'follow-on-google' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( FOG_OPTION_NAME ); ?>[autoInsert][position]">
							<option value="before" <?php selected( $settings['autoInsert']['position'], 'before' ); ?>>
								<?php esc_html_e( 'Before the content', 'follow-on-google' ); ?>
							</option>
							<option value="after" <?php selected( $settings['autoInsert']['position'], 'after' ); ?>>
								<?php esc_html_e( 'After the content', 'follow-on-google' ); ?>
							</option>
							<option value="both" <?php selected( $settings['autoInsert']['position'], 'both' ); ?>>
								<?php esc_html_e( 'Both before and after', 'follow-on-google' ); ?>
							</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Apply to', 'follow-on-google' ); ?></th>
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
									name="<?php echo esc_attr( FOG_OPTION_NAME ); ?>[autoInsert][postTypes][]"
									value="<?php echo esc_attr( $type->name ); ?>"
									<?php checked( in_array( $type->name, $settings['autoInsert']['postTypes'], true ) ); ?> />
								<?php echo esc_html( $type->labels->name ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Layout', 'follow-on-google' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Horizontal alignment', 'follow-on-google' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( FOG_OPTION_NAME ); ?>[layout][alignment]">
							<?php
							$alignments = array(
								'flex-start'    => __( 'Left', 'follow-on-google' ),
								'center'        => __( 'Center', 'follow-on-google' ),
								'flex-end'      => __( 'Right', 'follow-on-google' ),
								'space-between' => __( 'Spread across the row', 'follow-on-google' ),
							);
							foreach ( $alignments as $value => $align_label ) :
								?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['layout']['alignment'], $value ); ?>>
									<?php echo esc_html( $align_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Alignment applies to every row, so a button pushed onto a second row follows the same alignment.', 'follow-on-google' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Wrapping', 'follow-on-google' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( FOG_OPTION_NAME ); ?>[layout][allowWrap]"
								value="1" <?php checked( $settings['layout']['allowWrap'] ); ?> />
							<?php esc_html_e( 'Allow buttons to wrap onto multiple rows', 'follow-on-google' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Uncheck to force all buttons onto a single row.', 'follow-on-google' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'If a single row overflows', 'follow-on-google' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( FOG_OPTION_NAME ); ?>[layout][wrapOverflow]">
							<option value="scroll" <?php selected( $settings['layout']['wrapOverflow'], 'scroll' ); ?>>
								<?php esc_html_e( 'Keep full size and scroll horizontally', 'follow-on-google' ); ?>
							</option>
							<option value="shrink" <?php selected( $settings['layout']['wrapOverflow'], 'shrink' ); ?>>
								<?php esc_html_e( 'Shrink the buttons to fit', 'follow-on-google' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Only applies when wrapping is turned off.', 'follow-on-google' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Links', 'follow-on-google' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( FOG_OPTION_NAME ); ?>[layout][openInNewTab]"
								value="1" <?php checked( $settings['layout']['openInNewTab'] ); ?> />
							<?php esc_html_e( 'Open links in a new tab', 'follow-on-google' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Buttons', 'follow-on-google' ); ?></h2>
			<?php
			foreach ( array( 'news', 'discover', 'preferred' ) as $key ) {
				fog_render_button_fields( $key, $names[ $key ], $settings['buttons'][ $key ], $prefixes[ $key ] );
			}
			?>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
