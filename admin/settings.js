/**
 * Settings page: media library icon picker and color pickers.
 *
 * @package NewsFollowButtons
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var frame = null;

		// Swap the hex text fields for the core (Iris) color picker. Each input
		// carries data-default-color, which wpColorPicker reads off the element
		// to power its "Default" button.
		if ( $.fn.wpColorPicker ) {
			$( '.nfb-color-input' ).wpColorPicker( {
				palettes:
					window.nfbAdminL10n && window.nfbAdminL10n.palette
						? window.nfbAdminL10n.palette
						: true,
			} );
		}

		$( '.nfb-choose-icon' ).on( 'click', function ( event ) {
			event.preventDefault();

			var $field = $( this ).closest( '.nfb-icon-field' );

			// A new frame per field keeps the selection scoped correctly.
			frame = wp.media( {
				title: window.nfbAdminL10n
					? window.nfbAdminL10n.chooseIcon
					: 'Choose icon',
				button: {
					text: window.nfbAdminL10n
						? window.nfbAdminL10n.useIcon
						: 'Use this icon',
				},
				library: { type: 'image' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame
					.state()
					.get( 'selection' )
					.first()
					.toJSON();

				var thumb =
					attachment.sizes && attachment.sizes.thumbnail
						? attachment.sizes.thumbnail.url
						: attachment.url;

				$field.find( '.nfb-icon-id' ).val( attachment.id );
				$field
					.find( '.nfb-icon-preview' )
					.html( $( '<img>' ).attr( { src: thumb, alt: '' } ) );
			} );

			frame.open();
		} );

		$( '.nfb-clear-icon' ).on( 'click', function ( event ) {
			event.preventDefault();

			var $field = $( this ).closest( '.nfb-icon-field' );
			$field.find( '.nfb-icon-id' ).val( 0 );
			$field
				.find( '.nfb-icon-preview' )
				.html(
					$( '<em>' ).text(
						window.nfbAdminL10n
							? window.nfbAdminL10n.defaultIcon
							: 'Default Google icon'
					)
				);
		} );
	} );
} )( jQuery );
