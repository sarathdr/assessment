<?php
/**
 * Block render callback.
 *
 * @param array $attributes The block attributes.
 *
 * @return string The block content.
 */
function pa_render_quiz_block( $attributes ) {
	if ( ! isset( $attributes['quizId'] ) ) {
		return '';
	}

	return do_shortcode( '[pa_quiz id="' . intval( $attributes['quizId'] ) . '"]' );
}
