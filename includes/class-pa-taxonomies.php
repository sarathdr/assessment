<?php
/**
 * Taxonomies
 *
 * @package           PersonalityAssessment
 * @author            Jules
 * @copyright         2024 Jules
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 */

namespace PA;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Taxonomies
 */
class Taxonomies {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Register the taxonomies
	 */
	public function register_taxonomies() {
		$labels = array(
			'name'              => _x( 'Personality Labels', 'taxonomy general name', 'personality-assessment' ),
			'singular_name'     => _x( 'Personality Label', 'taxonomy singular name', 'personality-assessment' ),
			'search_items'      => __( 'Search Personality Labels', 'personality-assessment' ),
			'all_items'         => __( 'All Personality Labels', 'personality-assessment' ),
			'parent_item'       => __( 'Parent Personality Label', 'personality-assessment' ),
			'parent_item_colon' => __( 'Parent Personality Label:', 'personality-assessment' ),
			'edit_item'         => __( 'Edit Personality Label', 'personality-assessment' ),
			'update_item'       => __( 'Update Personality Label', 'personality-assessment' ),
			'add_new_item'      => __( 'Add New Personality Label', 'personality-assessment' ),
			'new_item_name'     => __( 'New Personality Label Name', 'personality-assessment' ),
			'menu_name'         => __( 'Labels', 'personality-assessment' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'personality-label' ),
			'public'            => false,
			'show_in_rest'      => true,
		);

		register_taxonomy( 'pa_personality_label', array(), $args );
	}
}
