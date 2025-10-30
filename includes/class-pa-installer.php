<?php
/**
 * Installer
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
 * Fired during plugin activation
 */
class Installer {
	/**
	 * Run the installer
	 */
	public static function install() {
		self::create_tables();
		self::create_sample_data();
	}

	/**
	 * Create the tables
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$wpdb->prefix}pa_quizzes (
			id BIGINT NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			slug VARCHAR(200) NOT NULL,
			description LONGTEXT NULL,
			status ENUM('draft','published') DEFAULT 'draft' NOT NULL,
			require_login TINYINT(1) DEFAULT 1 NOT NULL,
			settings LONGTEXT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE {$wpdb->prefix}pa_questions (
			id BIGINT NOT NULL AUTO_INCREMENT,
			quiz_id BIGINT NOT NULL,
			title TEXT NOT NULL,
			type ENUM('text','single','multiple') NOT NULL,
			position INT NOT NULL,
			is_required TINYINT(1) DEFAULT 1 NOT NULL,
			PRIMARY KEY  (id),
			KEY quiz_id (quiz_id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE {$wpdb->prefix}pa_answers (
			id BIGINT NOT NULL AUTO_INCREMENT,
			question_id BIGINT NOT NULL,
			label TEXT NOT NULL,
			weight DECIMAL(10,2) DEFAULT 0 NOT NULL,
			personality_label VARCHAR(100) NULL,
			position INT NOT NULL,
			PRIMARY KEY  (id),
			KEY question_id (question_id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE {$wpdb->prefix}pa_results (
			id BIGINT NOT NULL AUTO_INCREMENT,
			quiz_id BIGINT NOT NULL,
			user_id BIGINT NULL,
			attempt_uuid CHAR(36) NOT NULL,
			submitted_at DATETIME NOT NULL,
			score_total DECIMAL(10,2) NOT NULL,
			dominant_label VARCHAR(100) NULL,
			raw_payload LONGTEXT NOT NULL,
			is_locked TINYINT(1) DEFAULT 1 NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY attempt_uuid (attempt_uuid),
			KEY quiz_id (quiz_id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create sample data
	 */
	private static function create_sample_data() {
		global $wpdb;

		$quiz_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}pa_quizzes WHERE slug = %s", 'work-style-finder' ) );

		if ( $quiz_exists ) {
			return;
		}

		// 1. Insert Quiz
		$wpdb->insert(
			"{$wpdb->prefix}pa_quizzes",
			array(
				'title'       => 'Work Style Finder',
				'slug'        => 'work-style-finder',
				'description' => 'Discover your dominant work style and how you can leverage it.',
				'status'      => 'published',
				'require_login' => 0,
			)
		);
		$quiz_id = $wpdb->insert_id;

		// 2. Insert Questions & Answers
		// Question 1
		$wpdb->insert(
			"{$wpdb->prefix}pa_questions",
			array(
				'quiz_id'     => $quiz_id,
				'title'       => 'When faced with a complex problem, what is your initial approach?',
				'type'        => 'single',
				'position'    => 1,
				'is_required' => 1,
			)
		);
		$q1_id = $wpdb->insert_id;
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q1_id, 'label' => 'Analyze all the data and facts before making a move.', 'weight' => 2.0, 'personality_label' => 'Analyst', 'position' => 1));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q1_id, 'label' => 'Consider how the solution will affect my team members.', 'weight' => 2.0, 'personality_label' => 'Diplomat', 'position' => 2));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q1_id, 'label' => 'Follow established procedures and proven methods.', 'weight' => 2.0, 'personality_label' => 'Sentinel', 'position' => 3));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q1_id, 'label' => 'Jump in and experiment with different solutions.', 'weight' => 2.0, 'personality_label' => 'Explorer', 'position' => 4));

		// Question 2
		$wpdb->insert(
			"{$wpdb->prefix}pa_questions",
			array(
				'quiz_id'     => $quiz_id,
				'title'       => 'Which of these tasks do you enjoy the most?',
				'type'        => 'single',
				'position'    => 2,
				'is_required' => 1,
			)
		);
		$q2_id = $wpdb->insert_id;
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q2_id, 'label' => 'Organizing a detailed project plan.', 'weight' => 2.0, 'personality_label' => 'Sentinel', 'position' => 1));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q2_id, 'label' => 'Brainstorming new, unconventional ideas.', 'weight' => 2.0, 'personality_label' => 'Explorer', 'position' => 2));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q2_id, 'label' => 'Mediating a conflict between colleagues.', 'weight' => 2.0, 'personality_label' => 'Diplomat', 'position' => 3));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q2_id, 'label' => 'Solving a logical puzzle or debugging code.', 'weight' => 2.0, 'personality_label' => 'Analyst', 'position' => 4));

		// Question 3
		$wpdb->insert(
			"{$wpdb->prefix}pa_questions",
			array(
				'quiz_id'     => $quiz_id,
				'title'       => 'How do you prefer to receive feedback?',
				'type'        => 'single',
				'position'    => 3,
				'is_required' => 1,
			)
		);
		$q3_id = $wpdb->insert_id;
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q3_id, 'label' => 'Direct, honest, and based on objective evidence.', 'weight' => 2.0, 'personality_label' => 'Analyst', 'position' => 1));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q3_id, 'label' => 'Gentle, encouraging, and focused on personal growth.', 'weight' => 2.0, 'personality_label' => 'Diplomat', 'position' => 2));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q3_id, 'label' => 'As part of a structured performance review.', 'weight' => 2.0, 'personality_label' => 'Sentinel', 'position' => 3));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q3_id, 'label' => 'Informally, as part of an ongoing conversation.', 'weight' => 2.0, 'personality_label' => 'Explorer', 'position' => 4));

		// Question 4
		$wpdb->insert(
			"{$wpdb->prefix}pa_questions",
			array(
				'quiz_id'     => $quiz_id,
				'title'       => 'Which of these statements best describes you? (Select all that apply)',
				'type'        => 'multiple',
				'position'    => 4,
				'is_required' => 1,
			)
		);
		$q4_id = $wpdb->insert_id;
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q4_id, 'label' => 'I am naturally curious and question assumptions.', 'weight' => 1.0, 'personality_label' => 'Analyst', 'position' => 1));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q4_id, 'label' => 'I am adaptable and embrace change.', 'weight' => 1.0, 'personality_label' => 'Explorer', 'position' => 2));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q4_id, 'label' => 'I value harmony and cooperation in a team.', 'weight' => 1.0, 'personality_label' => 'Diplomat', 'position' => 3));
		$wpdb->insert("{$wpdb->prefix}pa_answers", array('question_id' => $q4_id, 'label' => 'I am reliable and detail-oriented.', 'weight' => 1.0, 'personality_label' => 'Sentinel', 'position' => 4));

		// Question 5
		$wpdb->insert(
			"{$wpdb->prefix}pa_questions",
			array(
				'quiz_id'     => $quiz_id,
				'title'       => 'Briefly describe your ideal work environment.',
				'type'        => 'text',
				'position'    => 5,
				'is_required' => 0,
			)
		);
	}
}
