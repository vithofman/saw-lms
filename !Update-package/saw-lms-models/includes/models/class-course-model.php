<?php
/**
 * Course Model
 *
 * Model for working with the wp_saw_lms_courses structured table.
 * Provides methods for CRUD operations, caching, and advanced queries.
 *
 * This model replaces the old postmeta approach with a single structured table,
 * improving performance from ~80 SQL queries per course to just 1 query.
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
 * SAW_LMS_Course_Model Class
 *
 * Handles all database operations for courses.
 *
 * @since 3.0.0
 */
class SAW_LMS_Course_Model {

	/**
	 * Table name (without prefix)
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	const TABLE_NAME = 'saw_lms_courses';

	/**
	 * Cache group
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	const CACHE_GROUP = 'saw_lms_courses';

	/**
	 * Cache TTL (1 hour)
	 *
	 * @since  3.0.0
	 * @var    int
	 */
	const CACHE_TTL = 3600;

	/**
	 * Get course by post_id
	 *
	 * Retrieves course data from the structured table.
	 * Uses caching for better performance.
	 *
	 * @since  3.0.0
	 * @param  int $post_id WordPress post ID.
	 * @return object|null  Course object or null if not found.
	 */
	public static function get_by_post_id( $post_id ) {
		global $wpdb;

		// Sanitize input.
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return null;
		}

		// Try to get from cache.
		$cache_key = 'course_' . $post_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database.
		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$course = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE post_id = %d",
				$post_id
			)
		);

		if ( ! $course ) {
			return null;
		}

		// Decode JSON fields.
		$course = self::decode_json_fields( $course );

		// Cache the result.
		wp_cache_set( $cache_key, $course, self::CACHE_GROUP, self::CACHE_TTL );

		return $course;
	}

	/**
	 * Save or update course data
	 *
	 * Inserts new course or updates existing course in the structured table.
	 * Automatically handles JSON encoding for array fields.
	 *
	 * @since  3.0.0
	 * @param  int   $post_id WordPress post ID.
	 * @param  array $data    Course data (column_name => value).
	 * @return int|false      Course ID on success, false on failure.
	 */
	public static function save( $post_id, $data ) {
		global $wpdb;

		// Sanitize post_id.
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		// Encode JSON fields.
		$data = self::encode_json_fields( $data );

		// Add timestamps.
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
	 * Delete course data
	 *
	 * Removes course from the structured table.
	 * Note: This does NOT delete the WordPress post itself.
	 *
	 * @since  3.0.0
	 * @param  int  $post_id WordPress post ID.
	 * @return bool          True on success, false on failure.
	 */
	public static function delete( $post_id ) {
		global $wpdb;

		// Sanitize post_id.
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		$table = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete(
			$table,
			array( 'post_id' => $post_id ),
			array( '%d' )
		);

		// Invalidate cache.
		self::invalidate_cache( $post_id );

		return ( false !== $result );
	}

	/**
	 * Get courses with filtering and sorting
	 *
	 * Retrieves multiple courses based on various criteria.
	 * Supports pagination, filtering, and sorting.
	 *
	 * @since  3.0.0
	 * @param  array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type string $access_mode       Filter by access mode (open/paid/restricted).
	 *     @type bool   $featured          Filter by featured status.
	 *     @type bool   $is_archived       Filter by archived status.
	 *     @type int    $min_price         Minimum price.
	 *     @type int    $max_price         Maximum price.
	 *     @type string $enrollment_type   Filter by enrollment type.
	 *     @type string $order_by          Column to sort by (default: created_at).
	 *     @type string $order             Sort direction (ASC/DESC, default: DESC).
	 *     @type int    $limit             Number of results (default: 10).
	 *     @type int    $offset            Results offset (default: 0).
	 * }
	 * @return array Array of course objects.
	 */
	public static function get_courses( $args = array() ) {
		global $wpdb;

		// Default arguments.
		$defaults = array(
			'access_mode'     => '',
			'featured'        => null,
			'is_archived'     => 0,
			'min_price'       => null,
			'max_price'       => null,
			'enrollment_type' => '',
			'order_by'        => 'created_at',
			'order'           => 'DESC',
			'limit'           => 10,
			'offset'          => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		// Build cache key from args.
		$cache_key = 'courses_' . md5( wp_json_encode( $args ) );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Build SQL query.
		$table = $wpdb->prefix . self::TABLE_NAME;
		$where = array( '1=1' );

		// Access mode filter.
		if ( ! empty( $args['access_mode'] ) ) {
			$where[] = $wpdb->prepare( 'access_mode = %s', sanitize_text_field( $args['access_mode'] ) );
		}

		// Featured filter.
		if ( null !== $args['featured'] ) {
			$where[] = $wpdb->prepare( 'featured = %d', (int) $args['featured'] );
		}

		// Archived filter.
		$where[] = $wpdb->prepare( 'is_archived = %d', (int) $args['is_archived'] );

		// Price range.
		if ( null !== $args['min_price'] ) {
			$where[] = $wpdb->prepare( 'price >= %f', floatval( $args['min_price'] ) );
		}

		if ( null !== $args['max_price'] ) {
			$where[] = $wpdb->prepare( 'price <= %f', floatval( $args['max_price'] ) );
		}

		// Enrollment type filter.
		if ( ! empty( $args['enrollment_type'] ) ) {
			$where[] = $wpdb->prepare( 'enrollment_type = %s', sanitize_text_field( $args['enrollment_type'] ) );
		}

		// Build WHERE clause.
		$where_sql = implode( ' AND ', $where );

		// Sanitize ORDER BY (whitelist).
		$allowed_order_by = array(
			'id',
			'post_id',
			'price',
			'created_at',
			'updated_at',
			'start_date',
			'end_date',
			'featured_order',
		);

		$order_by = in_array( $args['order_by'], $allowed_order_by, true ) ? $args['order_by'] : 'created_at';

		// Sanitize ORDER direction.
		$order = ( 'ASC' === strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';

		// Pagination.
		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		// Final query.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			"SELECT * FROM {$table}
			WHERE {$where_sql}
			ORDER BY {$order_by} {$order}
			LIMIT {$limit} OFFSET {$offset}"
		);

		if ( ! $results ) {
			return array();
		}

		// Decode JSON fields for each course.
		$courses = array_map( array( self::class, 'decode_json_fields' ), $results );

		// Cache the results.
		wp_cache_set( $cache_key, $courses, self::CACHE_GROUP, self::CACHE_TTL );

		return $courses;
	}

	/**
	 * Count courses
	 *
	 * Returns the total number of courses matching the criteria.
	 *
	 * @since  3.0.0
	 * @param  array $args Same arguments as get_courses().
	 * @return int         Number of courses.
	 */
	public static function count_courses( $args = array() ) {
		global $wpdb;

		// Default arguments.
		$defaults = array(
			'access_mode'     => '',
			'featured'        => null,
			'is_archived'     => 0,
			'min_price'       => null,
			'max_price'       => null,
			'enrollment_type' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Build cache key.
		$cache_key = 'courses_count_' . md5( wp_json_encode( $args ) );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// Build SQL query (same WHERE logic as get_courses).
		$table = $wpdb->prefix . self::TABLE_NAME;
		$where = array( '1=1' );

		if ( ! empty( $args['access_mode'] ) ) {
			$where[] = $wpdb->prepare( 'access_mode = %s', sanitize_text_field( $args['access_mode'] ) );
		}

		if ( null !== $args['featured'] ) {
			$where[] = $wpdb->prepare( 'featured = %d', (int) $args['featured'] );
		}

		$where[] = $wpdb->prepare( 'is_archived = %d', (int) $args['is_archived'] );

		if ( null !== $args['min_price'] ) {
			$where[] = $wpdb->prepare( 'price >= %f', floatval( $args['min_price'] ) );
		}

		if ( null !== $args['max_price'] ) {
			$where[] = $wpdb->prepare( 'price <= %f', floatval( $args['max_price'] ) );
		}

		if ( ! empty( $args['enrollment_type'] ) ) {
			$where[] = $wpdb->prepare( 'enrollment_type = %s', sanitize_text_field( $args['enrollment_type'] ) );
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}" );

		// Cache the result.
		wp_cache_set( $cache_key, (int) $count, self::CACHE_GROUP, self::CACHE_TTL );

		return (int) $count;
	}

	/**
	 * Invalidate cache for a course
	 *
	 * Clears all cache related to a specific course.
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

		// Delete specific course cache.
		wp_cache_delete( 'course_' . $post_id, self::CACHE_GROUP );

		// Flush group cache (for queries like get_courses).
		wp_cache_flush_group( self::CACHE_GROUP );

		/**
		 * Fires after course cache is invalidated.
		 *
		 * @since 3.0.0
		 * @param int $post_id Course post ID.
		 */
		do_action( 'saw_lms_course_cache_invalidated', $post_id );
	}

	/**
	 * Encode JSON fields
	 *
	 * Converts array fields to JSON strings for database storage.
	 *
	 * @since  3.0.0
	 * @param  array $data Course data array.
	 * @return array       Data with JSON-encoded fields.
	 */
	private static function encode_json_fields( $data ) {
		$json_fields = array(
			'prerequisite_courses',
			'prerequisite_achievements',
			'instructors',
			'co_instructors',
			'documents', // For sections.
		);

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
	 * @param  object $course Course object from database.
	 * @return object         Course object with decoded JSON fields.
	 */
	private static function decode_json_fields( $course ) {
		if ( ! $course ) {
			return $course;
		}

		$json_fields = array(
			'prerequisite_courses',
			'prerequisite_achievements',
			'instructors',
			'co_instructors',
			'documents',
		);

		foreach ( $json_fields as $field ) {
			if ( isset( $course->$field ) && is_string( $course->$field ) ) {
				$decoded = json_decode( $course->$field, true );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					$course->$field = $decoded;
				}
			}
		}

		return $course;
	}
}
