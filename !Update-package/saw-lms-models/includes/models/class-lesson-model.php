<?php
/**
 * Lesson Model
 *
 * Model for working with the wp_saw_lms_lessons structured table.
 * Provides methods for CRUD operations, caching, and lesson-specific queries.
 *
 * Lessons are individual learning units within sections.
 * They can be videos, documents, or assignments.
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
 * SAW_LMS_Lesson_Model Class
 *
 * Handles all database operations for lessons.
 *
 * @since 3.0.0
 */
class SAW_LMS_Lesson_Model {

	/**
	 * Table name (without prefix)
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	const TABLE_NAME = 'saw_lms_lessons';

	/**
	 * Cache group
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	const CACHE_GROUP = 'saw_lms_lessons';

	/**
	 * Cache TTL (1 hour)
	 *
	 * @since  3.0.0
	 * @var    int
	 */
	const CACHE_TTL = 3600;

	/**
	 * Get lesson by post_id
	 *
	 * Retrieves lesson data from the structured table.
	 *
	 * @since  3.0.0
	 * @param  int $post_id WordPress post ID.
	 * @return object|null  Lesson object or null if not found.
	 */
	public static function get_by_post_id( $post_id ) {
		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return null;
		}

		// Try cache first.
		$cache_key = 'lesson_' . $post_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database.
		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$lesson = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE post_id = %d",
				$post_id
			)
		);

		if ( ! $lesson ) {
			return null;
		}

		// Cache the result.
		wp_cache_set( $cache_key, $lesson, self::CACHE_GROUP, self::CACHE_TTL );

		return $lesson;
	}

	/**
	 * Get lessons by section_id
	 *
	 * Retrieves all lessons belonging to a specific section.
	 *
	 * @since  3.0.0
	 * @param  int  $section_id  Section ID from wp_saw_lms_sections.
	 * @param  bool $ordered     Whether to sort by lesson_order (default: true).
	 * @return array             Array of lesson objects.
	 */
	public static function get_by_section_id( $section_id, $ordered = true ) {
		global $wpdb;

		$section_id = absint( $section_id );

		if ( ! $section_id ) {
			return array();
		}

		// Try cache first.
		$cache_key = 'section_lessons_' . $section_id . '_' . ( $ordered ? 'ordered' : 'unordered' );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database.
		$table    = $wpdb->prefix . self::TABLE_NAME;
		$order_by = $ordered ? 'ORDER BY lesson_order ASC' : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$lessons = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE section_id = %d {$order_by}",
				$section_id
			)
		);

		if ( ! $lessons ) {
			return array();
		}

		// Cache the results.
		wp_cache_set( $cache_key, $lessons, self::CACHE_GROUP, self::CACHE_TTL );

		return $lessons;
	}

	/**
	 * Get lessons by type
	 *
	 * Retrieves all lessons of a specific type within a section.
	 *
	 * @since  3.0.0
	 * @param  int    $section_id  Section ID.
	 * @param  string $lesson_type Lesson type (video/document/assignment).
	 * @return array               Array of lesson objects.
	 */
	public static function get_by_type( $section_id, $lesson_type ) {
		global $wpdb;

		$section_id = absint( $section_id );

		if ( ! $section_id || empty( $lesson_type ) ) {
			return array();
		}

		// Try cache first.
		$cache_key = 'section_lessons_' . $section_id . '_type_' . sanitize_key( $lesson_type );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database.
		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$lessons = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE section_id = %d AND lesson_type = %s ORDER BY lesson_order ASC",
				$section_id,
				sanitize_text_field( $lesson_type )
			)
		);

		if ( ! $lessons ) {
			return array();
		}

		// Cache the results.
		wp_cache_set( $cache_key, $lessons, self::CACHE_GROUP, self::CACHE_TTL );

		return $lessons;
	}

	/**
	 * Save or update lesson data
	 *
	 * Inserts new lesson or updates existing lesson.
	 *
	 * @since  3.0.0
	 * @param  int   $post_id WordPress post ID.
	 * @param  array $data    Lesson data (column_name => value).
	 * @return int|false      Lesson ID on success, false on failure.
	 */
	public static function save( $post_id, $data ) {
		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		// Check if lesson exists.
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
	 * Delete lesson data
	 *
	 * Removes lesson from the structured table.
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

		// Get lesson to find section_id for cache invalidation.
		$lesson = self::get_by_post_id( $post_id );

		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete(
			$table,
			array( 'post_id' => $post_id ),
			array( '%d' )
		);

		// Invalidate cache.
		self::invalidate_cache( $post_id );

		// Also invalidate section lessons cache.
		if ( $lesson && isset( $lesson->section_id ) ) {
			wp_cache_delete( 'section_lessons_' . $lesson->section_id . '_ordered', self::CACHE_GROUP );
			wp_cache_delete( 'section_lessons_' . $lesson->section_id . '_unordered', self::CACHE_GROUP );
		}

		return ( false !== $result );
	}

	/**
	 * Invalidate cache for a lesson
	 *
	 * Clears all cache related to a specific lesson.
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

		// Get lesson to find section_id.
		$lesson = self::get_by_post_id( $post_id );

		// Delete specific lesson cache.
		wp_cache_delete( 'lesson_' . $post_id, self::CACHE_GROUP );

		// Delete section lessons cache.
		if ( $lesson && isset( $lesson->section_id ) ) {
			wp_cache_delete( 'section_lessons_' . $lesson->section_id . '_ordered', self::CACHE_GROUP );
			wp_cache_delete( 'section_lessons_' . $lesson->section_id . '_unordered', self::CACHE_GROUP );

			// Also clear by type cache.
			if ( isset( $lesson->lesson_type ) ) {
				wp_cache_delete( 'section_lessons_' . $lesson->section_id . '_type_' . $lesson->lesson_type, self::CACHE_GROUP );
			}
		}

		/**
		 * Fires after lesson cache is invalidated.
		 *
		 * @since 3.0.0
		 * @param int $post_id Lesson post ID.
		 */
		do_action( 'saw_lms_lesson_cache_invalidated', $post_id );
	}
}
