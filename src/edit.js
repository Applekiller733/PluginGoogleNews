/**
 * Editor component for the Follow on Google Buttons block.
 *
 * @package FollowOnGoogle
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	PanelColorSettings,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
	RangeControl,
	Notice,
	Button,
} from '@wordpress/components';

/**
 * Required URL prefix per button type. Keep in sync with render.php.
 * A URL that does not start with its prefix is rejected on the front end.
 */
const URL_PREFIXES = {
	showNews: 'https://news.google.com/publications/',
	showDiscover: 'https://profile.google.com/cp/',
	showPreferred: 'https://www.google.com/preferences/source?q=',
};

/**
 * Case-insensitive prefix test mirroring the server validator.
 *
 * @param {string} url    URL to test.
 * @param {string} prefix Required leading substring.
 * @return {boolean} Whether url starts with prefix.
 */
function urlMatchesPrefix( url, prefix ) {
	const value = typeof url === 'string' ? url.trim() : '';
	if ( ! value || ! prefix ) {
		return false;
	}
	return value.slice( 0, prefix.length ).toLowerCase() === prefix.toLowerCase();
}

const FONT_WEIGHTS = [
	{ label: __( 'Light (300)', 'follow-on-google' ), value: '300' },
	{ label: __( 'Regular (400)', 'follow-on-google' ), value: '400' },
	{ label: __( 'Medium (500)', 'follow-on-google' ), value: '500' },
	{ label: __( 'Semibold (600)', 'follow-on-google' ), value: '600' },
	{ label: __( 'Bold (700)', 'follow-on-google' ), value: '700' },
	{ label: __( 'Extrabold (800)', 'follow-on-google' ), value: '800' },
];

const BORDER_STYLES = [
	{ label: __( 'Solid', 'follow-on-google' ), value: 'solid' },
	{ label: __( 'Dashed', 'follow-on-google' ), value: 'dashed' },
	{ label: __( 'Dotted', 'follow-on-google' ), value: 'dotted' },
	{ label: __( 'Double', 'follow-on-google' ), value: 'double' },
	{ label: __( 'None', 'follow-on-google' ), value: 'none' },
];

/**
 * Media-library icon picker for a single button.
 *
 * Stores an attachment ID rather than a URL, so the image stays managed by
 * WordPress and is rendered server-side through wp_get_attachment_image().
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.iconKey       Attribute name holding the icon ID.
 * @param {number}   props.iconId        Current attachment ID (0 = default).
 * @param {Function} props.setAttributes Block setter.
 * @return {JSX.Element} Icon controls.
 */
function IconPicker( { iconKey, iconId, setAttributes } ) {
	const iconUrl = useSelect(
		( select ) => {
			if ( ! iconId ) {
				return null;
			}
			const media = select( 'core' ).getMedia( iconId );
			if ( ! media ) {
				return null;
			}
			return (
				media.media_details?.sizes?.thumbnail?.source_url ||
				media.source_url
			);
		},
		[ iconId ]
	);

	return (
		<div className="fog-icon-picker">
			<p className="fog-icon-picker__label">
				<strong>{ __( 'Icon', 'follow-on-google' ) }</strong>
			</p>
			{ iconUrl && (
				<img
					className="fog-icon-picker__preview"
					src={ iconUrl }
					alt=""
				/>
			) }
			<MediaUploadCheck>
				<MediaUpload
					onSelect={ ( media ) =>
						setAttributes( { [ iconKey ]: media.id } )
					}
					allowedTypes={ [ 'image' ] }
					value={ iconId }
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ iconId
								? __( 'Replace icon', 'follow-on-google' )
								: __( 'Choose icon', 'follow-on-google' ) }
						</Button>
					) }
				/>
			</MediaUploadCheck>
			{ !! iconId && (
				<Button
					variant="tertiary"
					isDestructive
					onClick={ () => setAttributes( { [ iconKey ]: 0 } ) }
				>
					{ __( 'Use default', 'follow-on-google' ) }
				</Button>
			) }
		</div>
	);
}

/**
 * Style controls (color, typography, border) for a single button.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.styleKey      Attribute name holding the style object.
 * @param {Object}   props.style         Current style object.
 * @param {Function} props.setAttributes Block setter.
 * @return {JSX.Element} Control group.
 */
function StyleControls( { styleKey, style = {}, setAttributes } ) {
	const set = ( field, value ) => {
		setAttributes( { [ styleKey ]: { ...style, [ field ]: value } } );
	};

	return (
		<>
			<PanelColorSettings
				title={ __( 'Colors', 'follow-on-google' ) }
				initialOpen={ false }
				colorSettings={ [
					{
						value: style.bgColor,
						onChange: ( value ) => set( 'bgColor', value ),
						label: __( 'Background', 'follow-on-google' ),
					},
					{
						value: style.textColor,
						onChange: ( value ) => set( 'textColor', value ),
						label: __( 'Text', 'follow-on-google' ),
					},
					{
						value: style.borderColor,
						onChange: ( value ) => set( 'borderColor', value ),
						label: __( 'Border', 'follow-on-google' ),
					},
				] }
			/>
			<RangeControl
				label={ __( 'Font size (px)', 'follow-on-google' ) }
				value={ style.fontSize }
				onChange={ ( value ) => set( 'fontSize', value ) }
				min={ 8 }
				max={ 72 }
			/>
			<SelectControl
				label={ __( 'Font weight', 'follow-on-google' ) }
				value={ style.fontWeight }
				options={ FONT_WEIGHTS }
				onChange={ ( value ) => set( 'fontWeight', value ) }
			/>
			<RangeControl
				label={ __( 'Border width (px)', 'follow-on-google' ) }
				value={ style.borderWidth }
				onChange={ ( value ) => set( 'borderWidth', value ) }
				min={ 0 }
				max={ 12 }
			/>
			<SelectControl
				label={ __( 'Border style', 'follow-on-google' ) }
				value={ style.borderStyle }
				options={ BORDER_STYLES }
				onChange={ ( value ) => set( 'borderStyle', value ) }
			/>
		</>
	);
}

/**
 * Full control group for one button: visibility, URL, label, and styling.
 *
 * @param {Object} props Component props.
 * @return {JSX.Element} Control group.
 */
function ButtonControls( {
	title,
	showKey,
	urlKey,
	labelKey,
	styleKey,
	iconKey,
	urlHelp,
	attributes,
	setAttributes,
} ) {
	return (
		<PanelBody title={ title } initialOpen={ false }>
			<ToggleControl
				label={ __( 'Show this button', 'follow-on-google' ) }
				checked={ attributes[ showKey ] }
				onChange={ ( value ) =>
					setAttributes( { [ showKey ]: value } )
				}
			/>
			{ attributes[ showKey ] && (
				<>
					<TextControl
						label={ __( 'URL', 'follow-on-google' ) }
						value={ attributes[ urlKey ] }
						help={ urlHelp }
						type="url"
						onChange={ ( value ) =>
							setAttributes( { [ urlKey ]: value } )
						}
					/>
					{ attributes[ urlKey ] &&
						! urlMatchesPrefix(
							attributes[ urlKey ],
							URL_PREFIXES[ showKey ]
						) && (
							<Notice
								status="warning"
								isDismissible={ false }
							>
								{ __(
									'This URL will be rejected on the front end. It must start with:',
									'follow-on-google'
								) }{ ' ' }
								<code>{ URL_PREFIXES[ showKey ] }</code>
							</Notice>
						) }
					<TextControl
						label={ __( 'Button label', 'follow-on-google' ) }
						value={ attributes[ labelKey ] }
						onChange={ ( value ) =>
							setAttributes( { [ labelKey ]: value } )
						}
					/>
					<IconPicker
						iconKey={ iconKey }
						iconId={ attributes[ iconKey ] }
						setAttributes={ setAttributes }
					/>
					<StyleControls
						styleKey={ styleKey }
						style={ attributes[ styleKey ] }
						setAttributes={ setAttributes }
					/>
				</>
			) }
		</PanelBody>
	);
}

/**
 * Build an inline style object for the editor preview.
 * Mirrors render.php; the server re-sanitizes authoritatively on output.
 *
 * @param {Object} style Style object.
 * @return {Object} React inline style.
 */
function toPreviewStyle( style = {} ) {
	return {
		backgroundColor: style.bgColor,
		color: style.textColor,
		fontSize: `${ style.fontSize || 15 }px`,
		fontWeight: style.fontWeight,
		borderWidth: `${ style.borderWidth || 0 }px`,
		borderColor: style.borderColor,
		borderStyle: style.borderStyle || 'solid',
		padding: '0.6rem 1.1rem',
		borderRadius: '6px',
		display: 'inline-flex',
		alignItems: 'center',
		gap: '0.5rem',
		textDecoration: 'none',
		lineHeight: '1.2',
	};
}

/**
 * Editor render.
 *
 * @param {Object} props Block props.
 * @return {JSX.Element} Editor markup.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { alignment, allowWrap, wrapOverflow, openInNewTab } = attributes;

	const blockProps = useBlockProps( {
		className: 'fog-buttons',
		style: {
			justifyContent: alignment,
			flexWrap: allowWrap ? 'wrap' : 'nowrap',
			overflowX:
				! allowWrap && 'scroll' === wrapOverflow ? 'auto' : undefined,
		},
	} );

	const previewButtons = [
		{
			show: attributes.showNews,
			url: attributes.newsUrl,
			prefix: URL_PREFIXES.showNews,
			label: attributes.newsLabel,
			mod: 'is-news',
			style: attributes.newsStyle,
		},
		{
			show: attributes.showDiscover,
			url: attributes.discoverUrl,
			prefix: URL_PREFIXES.showDiscover,
			label: attributes.discoverLabel,
			mod: 'is-discover',
			style: attributes.discoverStyle,
		},
		{
			show: attributes.showPreferred,
			url: attributes.preferredUrl,
			prefix: URL_PREFIXES.showPreferred,
			label: attributes.preferredLabel,
			mod: 'is-preferred',
			style: attributes.preferredStyle,
		},
	].filter(
		( b ) => b.show && urlMatchesPrefix( b.url, b.prefix )
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Layout', 'follow-on-google' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Alignment', 'follow-on-google' ) }
						value={ alignment }
						options={ [
							{
								label: __( 'Left', 'follow-on-google' ),
								value: 'flex-start',
							},
							{
								label: __( 'Center', 'follow-on-google' ),
								value: 'center',
							},
							{
								label: __( 'Right', 'follow-on-google' ),
								value: 'flex-end',
							},
							{
								label: __(
									'Spread across the row',
									'follow-on-google'
								),
								value: 'space-between',
							},
						] }
						help={ __(
							'Alignment applies to every row, so a button pushed onto a second row follows the same alignment.',
							'follow-on-google'
						) }
						onChange={ ( value ) =>
							setAttributes( { alignment: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Allow buttons to wrap onto multiple rows',
							'follow-on-google'
						) }
						checked={ allowWrap }
						help={ __(
							'Turn off to force all buttons onto a single row.',
							'follow-on-google'
						) }
						onChange={ ( value ) =>
							setAttributes( { allowWrap: value } )
						}
					/>
					{ ! allowWrap && (
						<SelectControl
							label={ __(
								'If the row overflows',
								'follow-on-google'
							) }
							value={ wrapOverflow }
							options={ [
								{
									label: __(
										'Scroll horizontally',
										'follow-on-google'
									),
									value: 'scroll',
								},
								{
									label: __(
										'Shrink buttons to fit',
										'follow-on-google'
									),
									value: 'shrink',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { wrapOverflow: value } )
							}
						/>
					) }
					<ToggleControl
						label={ __(
							'Open links in a new tab',
							'follow-on-google'
						) }
						checked={ openInNewTab }
						onChange={ ( value ) =>
							setAttributes( { openInNewTab: value } )
						}
					/>
				</PanelBody>

				<ButtonControls
					title={ __( 'Google News button', 'follow-on-google' ) }
					showKey="showNews"
					urlKey="newsUrl"
					labelKey="newsLabel"
					styleKey="newsStyle"
					iconKey="newsIconId"
					urlHelp={ __(
						'Your Google News publication URL from Publisher Center.',
						'follow-on-google'
					) }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<ButtonControls
					title={ __( 'Google Discover button', 'follow-on-google' ) }
					showKey="showDiscover"
					urlKey="discoverUrl"
					labelKey="discoverLabel"
					styleKey="discoverStyle"
					iconKey="discoverIconId"
					urlHelp={ __(
						'Google Discover has no per-site follow URL. Point this at your News publication or a help page.',
						'follow-on-google'
					) }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<ButtonControls
					title={ __(
						'Preferred source button',
						'follow-on-google'
					) }
					showKey="showPreferred"
					urlKey="preferredUrl"
					labelKey="preferredLabel"
					styleKey="preferredStyle"
					iconKey="preferredIconId"
					urlHelp={ __(
						'Preferred source is a user setting in Google Search. Link to a how-to or Google settings page.',
						'follow-on-google'
					) }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ previewButtons.length === 0 && (
					<p className="fog-empty">
						{ __(
							'No buttons to show yet. Enable a button and enter a valid Google URL in the block settings.',
							'follow-on-google'
						) }
					</p>
				) }
				{ previewButtons.map( ( button ) => (
					<span
						key={ button.mod }
						className={ `fog-button ${ button.mod }` }
						style={ toPreviewStyle( button.style ) }
					>
						<span className="fog-button__label">
							{ button.label }
						</span>
					</span>
				) ) }
			</div>
		</>
	);
}
