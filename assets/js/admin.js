(function($) {
    'use strict';

    $(function() {
        var frame;

        // Add Question Button
        $('#add-question-button').on('click', function(e) {
            e.preventDefault();
            $('.question-settings-wrapper').hide();
            $('#add-question-form-wrapper').show();
        });

        // Add Question AJAX
        $('#add-question-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var data = form.serialize() + '&action=pa_add_question';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        $('.questions-list').append(response.data.html);
                        $('#add-question-form-wrapper').hide();
                        form[0].reset();
                    }
                }
            });
        });

        // Edit Question Title
        $(document).on('click', '.edit-question-title', function(e) {
            e.preventDefault();
            var $this = $(this);
            var $title = $this.closest('.question-title-header').find('.question-title-text');
            var currentTitle = $title.text();
            var $input = $('<input type="text" class="question-title-input" value="' + currentTitle + '" />');
            $title.replaceWith($input);
            $input.focus();
        });

        $(document).on('blur', '.question-title-input', function() {
            var $this = $(this);
            var newTitle = $this.val();
            var $questionItem = $this.closest('.question-item');
            var questionId = $questionItem.data('question-id');
            var nonce = $questionItem.find('[name^="pa_save_question_title_nonce"]').val();

            var $title = $('<h3 class="question-title-text">' + newTitle + '</h3>');
            $this.replaceWith($title);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pa_save_question_title',
                    question_id: questionId,
                    title: newTitle,
                    pa_save_question_title_nonce: nonce
                }
            });
        });

        // Select Question
        $(document).on('click', '.question-item', function() {
            var $this = $(this);

            if ($this.hasClass('active')) {
                return;
            }

            $('#add-question-form-wrapper').hide();
            $('.question-item').removeClass('active');
            $this.addClass('active');

            var questionId = $this.data('question-id');
            var questionType = $this.data('question-type');
            var isRequired = $this.data('is-required');

            $('#setting_question_id').val(questionId);
            $('#setting_question_type').val(questionType);
            $('#setting_is_required').prop('checked', isRequired == 1);

            $('.question-settings-wrapper').show();
        });

        // Cancel Edit
        $(document).on('click', '.cancel-button', function(e) {
            e.preventDefault();
            $('.question-item').removeClass('active');
            $('.question-settings-wrapper').hide();
        });

        // Save Question Settings
        $('#question-settings-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var data = form.serialize(); // action is already in the form

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        var questionId = response.data.question_id;
                        var questionType = response.data.type;
                        var isRequired = response.data.is_required;

                        var $questionItem = $('.question-item[data-question-id="' + questionId + '"]');
                        $questionItem.data('question-type', questionType);
                        $questionItem.data('is-required', isRequired);

                        // Add some visual feedback
                        form.find('.button-primary').val('Saved!');
                        setTimeout(function(){
                             form.find('.button-primary').val('Save Question');
                        }, 2000);
                    }
                }
            });
        });

        // Add Answer
        $(document).on('click', '.add-answer-button', function(e) {
            e.preventDefault();
            var $this = $(this);
            var questionId = $this.data('question-id');
            var nonce = $this.data('nonce');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pa_add_answer',
                    question_id: questionId,
                    pa_add_answer_nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $this.siblings('.answers-list').append(response.data.html);
                    }
                }
            });
        });

        // Add Label
        $(document).on('click', '.add-label-button', function(e) {
            e.preventDefault();
            var $this = $(this);
            var $wrapper = $this.siblings('.label-weights-wrapper');
            var $newItem = $('<div class="label-weight-item">' +
                '<input type="text" class="answer-personality-label-input" value="" placeholder="Label" />' +
                '<input type="number" class="answer-weight-input" value="0" placeholder="Weight" />' +
                '<button class="button delete-label-button"><span class="dashicons dashicons-trash"></span></button>' +
                '</div>');
            $wrapper.append($newItem);
        });

        // Delete Label
        $(document).on('click', '.delete-label-button', function(e) {
            e.preventDefault();
            $(this).closest('.label-weight-item').remove();
            // Trigger blur to save the changes after deleting a label
            $(this).closest('.answer-item').find('input').first().trigger('blur');
        });

        // Save Answer Details
        $(document).on('blur', '.answer-item input', function() {
            var $this = $(this);
            var $answerItem = $this.closest('.answer-item');
            var answerId = $answerItem.data('answer-id');

            var labelWeights = {};
            $answerItem.find('.label-weight-item').each(function() {
                var label = $(this).find('.answer-personality-label-input').val();
                var weight = $(this).find('.answer-weight-input').val();
                if (label) {
                    labelWeights[label] = weight;
                }
            });

            var data = {
                action: 'pa_save_answer_details',
                answer_id: answerId,
                label: $answerItem.find('.answer-label-input').val(),
                label_weights: JSON.stringify(labelWeights),
                pa_save_answer_details_nonce: $('#pa_save_answer_details_nonce').val()
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Visual feedback could be added here
                }
            });
        });

        // Delete Answer
        $(document).on('click', '.delete-answer-button', function(e) {
            e.preventDefault();

            if ( ! confirm( 'Are you sure you want to delete this answer?' ) ) {
				return;
			}

            var $this = $(this);
            var answerId = $this.data('answer-id');
            var nonce = $this.data('nonce');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pa_delete_answer',
                    answer_id: answerId,
                    _wpnonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $this.closest('.answer-item').remove();
                    }
                }
            });
        });

        // Delete Quiz Confirmation
        $(document).on('click', '.pa-delete-quiz', function(e) {
            if ( ! confirm( 'Are you sure you want to delete this quiz and all its results? This action cannot be undone.' ) ) {
                e.preventDefault();
            }
        });

        // Toggle Import Form
        $('#pa-import-quiz-button').on('click', function(e) {
            e.preventDefault();
            $('#pa-import-form-wrapper').toggle();
        });
    });
})(jQuery);
