(function( $ ) {
	'use strict';
	$(function() {
		var currentQuestion = 0;
		var questions = $( '.pa-question' );
		var progressBar = $( '.pa-progress-bar-inner' );
		var prevBtn = $( '.pa-prev-btn' );
		var nextBtn = $( '.pa-next-btn' );
		var submitBtn = $( '.pa-submit-btn' );

		function showQuestion( index ) {
			questions.removeClass( 'active' );
			$( questions[ index ] ).addClass( 'active' );
			updateProgressBar();
			updateNavButtons();
		}

		function updateProgressBar() {
			var progress = ( ( currentQuestion + 1 ) / questions.length ) * 100;
			progressBar.css( 'width', progress + '%' );
		}

		function updateNavButtons() {
			if ( currentQuestion === 0 ) {
				prevBtn.hide();
			} else {
				prevBtn.show();
			}

			if ( currentQuestion === questions.length - 1 ) {
				nextBtn.hide();
				submitBtn.show();
			} else {
				nextBtn.show();
				submitBtn.hide();
			}
		}

		nextBtn.on( 'click', function() {
			currentQuestion++;
			showQuestion( currentQuestion );
		});

		prevBtn.on( 'click', function() {
			currentQuestion--;
			showQuestion( currentQuestion );
		});

		submitBtn.on( 'click', function() {
			var quizId = $( '.pa-quiz-container' ).data( 'quiz-id' );
			var answers = {};

			questions.each(function() {
				var questionId = $( this ).data( 'question-id' );
				var questionType = $( this ).find( 'input' ).first().attr( 'type' );

				if ( 'checkbox' === questionType ) {
					var answer = $( this ).find( 'input:checked' ).map(function() {
						return $( this ).val();
					}).get();
					answers[ questionId ] = answer;
				} else {
					var answer = $( this ).find( 'input:checked, textarea' ).val();
					answers[ questionId ] = answer;
				}
			});

			$.ajax({
				url: pa_quiz.ajax_url,
				type: 'POST',
				data: {
					action: 'pa_submit_quiz',
					nonce: pa_quiz.nonce,
					quiz_id: quizId,
					answers: answers
				},
				success: function( response ) {
					if ( response.success ) {
						$( '.pa-quiz-container' ).html( response.data.success_message );
					}
				}
			});
		});

		showQuestion( currentQuestion );
	});
})( jQuery );
