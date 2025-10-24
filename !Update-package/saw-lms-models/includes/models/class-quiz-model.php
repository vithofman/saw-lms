<?php
/**
 * Quiz Model
 *
 * Model for working with the wp_saw_lms_quizzes structured table.
 * Provides methods for CRUD operations, caching, and quiz-specific queries.
 *
 * Quizzes can be associated with courses or sections.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/models
 * @since      3.0.0
 * @version    3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Quiz_Model Class
 *
 * Handles all database operations for quizzes.
 *
 * @since 3.0.0
 */
class SAW_LMS_Quiz_Model {

	/**
	 * Table name (without prefix)
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	const TABLE_NAME = 'saw_lms_quizzes';

	/**
	 * Cache group
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	const CACHE_GROUP = 'saw_lms_quizzes';

	/**
	 * Cache TTL (1 hour)
	 *
	 * @since  3.0.0
	 * @var    int
	 */
	const CACHE_TTL = 3600;

	/**
	 * Get quiz by post_id
	 *
	 * Retrieves quiz data from the structured table.
	 *
	 * @since  3.0.0
	 * @param  int $post_id WordPress post ID.
	 * @return object|null  Quiz object or null if not found.
	 */
	public static function get_by_post_id( $post_id ) {
		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return null;
		}

		// Try cache first.
		$cache_key = 'quiz_' . $post_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database.
		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$quiz = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE post_id = %d",
				$post_id
			)
		);

		if ( ! $quiz ) {
			return null;
		}

		// Cache the result.
		wp_cache_set( $cache_key, $quiz, self::CACHE_GROUP, self::CACHE_TTL );

		return $quiz;
	}

	/**
	 * Get quizzes by course_id
	 *
	 * Retrieves all quizzes belonging to a specific course.
	 *
	 * @since  3.0.0
	 * @param  int $course_id Course ID from wp_saw_lms_courses.
	 * @return array          Array of quiz objects.
	 */
	public static function get_by_course_id( $course_id ) {
		global $wpdb;

		$course_id = absint( $course_id );

		if ( ! $course_id ) {
			return array();
		}

		// Try cache first.
		$cache_key = 'course_quizzes_' . $course_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database.
		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$quizzes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE course_id = %d",
				$course_id
			)
		);

		if ( ! $quizzes ) {
			return array();
		}

		// Cache the results.
		wp_cache_set( $cache_key, $quizzes, self::CACHE_GROUP, self::CACHE_TTL );

		return $quizzes;
	}

	/**
	 * Get quizzes by section_id
	 *
	 * Retrieves all quizzes belonging to a specific section.
	 *
	 * @since  3.0.0
	 * @param  int $section_id Section ID from wp_saw_lms_sections.
	 * @return array           Array of quiz objects.
	 */
	public static function get_by_section_id( $section_id ) {
		global $wpdb;

		$section_id = absint( $section_id );

		if ( ! $section_id ) {
			return array();
		}

		// Try cache first.
		$cache_key = 'section_quizzes_' . $section_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database.
		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$quizzes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE section_id = %d",
				$section_id
			)
		);

		if ( ! $quizzes ) {
			return array();
		}

		// Cache the results.
		wp_cache_set( $cache_key, $quizzes, self::CACHE_GROUP, self::CACHE_TTL );

		return $quizzes;
	}

	/**
	 * Save or update quiz data
	 *
	 * Inserts new quiz or updates existing quiz.
	 *
	 * @since  3.0.0
	 * @param  int   $post_id WordPress post ID.
	 * @param  array $data    Quiz data (column_name => value).
	 * @return int|false      Quiz ID on success, false on failure.
	 */
	public static function save( $post_id, $data ) {
		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		// Check if quiz exists.
		$existing = self::get_by_post_id( $post_id );

		if ( $existing ) {
			// UPDATE.
			$data['updated_at'] = current_time( 'mysql' );

			$table = $wpdb->prefix . self::TABLE_NAME;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->update(
				$table,
				$data,
				array( 'post_id' => $post_id ),
				null,
				array( '%d' )
			);

			// Invalidate cache.
			self::invalidate_cache( $post_id );

			return ( false !== $result ) ? $existing->id : false;

		} else {
			// INSERT.
			$data['post_id']    = $post_id;
			$data['created_at'] = current_time( 'mysql' );
			$data['updated_at'] = current_time( 'mysql' );

			$table = $wpdb->prefix . self::TABLE_NAME;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert( $table, $data );

			// Invalidate cache.
			self::invalidate_cache( $post_id );

			return ( false !== $result ) ? $wpdb->insert_id : false;
		}
	}

	/**
	 * Delete quiz data
	 *
	 * Removes quiz from the structured table.
	 *
	 * @since  3.0.0
	 * @param  int  $post_id WordPress post ID.
	 * @return bool          True on success, false on failure.
	 */
	public static function delete( $post_id ) {
		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		// Get quiz to find course_id and section_id for cache invalidation.
		$quiz = self::get_by_post_id( $post_id );

		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete(
			$table,
			array( 'post_id' => $post_id ),
			array( '%d' )
		);

		// Invalidate cache.
		self::invalidate_cache( $post_id );

		// Also invalidate course/section quizzes cache.
		if ( $quiz ) {
			if ( isset( $quiz->course_id ) && $quiz->course_id ) {
				wp_cache_delete( 'course_quizzes_' . $quiz->course_id, self::CACHE_GROUP );
			}

			if ( isset( $quiz->section_id ) && $quiz->section_id ) {
				wp_cache_delete( 'section_quizzes_' . $quiz->section_id, self::CACHE_GROUP );
			}
		}

		return ( false !== $result );
	}

	/**
	 * Invalidate cache for a quiz
	 *
	 * Clears all cache related to a specific quiz.
	 *
	 * @since  3.0.0
	 * @param  int $post_id WordPress post ID.
	 * @return void
	 */
	public static function invalidate_cache( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return;
		}

		// Get quiz to find course_id and section_id.
		$quiz = self::get_by_post_id( $post_id );

		// Delete specific quiz cache.
		wp_cache_delete( 'quiz_' . $post_id, self::CACHE_GROUP );

		// Delete course/section quizzes cache.
		if ( $quiz ) {
			if ( isset( $quiz->course_id ) && $quiz->course_id ) {
				wp_cache_delete( 'course_quizzes_' . $quiz->course_id, self::CACHE_GROUP );
			}

			if ( isset( $quiz->section_id ) && $quiz->section_id ) {
				wp_cache_delete( 'section_quizzes_' . $quiz->section_id, self::CACHE_GROUP );
			}
		}

		/**
		 * Fires after quiz cache is invalidated.
		 *
		 * @since 3.0.0
		 * @param int $post_id Quiz post ID.
		 */
		do_action( 'saw_lms_quiz_cache_invalidated', $post_id );
	}
}
