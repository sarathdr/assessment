(function( $ ) {
	'use strict';

	$(function() {
		$( document ).on( 'click', '.add-answer-form .submit', function( e ) {
			e.preventDefault();

			var form = $( this ).closest( 'form' );
			var data = form.serialize();

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: data,
				success: function( response ) {
					if ( response.success ) {
						form.siblings( '.answers-list' ).append( response.data.html );
						form.find( 'input[type="text"], input[type="number"]' ).val( '' );
					}
				}
			});
		});
	});
})( jQuery );
