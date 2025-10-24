<?php
/**
 * Section Model
 *
 * Model for working with the wp_saw_lms_sections structured table.
 * Provides methods for CRUD operations, caching, and section-specific queries.
 *
 * Sections are hierarchical containers for lessons within a course.
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
 * SAW_LMS_Section_Model Class
 *
 * Handles all database operations for sections.
 *
 * @since 3.0.0
 */
class SAW_LMS_Section_Model {

	/**
	 * Table name (without prefix)
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	const TABLE_NAME = 'saw_lms_sections';

	/**
	 * Cache group
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	const CACHE_GROUP = 'saw_lms_sections';

	/**
	 * Cache TTL (1 hour)
	 *
	 * @since  3.0.0
	 * @var    int
	 */
	const CACHE_TTL = 3600;

	/**
	 * Get section by post_id
	 *
	 * Retrieves section data from the structured table.
	 *
	 * @since  3.0.0
	 * @param  int $post_id WordPress post ID.
	 * @return object|null  Section object or null if not found.
	 */
	public static function get_by_post_id( $post_id ) {
		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return null;
		}

		// Try cache first.
		$cache_key = 'section_' . $post_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database.
		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$section = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE post_id = %d",
				$post_id
			)
		);

		if ( ! $section ) {
			return null;
		}

		// Decode JSON fields.
		$section = self::decode_json_fields( $section );

		// Cache the result.
		wp_cache_set( $cache_key, $section, self::CACHE_GROUP, self::CACHE_TTL );

		return $section;
	}

	/**
	 * Get sections by course_id
	 *
	 * Retrieves all sections belonging to a specific course.
	 *
	 * @since  3.0.0
	 * @param  int  $course_id    Course ID from wp_saw_lms_courses.
	 * @param  bool $ordered      Whether to sort by section_order (default: true).
	 * @return array              Array of section objects.
	 */
	public static function get_by_course_id( $course_id, $ordered = true ) {
		global $wpdb;

		$course_id = absint( $course_id );

		if ( ! $course_id ) {
			return array();
		}

		// Try cache first.
		$cache_key = 'course_sections_' . $course_id . '_' . ( $ordered ? 'ordered' : 'unordered' );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database.
		$table    = $wpdb->prefix . self::TABLE_NAME;
		$order_by = $ordered ? 'ORDER BY section_order ASC' : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sections = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE course_id = %d {$order_by}",
				$course_id
			)
		);

		if ( ! $sections ) {
			return array();
		}

		// Decode JSON fields for each section.
		$sections = array_map( array( self::class, 'decode_json_fields' ), $sections );

		// Cache the results.
		wp_cache_set( $cache_key, $sections, self::CACHE_GROUP, self::CACHE_TTL );

		return $sections;
	}

	/**
	 * Save or update section data
	 *
	 * Inserts new section or updates existing section.
	 *
	 * @since  3.0.0
	 * @param  int   $post_id WordPress post ID.
	 * @param  array $data    Section data (column_name => value).
	 * @return int|false      Section ID on success, false on failure.
	 */
	public static function save( $post_id, $data ) {
		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		// Encode JSON fields.
		$data = self::encode_json_fields( $data );

		// Check if section exists.
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
	 * Delete section data
	 *
	 * Removes section from the structured table.
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

		// Get section to find course_id for cache invalidation.
		$section = self::get_by_post_id( $post_id );

		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete(
			$table,
			array( 'post_id' => $post_id ),
			array( '%d' )
		);

		// Invalidate cache.
		self::invalidate_cache( $post_id );

		// Also invalidate course sections cache.
		if ( $section && isset( $section->course_id ) ) {
			wp_cache_delete( 'course_sections_' . $section->course_id . '_ordered', self::CACHE_GROUP );
			wp_cache_delete( 'course_sections_' . $section->course_id . '_unordered', self::CACHE_GROUP );
		}

		return ( false !== $result );
	}

	/**
	 * Invalidate cache for a section
	 *
	 * Clears all cache related to a specific section.
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

		// Get section to find course_id.
		$section = self::get_by_post_id( $post_id );

		// Delete specific section cache.
		wp_cache_delete( 'section_' . $post_id, self::CACHE_GROUP );

		// Delete course sections cache.
		if ( $section && isset( $section->course_id ) ) {
			wp_cache_delete( 'course_sections_' . $section->course_id . '_ordered', self::CACHE_GROUP );
			wp_cache_delete( 'course_sections_' . $section->course_id . '_unordered', self::CACHE_GROUP );
		}

		/**
		 * Fires after section cache is invalidated.
		 *
		 * @since 3.0.0
		 * @param int $post_id Section post ID.
		 */
		do_action( 'saw_lms_section_cache_invalidated', $post_id );
	}

	/**
	 * Encode JSON fields
	 *
	 * Converts array fields to JSON strings for database storage.
	 *
	 * @since  3.0.0
	 * @param  array $data Section data array.
	 * @return array       Data with JSON-encoded fields.
	 */
	private static function encode_json_fields( $data ) {
		$json_fields = array( 'documents' );

		foreach ( $json_fields as $field ) {
			if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
				$data[ $field ] = wp_json_encode( $data[ $field ] );
			}
		}

		return $data;
	}

	/**
	 * Decode JSON fields
	 *
	 * Converts JSON strings back to arrays after fetching from database.
	 *
	 * @since  3.0.0
	 * @param  object $section Section object from database.
	 * @return object          Section object with decoded JSON fields.
	 */
	private static function decode_json_fields( $section ) {
		if ( ! $section ) {
			return $section;
		}

		$json_fields = array( 'documents' );

		foreach ( $json_fields as $field ) {
			if ( isset( $section->$field ) && is_string( $section->$field ) ) {
				$decoded = json_decode( $section->$field, true );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					$section->$field = $decoded;
				}
			}
		}

		return $section;
	}
}
