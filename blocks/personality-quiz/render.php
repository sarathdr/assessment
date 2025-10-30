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

	$shortcode = '[pa_quiz id="' . intval( $attributes['quizId'] ) . '"';

	if ( isset( $attributes['showTitle'] ) && ! $attributes['showTitle'] ) {
		$shortcode .= ' show_title="false"';
	}

	if ( isset( $attributes['showProgress'] ) && ! $attributes['showProgress'] ) {
		$shortcode .= ' show_progress="false"';
	}

	$shortcode .= ']';

	return do_shortcode( $shortcode );
}
