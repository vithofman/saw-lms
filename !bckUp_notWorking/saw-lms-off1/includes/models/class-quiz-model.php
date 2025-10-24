<?php
/**
 * Quiz Model
 *
 * @package     SAW_LMS
 * @subpackage  Models
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Quiz_Model class
 *
 * Model for Quiz post type with database operations and caching.
 *
 * @since 1.0.0
 */
class SAW_LMS_Quiz_Model {

	/**
	 * Cache manager instance
	 *
	 * @var SAW_LMS_Cache_Manager
	 */
	private $cache_manager;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->cache_manager = SAW_LMS_Cache_Manager::get_instance();
	}

	/**
	 * Get quiz by ID
	 *
	 * @since  1.0.0
	 * @param  int  $quiz_id Quiz post ID.
	 * @param  bool $force_refresh Force cache refresh.
	 * @return array|null Quiz data or null if not found.
	 */
	public function get_quiz( $quiz_id, $force_refresh = false ) {
		$cache_key = 'saw_lms_quiz_' . $quiz_id;

		if ( ! $force_refresh ) {
			$cached = $this->cache_manager->get( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$post = get_post( $quiz_id );

		if ( ! $post || 'saw_lms_quiz' !== $post->post_type ) {
			return null;
		}

		$quiz_data = array(
			'id'               => $post->ID,
			'title'            => $post->post_title,
			'slug'             => $post->post_name,
			'description'      => $post->post_content,
			'section_id'       => $post->post_parent,
			'order'            => $post->menu_order,
			'status'           => $post->post_status,
			'created_at'       => $post->post_date,
			'modified_at'      => $post->post_modified,
			'passing_grade'    => get_post_meta( $post->ID, '_saw_lms_passing_grade', true ),
			'time_limit'       => get_post_meta( $post->ID, '_saw_lms_time_limit', true ),
			'max_attempts'     => get_post_meta( $post->ID, '_saw_lms_max_attempts', true ),
			'randomize_questions' => get_post_meta( $post->ID, '_saw_lms_randomize_questions', true ),
			'questions'        => $this->get_quiz_questions( $post->ID ),
		);

		$this->cache_manager->set( $cache_key, $quiz_data, 3600 );

		return $quiz_data;
	}

	/**
	 * Get quiz questions
	 *
	 * @since  1.0.0
	 * @param  int $quiz_id Quiz post ID.
	 * @return array Array of questions.
	 */
	private function get_quiz_questions( $quiz_id ) {
		$questions = get_post_meta( $quiz_id, '_saw_lms_questions', true );

		if ( ! is_array( $questions ) ) {
			return array();
		}

		return $questions;
	}

	/**
	 * Create new quiz
	 *
	 * @since  1.0.0
	 * @param  array $data Quiz data.
	 * @return int|WP_Error Quiz ID on success, WP_Error on failure.
	 */
	public function create_quiz( $data ) {
		$defaults = array(
			'title'               => '',
			'description'         => '',
			'section_id'          => 0,
			'order'               => 0,
			'status'              => 'publish',
			'passing_grade'       => 70,
			'time_limit'          => 0,
			'max_attempts'        => 0,
			'randomize_questions' => false,
			'questions'           => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Quiz title is required.', 'saw-lms' ) );
		}

		if ( empty( $data['section_id'] ) ) {
			return new WP_Error( 'missing_section', __( 'Section ID is required.', 'saw-lms' ) );
		}

		$section = get_post( $data['section_id'] );
		if ( ! $section || 'saw_lms_section' !== $section->post_type ) {
			return new WP_Error( 'invalid_section', __( 'Invalid section ID.', 'saw-lms' ) );
		}

		$quiz_id = wp_insert_post(
			array(
				'post_type'    => 'saw_lms_quiz',
				'post_title'   => sanitize_text_field( $data['title'] ),
				'post_content' => wp_kses_post( $data['description'] ),
				'post_parent'  => absint( $data['section_id'] ),
				'menu_order'   => absint( $data['order'] ),
				'post_status'  => sanitize_key( $data['status'] ),
			),
			true
		);

		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		update_post_meta( $quiz_id, '_saw_lms_passing_grade', absint( $data['passing_grade'] ) );
		update_post_meta( $quiz_id, '_saw_lms_time_limit', absint( $data['time_limit'] ) );
		update_post_meta( $quiz_id, '_saw_lms_max_attempts', absint( $data['max_attempts'] ) );
		update_post_meta( $quiz_id, '_saw_lms_randomize_questions', (bool) $data['randomize_questions'] );
		update_post_meta( $quiz_id, '_saw_lms_questions', $data['questions'] );

		$this->invalidate_quiz_cache( $quiz_id, $data['section_id'] );

		do_action( 'saw_lms_quiz_created', $quiz_id, $data );

		return $quiz_id;
	}

	/**
	 * Update existing quiz
	 *
	 * @since  1.0.0
	 * @param  int   $quiz_id Quiz post ID.
	 * @param  array $data Quiz data to update.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function update_quiz( $quiz_id, $data ) {
		$post = get_post( $quiz_id );

		if ( ! $post || 'saw_lms_quiz' !== $post->post_type ) {
			return new WP_Error( 'invalid_quiz', __( 'Invalid quiz ID.', 'saw-lms' ) );
		}

		$update_data = array(
			'ID' => $quiz_id,
		);

		if ( isset( $data['title'] ) ) {
			$update_data['post_title'] = sanitize_text_field( $data['title'] );
		}

		if ( isset( $data['description'] ) ) {
			$update_data['post_content'] = wp_kses_post( $data['description'] );
		}

		if ( isset( $data['order'] ) ) {
			$update_data['menu_order'] = absint( $data['order'] );
		}

		if ( isset( $data['status'] ) ) {
			$update_data['post_status'] = sanitize_key( $data['status'] );
		}

		$result = wp_update_post( $update_data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( isset( $data['passing_grade'] ) ) {
			update_post_meta( $quiz_id, '_saw_lms_passing_grade', absint( $data['passing_grade'] ) );
		}

		if ( isset( $data['time_limit'] ) ) {
			update_post_meta( $quiz_id, '_saw_lms_time_limit', absint( $data['time_limit'] ) );
		}

		if ( isset( $data['max_attempts'] ) ) {
			update_post_meta( $quiz_id, '_saw_lms_max_attempts', absint( $data['max_attempts'] ) );
		}

		if ( isset( $data['randomize_questions'] ) ) {
			update_post_meta( $quiz_id, '_saw_lms_randomize_questions', (bool) $data['randomize_questions'] );
		}

		if ( isset( $data['questions'] ) ) {
			update_post_meta( $quiz_id, '_saw_lms_questions', $data['questions'] );
		}

		$this->invalidate_quiz_cache( $quiz_id, $post->post_parent );

		do_action( 'saw_lms_quiz_updated', $quiz_id, $data );

		return true;
	}

	/**
	 * Delete quiz
	 *
	 * @since  1.0.0
	 * @param  int  $quiz_id Quiz post ID.
	 * @param  bool $force_delete Force permanent deletion.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_quiz( $quiz_id, $force_delete = false ) {
		$post = get_post( $quiz_id );

		if ( ! $post || 'saw_lms_quiz' !== $post->post_type ) {
			return new WP_Error( 'invalid_quiz', __( 'Invalid quiz ID.', 'saw-lms' ) );
		}

		$section_id = $post->post_parent;

		$result = wp_delete_post( $quiz_id, $force_delete );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete quiz.', 'saw-lms' ) );
		}

		$this->invalidate_quiz_cache( $quiz_id, $section_id );

		do_action( 'saw_lms_quiz_deleted', $quiz_id, $force_delete );

		return true;
	}

	/**
	 * Submit quiz attempt
	 *
	 * @since  1.0.0
	 * @param  int   $quiz_id Quiz post ID.
	 * @param  int   $user_id User ID.
	 * @param  array $answers User's answers.
	 * @return array|WP_Error Attempt data on success, WP_Error on failure.
	 */
	public function submit_attempt( $quiz_id, $user_id, $answers ) {
		global $wpdb;

		$quiz = $this->get_quiz( $quiz_id );

		if ( ! $quiz ) {
			return new WP_Error( 'invalid_quiz', __( 'Invalid quiz ID.', 'saw-lms' ) );
		}

		$max_attempts = absint( $quiz['max_attempts'] );

		if ( $max_attempts > 0 ) {
			$attempt_count = $this->get_attempt_count( $quiz_id, $user_id );

			if ( $attempt_count >= $max_attempts ) {
				return new WP_Error( 'max_attempts', __( 'Maximum attempts reached.', 'saw-lms' ) );
			}
		}

		$score = $this->calculate_score( $quiz['questions'], $answers );

		$table = $wpdb->prefix . 'saw_lms_quiz_attempts';

		$result = $wpdb->insert(
			$table,
			array(
				'user_id'      => absint( $user_id ),
				'quiz_id'      => absint( $quiz_id ),
				'score'        => floatval( $score ),
				'answers'      => wp_json_encode( $answers ),
				'started_at'   => current_time( 'mysql' ),
				'completed_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%f', '%s', '%s', '%s' )
		);

		if ( ! $result ) {
			return new WP_Error( 'submit_failed', __( 'Failed to submit quiz attempt.', 'saw-lms' ) );
		}

		$attempt_id = $wpdb->insert_id;

		$this->cache_manager->delete( 'saw_lms_quiz_attempts_' . $user_id . '_' . $quiz_id );

		$passed = $score >= absint( $quiz['passing_grade'] );

		do_action( 'saw_lms_quiz_submitted', $quiz_id, $user_id, $attempt_id, $score, $passed );

		return array(
			'attempt_id' => $attempt_id,
			'score'      => $score,
			'passed'     => $passed,
		);
	}

	/**
	 * Calculate quiz score
	 *
	 * @since  1.0.0
	 * @param  array $questions Quiz questions.
	 * @param  array $answers User's answers.
	 * @return float Score percentage (0-100).
	 */
	private function calculate_score( $questions, $answers ) {
		if ( empty( $questions ) ) {
			return 0;
		}

		$total_questions = count( $questions );
		$correct_answers = 0;

		foreach ( $questions as $index => $question ) {
			if ( ! isset( $answers[ $index ] ) ) {
				continue;
			}

			$user_answer    = $answers[ $index ];
			$correct_answer = isset( $question['correct_answer'] ) ? $question['correct_answer'] : '';

			if ( $user_answer === $correct_answer ) {
				$correct_answers++;
			}
		}

		return ( $correct_answers / $total_questions ) * 100;
	}

	/**
	 * Get attempt count for user
	 *
	 * @since  1.0.0
	 * @param  int $quiz_id Quiz post ID.
	 * @param  int $user_id User ID.
	 * @return int Attempt count.
	 */
	public function get_attempt_count( $quiz_id, $user_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'saw_lms_quiz_attempts';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE quiz_id = %d AND user_id = %d",
				$quiz_id,
				$user_id
			)
		);

		return absint( $count );
	}

	/**
	 * Get user's quiz attempts
	 *
	 * @since  1.0.0
	 * @param  int  $quiz_id Quiz post ID.
	 * @param  int  $user_id User ID.
	 * @param  bool $force_refresh Force cache refresh.
	 * @return array Array of attempts.
	 */
	public function get_user_attempts( $quiz_id, $user_id, $force_refresh = false ) {
		global $wpdb;

		$cache_key = 'saw_lms_quiz_attempts_' . $user_id . '_' . $quiz_id;

		if ( ! $force_refresh ) {
			$cached = $this->cache_manager->get( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$table = $wpdb->prefix . 'saw_lms_quiz_attempts';

		$attempts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE quiz_id = %d AND user_id = %d ORDER BY completed_at DESC",
				$quiz_id,
				$user_id
			),
			ARRAY_A
		);

		if ( ! $attempts ) {
			$attempts = array();
		}

		$this->cache_manager->set( $cache_key, $attempts, 1800 );

		return $attempts;
	}

	/**
	 * Invalidate quiz cache
	 *
	 * @since 1.0.0
	 * @param int $quiz_id Quiz post ID.
	 * @param int $section_id Section post ID.
	 */
	private function invalidate_quiz_cache( $quiz_id, $section_id ) {
		$this->cache_manager->delete( 'saw_lms_quiz_' . $quiz_id );
		$this->cache_manager->delete( 'saw_lms_section_quizzes_' . $section_id );
	}
}