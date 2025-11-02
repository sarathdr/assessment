(function($) {
    'use strict';

    $(function() {
        var frame;

        // Add Question Modal
        $('#add-question-button').on('click', function(e) {
            e.preventDefault();

            if ($('#pa-modal-backdrop').length) {
                return;
            }

            var template = wp.template('add-question-form');
            $('body').append('<div id="pa-modal-backdrop"></div>');
            $('body').append('<div id="pa-modal-wrap"></div>');
            $('#pa-modal-wrap').html(template());
        });

        // Close Modal
        $(document).on('click', '#pa-modal-backdrop', function() {
            $('#pa-modal-wrap, #pa-modal-backdrop').remove();
        });

        // Add Question AJAX
        $(document).on('submit', '.add-question-form-modal', function(e) {
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
                        $('#pa-modal-wrap, #pa-modal-backdrop').remove();
                    }
                }
            });
        });

        // Select Question
        $(document).on('click', '.question-item', function() {
            var $this = $(this);
            $('.question-item').removeClass('active');
            $this.addClass('active');

            var questionId = $this.data('question-id');
            var questionType = $this.data('question-type');
            var isRequired = $this.data('is-required');

            $('#setting_question_id').val(questionId);
            $('#setting_question_type').val(questionType);
            $('#setting_is_required').prop('checked', isRequired == 1);

            $('.no-question-selected').hide();
            $('.settings-fields').show();
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

        // Save Answer Details
        $(document).on('blur', '.answer-item input', function() {
            var $this = $(this);
            var $answerItem = $this.closest('.answer-item');
            var answerId = $answerItem.data('answer-id');

            var data = {
                action: 'pa_save_answer_details',
                answer_id: answerId,
                label: $answerItem.find('.answer-label-input').val(),
                weight: $answerItem.find('.answer-weight-input').val(),
                personality_label: $answerItem.find('.answer-personality-label-input').val(),
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

    });
})(jQuery);
