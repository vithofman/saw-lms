<?php
/**
 * Cache Helper
 * 
 * Utility class providing:
 * - Standard cache key generators for consistency
 * - Recommended TTL values for different data types
 * - Cache invalidation helpers
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/cache
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Cache_Helper Class
 * 
 * Static helper class for cache operations
 * 
 * @since 1.0.0
 */
class SAW_LMS_Cache_Helper {

	/**
	 * Generate cache key for user enrollment
	 *
	 * @since  1.0.0
	 * @param  int $user_id   User ID
	 * @param  int $course_id Course ID
	 * @return string         Cache key
	 */
	public static function enrollment_key( $user_id, $course_id ) {
		return sprintf( 'enrollment_%d_%d', $user_id, $course_id );
	}

	/**
	 * Generate cache key for user's all enrollments
	 *
	 * @since  1.0.0
	 * @param  int $user_id User ID
	 * @return string       Cache key
	 */
	public static function user_enrollments_key( $user_id ) {
		return sprintf( 'user_enrollments_%d', $user_id );
	}

	/**
	 * Generate cache key for course structure
	 *
	 * @since  1.0.0
	 * @param  int $course_id Course ID
	 * @return string         Cache key
	 */
	public static function course_structure_key( $course_id ) {
		return sprintf( 'course_structure_%d', $course_id );
	}

	/**
	 * Generate cache key for course statistics
	 *
	 * @since  1.0.0
	 * @param  int $course_id Course ID
	 * @return string         Cache key
	 */
	public static function course_stats_key( $course_id ) {
		return sprintf( 'course_stats_%d', $course_id );
	}

	/**
	 * Generate cache key for user progress in course
	 *
	 * @since  1.0.0
	 * @param  int $user_id   User ID
	 * @param  int $course_id Course ID
	 * @return string         Cache key
	 */
	public static function user_progress_key( $user_id, $course_id ) {
		return sprintf( 'user_progress_%d_%d', $user_id, $course_id );
	}

	/**
	 * Generate cache key for lesson progress
	 *
	 * @since  1.0.0
	 * @param  int $user_id   User ID
	 * @param  int $lesson_id Lesson ID
	 * @return string         Cache key
	 */
	public static function lesson_progress_key( $user_id, $lesson_id ) {
		return sprintf( 'lesson_progress_%d_%d', $user_id, $lesson_id );
	}

	/**
	 * Generate cache key for quiz attempts
	 *
	 * @since  1.0.0
	 * @param  int $user_id User ID
	 * @param  int $quiz_id Quiz ID
	 * @return string       Cache key
	 */
	public static function quiz_attempts_key( $user_id, $quiz_id ) {
		return sprintf( 'quiz_attempts_%d_%d', $user_id, $quiz_id );
	}

	/**
	 * Generate cache key for group statistics
	 *
	 * @since  1.0.0
	 * @param  int $group_id Group ID
	 * @return string        Cache key
	 */
	public static function group_stats_key( $group_id ) {
		return sprintf( 'group_stats_%d', $group_id );
	}

	/**
	 * Generate cache key for group members
	 *
	 * @since  1.0.0
	 * @param  int $group_id Group ID
	 * @return string        Cache key
	 */
	public static function group_members_key( $group_id ) {
		return sprintf( 'group_members_%d', $group_id );
	}

	/**
	 * Generate cache key for user's points balance
	 *
	 * @since  1.0.0
	 * @param  int $user_id User ID
	 * @return string       Cache key
	 */
	public static function user_points_key( $user_id ) {
		return sprintf( 'user_points_%d', $user_id );
	}

	/**
	 * Generate cache key for leaderboard
	 *
	 * @since  1.0.0
	 * @param  string $type Leaderboard type (global, course, group, monthly)
	 * @param  int    $id   Optional ID (course_id or group_id)
	 * @return string       Cache key
	 */
	public static function leaderboard_key( $type = 'global', $id = null ) {
		if ( $id ) {
			return sprintf( 'leaderboard_%s_%d', $type, $id );
		}
		return sprintf( 'leaderboard_%s', $type );
	}

	/**
	 * Generate cache key for certificates list
	 *
	 * @since  1.0.0
	 * @param  int $user_id User ID
	 * @return string       Cache key
	 */
	public static function user_certificates_key( $user_id ) {
		return sprintf( 'user_certificates_%d', $user_id );
	}

	/**
	 * Generate cache key for course availability check
	 *
	 * @since  1.0.0
	 * @param  int $user_id   User ID
	 * @param  int $course_id Course ID
	 * @return string         Cache key
	 */
	public static function course_available_key( $user_id, $course_id ) {
		return sprintf( 'course_available_%d_%d', $user_id, $course_id );
	}

	/**
	 * Generate cache key for custom documents list
	 *
	 * @since  1.0.0
	 * @param  int $group_id  Group ID
	 * @param  int $lesson_id Lesson ID
	 * @return string         Cache key
	 */
	public static function custom_docs_key( $group_id, $lesson_id ) {
		return sprintf( 'custom_docs_%d_%d', $group_id, $lesson_id );
	}

	/**
	 * Get recommended TTL for data type
	 *
	 * Returns Time-To-Live in seconds based on data volatility.
	 * These are reasonable defaults - adjust based on your needs.
	 *
	 * @since  1.0.0
	 * @param  string $type Data type
	 * @return int          TTL in seconds
	 */
	public static function get_ttl( $type ) {
		$ttls = array(
			// Frequently changing data (5 minutes)
			'balance'          => 300,
			'cart'             => 300,
			'session'          => 300,

			// Moderately changing data (15 minutes)
			'enrollment'       => 900,
			'progress'         => 900,
			'stats'            => 900,
			'leaderboard'      => 900,
			'attempts'         => 900,
			'group_members'    => 900,

			// Relatively stable data (1 hour)
			'structure'        => 3600,
			'course_data'      => 3600,
			'quiz_data'        => 3600,
			'user_data'        => 3600,
			'certificates'     => 3600,
			'custom_docs'      => 3600,

			// Stable data (6 hours)
			'content_versions' => 21600,
			'snapshots'        => 21600,

			// Very stable data (24 hours)
			'settings'         => 86400,
			'course_list'      => 86400,

			// Default (15 minutes)
			'default'          => 900,
		);

		return isset( $ttls[ $type ] ) ? $ttls[ $type ] : $ttls['default'];
	}

	/**
	 * Invalidate enrollment-related cache
	 *
	 * Called when enrollment status changes.
	 *
	 * @since 1.0.0
	 * @param int $user_id   User ID
	 * @param int $course_id Course ID
	 */
	public static function invalidate_enrollment( $user_id, $course_id ) {
		$cache = SAW_LMS_Cache_Manager::init();

		$keys_to_delete = array(
			self::enrollment_key( $user_id, $course_id ),
			self::user_enrollments_key( $user_id ),
			self::user_progress_key( $user_id, $course_id ),
			self::course_stats_key( $course_id ),
			self::course_available_key( $user_id, $course_id ),
		);

		foreach ( $keys_to_delete as $key ) {
			$cache->delete( $key );
		}

		SAW_LMS_Logger::init()->debug( 'Enrollment cache invalidated', array(
			'user_id' => $user_id,
			'course_id' => $course_id,
			'keys_deleted' => count( $keys_to_delete ),
		) );
	}

	/**
	 * Invalidate progress-related cache
	 *
	 * Called when user completes lesson or quiz.
	 *
	 * @since 1.0.0
	 * @param int $user_id   User ID
	 * @param int $course_id Course ID
	 * @param int $item_id   Lesson or Quiz ID
	 */
	public static function invalidate_progress( $user_id, $course_id, $item_id ) {
		$cache = SAW_LMS_Cache_Manager::init();

		$keys_to_delete = array(
			self::user_progress_key( $user_id, $course_id ),
			self::lesson_progress_key( $user_id, $item_id ),
			self::course_stats_key( $course_id ),
			self::enrollment_key( $user_id, $course_id ),
		);

		foreach ( $keys_to_delete as $key ) {
			$cache->delete( $key );
		}

		SAW_LMS_Logger::init()->debug( 'Progress cache invalidated', array(
			'user_id' => $user_id,
			'course_id' => $course_id,
			'item_id' => $item_id,
		) );
	}

	/**
	 * Invalidate points-related cache
	 *
	 * Called when user earns or loses points.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 */
	public static function invalidate_points( $user_id ) {
		$cache = SAW_LMS_Cache_Manager::init();

		$keys_to_delete = array(
			self::user_points_key( $user_id ),
			self::leaderboard_key( 'global' ),
			self::leaderboard_key( 'monthly' ),
		);

		foreach ( $keys_to_delete as $key ) {
			$cache->delete( $key );
		}

		SAW_LMS_Logger::init()->debug( 'Points cache invalidated', array(
			'user_id' => $user_id,
		) );
	}

	/**
	 * Invalidate group-related cache
	 *
	 * Called when group membership changes.
	 *
	 * @since 1.0.0
	 * @param int $group_id Group ID
	 */
	public static function invalidate_group( $group_id ) {
		$cache = SAW_LMS_Cache_Manager::init();

		$keys_to_delete = array(
			self::group_stats_key( $group_id ),
			self::group_members_key( $group_id ),
			self::leaderboard_key( 'group', $group_id ),
		);

		foreach ( $keys_to_delete as $key ) {
			$cache->delete( $key );
		}

		SAW_LMS_Logger::init()->debug( 'Group cache invalidated', array(
			'group_id' => $group_id,
		) );
	}

	/**
	 * Invalidate course structure cache
	 *
	 * Called when course structure changes.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID
	 */
	public static function invalidate_course_structure( $course_id ) {
		$cache = SAW_LMS_Cache_Manager::init();

		$cache->delete( self::course_structure_key( $course_id ) );

		SAW_LMS_Logger::init()->debug( 'Course structure cache invalidated', array(
			'course_id' => $course_id,
		) );
	}

	/**
	 * Invalidate all cache for a user
	 *
	 * Nuclear option - use sparingly.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 */
	public static function invalidate_user( $user_id ) {
		$cache = SAW_LMS_Cache_Manager::init();

		// Get all possible keys for this user
		$patterns = array(
			'enrollment_' . $user_id . '_',
			'user_enrollments_' . $user_id,
			'user_progress_' . $user_id . '_',
			'lesson_progress_' . $user_id . '_',
			'quiz_attempts_' . $user_id . '_',
			'user_points_' . $user_id,
			'user_certificates_' . $user_id,
			'course_available_' . $user_id . '_',
		);

		// Note: This is a simplified approach
		// For production, consider implementing a key tracking system
		// or using cache tags if your driver supports them

		SAW_LMS_Logger::init()->info( 'User cache invalidation requested', array(
			'user_id' => $user_id,
			'note' => 'Full invalidation may require cache flush',
		) );
	}

	/**
	 * Warm up cache for user
	 *
	 * Pre-populate cache with commonly needed data.
	 * Useful after login or enrollment.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 */
	public static function warmup_user_cache( $user_id ) {
		// This would be implemented based on specific needs
		// Example: Pre-cache user's active enrollments

		SAW_LMS_Logger::init()->debug( 'User cache warmup started', array(
			'user_id' => $user_id,
		) );

		// Pre-cache user enrollments
		// $enrollments = saw_lms_get_user_enrollments($user_id);
		// Cache is automatically populated by the function

		do_action( 'saw_lms_cache_warmup_user', $user_id );
	}

	/**
	 * Get cache statistics summary
	 *
	 * @since  1.0.0
	 * @return array Statistics array
	 */
	public static function get_stats_summary() {
		$cache = SAW_LMS_Cache_Manager::init();

		return array(
			'driver' => $cache->get_driver_name(),
			'available' => $cache->is_available(),
			'stats' => $cache->get_stats(),
		);
	}

	/**
	 * Format cache size for display
	 *
	 * @since  1.0.0
	 * @param  int $bytes Size in bytes
	 * @return string     Formatted size (e.g., "1.5 MB")
	 */
	public static function format_size( $bytes ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );
		
		$bytes /= pow( 1024, $pow );
		
		return round( $bytes, 2 ) . ' ' . $units[ $pow ];
	}
}