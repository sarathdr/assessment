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
        $(document).on('click', '#pa-import-quiz-button', function(e) {
            e.preventDefault();
            $('#pa-import-form-wrapper').toggle();
        });

        // Tab Switching
        $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            // Remove active class from all tabs
            $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
            
            // Add active class to clicked tab
            $(this).addClass('nav-tab-active');
            
            // Hide all tab content
            $('.pa-tab-content').hide();
            
            // Show target tab content
            $(target).show();
            
            // Store active tab in localStorage
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem("pa_active_tab", target);
            }

            // If Label Settings tab is clicked, refresh the content
            if (target === '#pa-tab-labels') {
                var quizId = $('input[name="quiz_id"]').val();
                var nonce = $('#pa_save_label_settings_nonce').val();

                // Show loading state (optional)
                $('#pa-tab-labels').css('opacity', '0.5');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pa_get_label_settings',
                        quiz_id: quizId,
                        nonce: nonce
                    },
                    success: function(response) {
                        $('#pa-tab-labels').css('opacity', '1');
                        if (response.success) {
                            // We need to keep the wrapper and form structure if the response only returns the inner content
                            // But our PHP returns the inner content of the form mostly.
                            // Let's check what PHP returns. It returns render_label_settings_content output.
                            // render_label_settings_content outputs the grid or the "no labels" message + submit button.
                            // It does NOT output the <form> tag itself.
                            // So we should replace the content INSIDE the form.
                            
                            // Wait, the PHP method render_label_settings_content outputs everything INSIDE the form?
                            // Let's check the PHP code again.
                            // Yes, it outputs the grid and the submit button.
                            // The form tag is in the main render_quiz_form method.
                            // So we should replace the content of the form in #pa-tab-labels.
                            
                            $('#pa-tab-labels form').html(
                                '<input type="hidden" name="action" value="save_label_settings" />' +
                                '<input type="hidden" name="quiz_id" value="' + quizId + '" />' +
                                '<input type="hidden" name="pa_save_label_settings_nonce" value="' + nonce + '" />' + // We need to manually add the nonce field back or use the one from response if we included it?
                                // Actually, wp_nonce_field outputs a hidden input.
                                // The PHP method render_label_settings_content does NOT output the nonce field.
                                // The nonce field is OUTSIDE render_label_settings_content in the main method.
                                // So if we replace the form content, we lose the nonce and hidden inputs.
                                
                                // Better approach: The PHP method should output ONLY the grid and submit button.
                                // We should target a container INSIDE the form to replace.
                                // But currently there is no container wrapping the dynamic content.
                                // The form contains: hidden inputs, nonce, AND the dynamic content.
                                
                                // Let's look at the PHP again.
                                // <form ...>
                                //   <inputs...>
                                //   <?php $this->render_label_settings_content($quiz_id); ?>
                                // </form>
                                
                                // So we should wrap the dynamic content in a div in PHP, or just replace everything after the nonce.
                                // Or simpler: Just replace the .pa-label-settings-grid and the submit button.
                                // But if there are NO labels, it outputs a <p>.
                                
                                // Let's update the JS to replace the whole form content, but we need to reconstruct the hidden inputs.
                                // OR, we can update the PHP to include the hidden inputs in the response? No, that duplicates logic.
                                
                                // Best way: Update PHP to wrap the dynamic content in a <div id="pa-label-settings-content">.
                                // Then JS targets that div.
                                
                                // I will assume I will update PHP to add the wrapper.
                                // For now, let's write the JS to target #pa-label-settings-content.
                                
                                // Wait, I haven't added that wrapper yet.
                                // I should add the wrapper in PHP first.
                                
                                // Let's do the JS assuming the wrapper exists, then I'll add the wrapper.
                                // Actually, I can just replace everything inside the form, and re-add the hidden inputs.
                                // The nonce value is available in `nonce` variable.
                                
                                '<input type="hidden" name="action" value="save_label_settings" />' +
                                '<input type="hidden" name="quiz_id" value="' + quizId + '" />' +
                                '<input type="hidden" id="pa_save_label_settings_nonce" name="pa_save_label_settings_nonce" value="' + nonce + '" />' +
                                '<input type="hidden" name="_wp_http_referer" value="' + $('input[name="_wp_http_referer"]').val() + '" />' + 
                                response.data.html
                            );
                        }
                    }
                });
            }
        });

        // Restore active tab on page load
        if (typeof(Storage) !== "undefined") {
            var activeTab = localStorage.getItem("pa_active_tab");
            if (activeTab && $(activeTab).length > 0) {
                $('.nav-tab-wrapper .nav-tab[href="' + activeTab + '"]').click();
            } else {
                // Default to first tab
                $('.nav-tab-wrapper .nav-tab').first().click();
            }
        }
    });

    // Media Uploader
    $(document).on('click', '.pa-upload-icon-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetInput = $(button.data('target'));
        
        // Create the media frame.
        var customUploader = wp.media({
            title: 'Select Icon',
            button: {
                text: 'Use this icon'
            },
            multiple: false
        });

        // When an image is selected, run a callback.
        customUploader.on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            targetInput.val(attachment.url);
            
            // Show preview
            // The structure is somewhat loose, so we look for existing preview or insert new one
            // In PHP rendering: button is after input, preview is after wrapper or inside wrapper?
            // PHP: 
            // <div class="pa-icon-upload-wrapper">
            //    <input ...> <button ...>
            // </div>
            // <img class="pa-icon-preview"> (if exists)
            
            // The previous JS implementation:
            // var previewContainer = button.next('.pa-icon-preview');
            // if (previewContainer.length === 0) {
            //      previewContainer = $('<img style="max-width: 50px; margin-top: 5px;" />').insertAfter(button);
            //      button.after('<br>'); // This adds a break between button and image if inserted
            // }
            
            // Let's stick to a robust finding method.
            // In the PHP render_label_settings_content:
            // echo '<div class="pa-icon-upload-wrapper">';
            // echo '<input ...>'; 
            // echo '<button ...>'; 
            // echo '</div>';
            // if ($icon_url) echo '<img ...>';
            
            // So the img is a sibling of the wrapper, NOT the button. The button is effectively inside the wrapper?
            // Wait, looking at PHP again:
            // echo '<div class="pa-icon-upload-wrapper">';
            // echo '<input ...>';
            // echo '<button ...>';
            // echo '</div>';
            // if ($icon_url) ...
            
            // The JS `button.next('.pa-icon-preview')` would attempt to find it inside the wrapper if button is last, 
            // but the image is OUTSIDE the wrapper.
            
            var wrapper = button.closest('.pa-icon-upload-wrapper');
            var previewContainer = wrapper.next('.pa-icon-preview');
            
            if (previewContainer.length === 0) {
                previewContainer = $('<img class="pa-icon-preview" style="max-width: 50px; margin-top: 5px; display: block;" />');
                wrapper.after(previewContainer);
            }
            
            previewContainer.attr('src', attachment.url);
        });

        // Open the modal.
        customUploader.open();
    });

})(jQuery);
