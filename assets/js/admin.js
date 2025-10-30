(function( $ ) {
	'use strict';

	$(function() {
		$( document ).on( 'submit', '.add-answer-form', function( e ) {
			e.preventDefault();

			var form = $( this );
			var data = form.serialize() + '&action=pa_add_answer';

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
