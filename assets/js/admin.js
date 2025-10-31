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

		$( document ).on( 'click', '.delete-answer', function( e ) {
			e.preventDefault();

			if ( ! confirm( 'Are you sure you want to delete this answer?' ) ) {
				return;
			}

			var link = $( this );
			var url = new URL( link.attr( 'href' ) );
			var answerId = url.searchParams.get( 'answer_id' );
			var nonce = url.searchParams.get( '_wpnonce' );

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'pa_delete_answer',
					answer_id: answerId,
					_wpnonce: nonce
				},
				success: function( response ) {
					if ( response.success ) {
						link.closest( '.answer-item' ).remove();
					}
				}
			});
		});

		$( document ).on( 'submit', '.add-question-form', function( e ) {
			e.preventDefault();

			var form = $( this );
			var data = form.serialize() + '&action=pa_add_question';

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: data,
				success: function( response ) {
					if ( response.success ) {
						$( '.questions-list' ).append( response.data.html );
						form.find( 'input[type="text"], select' ).val( '' );
					}
				}
			});
		});
	});
})( jQuery );
