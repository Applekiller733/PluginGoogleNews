/**
 * Editor component for the News Follow Buttons block.
 *
 * @package NewsFollowButtons
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
	{ label: __( 'Light (300)', 'news-follow-buttons' ), value: '300' },
	{ label: __( 'Regular (400)', 'news-follow-buttons' ), value: '400' },
	{ label: __( 'Medium (500)', 'news-follow-buttons' ), value: '500' },
	{ label: __( 'Semibold (600)', 'news-follow-buttons' ), value: '600' },
	{ label: __( 'Bold (700)', 'news-follow-buttons' ), value: '700' },
	{ label: __( 'Extrabold (800)', 'news-follow-buttons' ), value: '800' },
];

const BORDER_STYLES = [
	{ label: __( 'Solid', 'news-follow-buttons' ), value: 'solid' },
	{ label: __( 'Dashed', 'news-follow-buttons' ), value: 'dashed' },
	{ label: __( 'Dotted', 'news-follow-buttons' ), value: 'dotted' },
	{ label: __( 'Double', 'news-follow-buttons' ), value: 'double' },
	{ label: __( 'None', 'news-follow-buttons' ), value: 'none' },
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
		<div className="nfb-icon-picker">
			<p className="nfb-icon-picker__label">
				<strong>{ __( 'Icon', 'news-follow-buttons' ) }</strong>
			</p>
			{ iconUrl && (
				<img
					className="nfb-icon-picker__preview"
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
								? __( 'Replace icon', 'news-follow-buttons' )
								: __( 'Choose icon', 'news-follow-buttons' ) }
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
					{ __( 'Use default', 'news-follow-buttons' ) }
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
				title={ __( 'Colors', 'news-follow-buttons' ) }
				initialOpen={ false }
				colorSettings={ [
					{
						value: style.bgColor,
						onChange: ( value ) => set( 'bgColor', value ),
						label: __( 'Background', 'news-follow-buttons' ),
					},
					{
						value: style.textColor,
						onChange: ( value ) => set( 'textColor', value ),
						label: __( 'Text', 'news-follow-buttons' ),
					},
					{
						value: style.borderColor,
						onChange: ( value ) => set( 'borderColor', value ),
						label: __( 'Border', 'news-follow-buttons' ),
					},
				] }
			/>
			<RangeControl
				label={ __( 'Font size (px)', 'news-follow-buttons' ) }
				value={ style.fontSize }
				onChange={ ( value ) => set( 'fontSize', value ) }
				min={ 8 }
				max={ 72 }
			/>
			<SelectControl
				label={ __( 'Font weight', 'news-follow-buttons' ) }
				value={ style.fontWeight }
				options={ FONT_WEIGHTS }
				onChange={ ( value ) => set( 'fontWeight', value ) }
			/>
			<RangeControl
				label={ __( 'Border width (px)', 'news-follow-buttons' ) }
				value={ style.borderWidth }
				onChange={ ( value ) => set( 'borderWidth', value ) }
				min={ 0 }
				max={ 12 }
			/>
			<SelectControl
				label={ __( 'Border style', 'news-follow-buttons' ) }
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
				label={ __( 'Show this button', 'news-follow-buttons' ) }
				checked={ attributes[ showKey ] }
				onChange={ ( value ) =>
					setAttributes( { [ showKey ]: value } )
				}
			/>
			{ attributes[ showKey ] && (
				<>
					<TextControl
						label={ __( 'URL', 'news-follow-buttons' ) }
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
									'news-follow-buttons'
								) }{ ' ' }
								<code>{ URL_PREFIXES[ showKey ] }</code>
							</Notice>
						) }
					<TextControl
						label={ __( 'Button label', 'news-follow-buttons' ) }
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
		className: 'nfb-buttons',
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
					title={ __( 'Layout', 'news-follow-buttons' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Alignment', 'news-follow-buttons' ) }
						value={ alignment }
						options={ [
							{
								label: __( 'Left', 'news-follow-buttons' ),
								value: 'flex-start',
							},
							{
								label: __( 'Center', 'news-follow-buttons' ),
								value: 'center',
							},
							{
								label: __( 'Right', 'news-follow-buttons' ),
								value: 'flex-end',
							},
							{
								label: __(
									'Spread across the row',
									'news-follow-buttons'
								),
								value: 'space-between',
							},
						] }
						help={ __(
							'Alignment applies to every row, so a button pushed onto a second row follows the same alignment.',
							'news-follow-buttons'
						) }
						onChange={ ( value ) =>
							setAttributes( { alignment: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Allow buttons to wrap onto multiple rows',
							'news-follow-buttons'
						) }
						checked={ allowWrap }
						help={ __(
							'Turn off to force all buttons onto a single row.',
							'news-follow-buttons'
						) }
						onChange={ ( value ) =>
							setAttributes( { allowWrap: value } )
						}
					/>
					{ ! allowWrap && (
						<SelectControl
							label={ __(
								'If the row overflows',
								'news-follow-buttons'
							) }
							value={ wrapOverflow }
							options={ [
								{
									label: __(
										'Scroll horizontally',
										'news-follow-buttons'
									),
									value: 'scroll',
								},
								{
									label: __(
										'Shrink buttons to fit',
										'news-follow-buttons'
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
							'news-follow-buttons'
						) }
						checked={ openInNewTab }
						onChange={ ( value ) =>
							setAttributes( { openInNewTab: value } )
						}
					/>
				</PanelBody>

				<ButtonControls
					title={ __( 'Google News button', 'news-follow-buttons' ) }
					showKey="showNews"
					urlKey="newsUrl"
					labelKey="newsLabel"
					styleKey="newsStyle"
					iconKey="newsIconId"
					urlHelp={ __(
						'Your Google News publication URL from Publisher Center.',
						'news-follow-buttons'
					) }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<ButtonControls
					title={ __( 'Google Discover button', 'news-follow-buttons' ) }
					showKey="showDiscover"
					urlKey="discoverUrl"
					labelKey="discoverLabel"
					styleKey="discoverStyle"
					iconKey="discoverIconId"
					urlHelp={ __(
						'Google Discover has no per-site follow URL. Point this at your News publication or a help page.',
						'news-follow-buttons'
					) }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<ButtonControls
					title={ __(
						'Preferred source button',
						'news-follow-buttons'
					) }
					showKey="showPreferred"
					urlKey="preferredUrl"
					labelKey="preferredLabel"
					styleKey="preferredStyle"
					iconKey="preferredIconId"
					urlHelp={ __(
						'Preferred source is a user setting in Google Search. Link to a how-to or Google settings page.',
						'news-follow-buttons'
					) }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ previewButtons.length === 0 && (
					<p className="nfb-empty">
						{ __(
							'No buttons to show yet. Enable a button and enter a valid Google URL in the block settings.',
							'news-follow-buttons'
						) }
					</p>
				) }
				{ previewButtons.map( ( button ) => (
					<span
						key={ button.mod }
						className={ `nfb-button ${ button.mod }` }
						style={ toPreviewStyle( button.style ) }
					>
						<span className="nfb-button__label">
							{ button.label }
						</span>
					</span>
				) ) }
			</div>
		</>
	);
}
