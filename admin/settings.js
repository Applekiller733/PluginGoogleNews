/**
 * Settings page: media library icon picker.
 *
 * @package FollowOnGoogle
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var frame = null;

		$( '.fog-choose-icon' ).on( 'click', function ( event ) {
			event.preventDefault();

			var $field = $( this ).closest( '.fog-icon-field' );

			// A new frame per field keeps the selection scoped correctly.
			frame = wp.media( {
				title: window.fogAdminL10n
					? window.fogAdminL10n.chooseIcon
					: 'Choose icon',
				button: {
					text: window.fogAdminL10n
						? window.fogAdminL10n.useIcon
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

				$field.find( '.fog-icon-id' ).val( attachment.id );
				$field
					.find( '.fog-icon-preview' )
					.html( $( '<img>' ).attr( { src: thumb, alt: '' } ) );
			} );

			frame.open();
		} );

		$( '.fog-clear-icon' ).on( 'click', function ( event ) {
			event.preventDefault();

			var $field = $( this ).closest( '.fog-icon-field' );
			$field.find( '.fog-icon-id' ).val( 0 );
			$field
				.find( '.fog-icon-preview' )
				.html(
					$( '<em>' ).text(
						window.fogAdminL10n
							? window.fogAdminL10n.defaultIcon
							: 'Default Google icon'
					)
				);
		} );
	} );
} )( jQuery );
