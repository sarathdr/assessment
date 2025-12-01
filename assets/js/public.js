(function( $ ) {
	'use strict';
	$(function() {
		var currentQuestion = 0;
		var questions = $( '.pa-question' );
		var steps = $( '.pa-step' );
		var progressBar = $( '.pa-progress-bar-inner' );
		var prevBtn = $( '.pa-prev-btn' );
		var nextBtn = $( '.pa-next-btn' );
		var submitBtn = $( '.pa-submit-btn' );

		function showQuestion( index ) {
			// Ensure index is within bounds
			if ( index < 0 ) index = 0;
			if ( index >= questions.length ) index = questions.length - 1;
			
			currentQuestion = index;

			questions.removeClass( 'active' );
			questions.eq( index ).addClass( 'active' );
			
			// Update steps active state
			steps.removeClass( 'current' );
			steps.eq( index ).addClass( 'current' );

			updatePagination( index );
			updateNavButtons();
		}

		function updatePagination( currentIndex ) {
			// Show all steps
			steps.css( 'display', 'flex' );
			
			// Remove any leftover ellipses if they exist (cleanup)
			$( '.pa-ellipsis' ).remove();
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

		// Review Button Click
		$( '.pa-btn-review' ).on( 'click', function() {
			steps.eq( currentQuestion ).addClass( 'review' );
		});

		// Check if question is answered
		function isQuestionAnswered( index ) {
			var question = questions.eq( index );
			var type = question.find( 'input' ).first().attr( 'type' );
			
			if ( 'radio' === type || 'checkbox' === type ) {
				return question.find( 'input:checked' ).length > 0;
			} else {
				return question.find( 'textarea, input[type="text"]' ).val().trim() !== '';
			}
		}

		// Handle Input Change (Mark as Answered)
		questions.on( 'change', 'input, textarea', function() {
			if ( isQuestionAnswered( currentQuestion ) ) {
				steps.eq( currentQuestion ).addClass( 'answered' ).removeClass( 'review' );
			} else {
				steps.eq( currentQuestion ).removeClass( 'answered' );
			}
		});

		nextBtn.on( 'click', function() {
			// If not answered, mark as review
			if ( ! isQuestionAnswered( currentQuestion ) ) {
				steps.eq( currentQuestion ).addClass( 'review' );
			}
			showQuestion( currentQuestion + 1 );
		});

		prevBtn.on( 'click', function() {
			showQuestion( currentQuestion - 1 );
		});

		// Use event delegation for steps
		$( document ).on( 'click', '.pa-step', function() {
			var stepIndex = $( this ).data( 'step' ) - 1;
			showQuestion( stepIndex );
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

		// Initial render
		showQuestion( 0 );
	});
})( jQuery );
