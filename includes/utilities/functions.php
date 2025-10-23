<?php
/**
 * Global Helper Functions
 * 
 * Reusable utility functions for common tasks throughout the plugin
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/utilities
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Get Error Handler instance
 *
 * @since  1.0.0
 * @return SAW_LMS_Error_Handler
 */
function saw_lms_error_handler() {
	return SAW_LMS_Error_Handler::init();
}

/**
 * Get Logger instance
 *
 * @since  1.0.0
 * @return SAW_LMS_Logger
 */
function saw_lms_logger() {
	return SAW_LMS_Logger::init();
}

/**
 * Log an error (shorthand for error handler)
 *
 * @since 1.0.0
 * @param string $type    Error type (emergency, alert, critical, error, warning, notice, info, debug)
 * @param string $message Error message
 * @param array  $context Additional context
 */
function saw_lms_log_error( $type, $message, $context = array() ) {
	$handler = saw_lms_error_handler();
	$handler->log_error( $type, $message, $context );
}

/**
 * Safe execution wrapper with try-catch
 *
 * @since  1.0.0
 * @param  callable $callback The callback to execute
 * @param  mixed    $default  Default value to return on error
 * @return mixed
 */
function saw_lms_safe_get( $callback, $default = null ) {
	try {
		return call_user_func( $callback );
	} catch ( Exception $e ) {
		saw_lms_log_error( 'error', $e->getMessage(), array(
			'exception' => $e,
			'callback'  => 'anonymous',
		) );
		return $default;
	}
}

/**
 * Get user enrollment details
 *
 * @since  1.0.0
 * @param  int $user_id   User ID
 * @param  int $course_id Course ID
 * @return object|null Enrollment object or null if not found
 */
function saw_lms_get_user_enrollment( $user_id, $course_id ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'saw_lms_enrollments';
	
	$enrollment = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} 
			WHERE user_id = %d 
			AND course_id = %d 
			AND status != 'archived'
			ORDER BY created_at DESC 
			LIMIT 1",
			$user_id,
			$course_id
		)
	);
	
	return $enrollment;
}

/**
 * Check if user is enrolled in course
 *
 * @since  1.0.0
 * @param  int $user_id   User ID
 * @param  int $course_id Course ID
 * @return bool
 */
function saw_lms_is_user_enrolled( $user_id, $course_id ) {
	$enrollment = saw_lms_get_user_enrollment( $user_id, $course_id );
	
	if ( ! $enrollment ) {
		return false;
	}
	
	// Check if enrollment is active (not expired)
	if ( $enrollment->status === 'expired' ) {
		return false;
	}
	
	if ( $enrollment->expires_at ) {
		$expires_at = strtotime( $enrollment->expires_at );
		if ( $expires_at < time() ) {
			return false;
		}
	}
	
	return true;
}

/**
 * Get lesson progress for user
 *
 * @since  1.0.0
 * @param  int $user_id      User ID
 * @param  int $lesson_id    Lesson ID
 * @param  int $enrollment_id Enrollment ID (optional, will be fetched if not provided)
 * @return object|null Progress object or null if not found
 */
function saw_lms_get_lesson_progress( $user_id, $lesson_id, $enrollment_id = null ) {
	global $wpdb;
	
	// Get enrollment ID if not provided
	if ( ! $enrollment_id ) {
		// Get course ID from lesson
		$course_id = get_post_meta( $lesson_id, '_saw_lms_course_id', true );
		
		if ( ! $course_id ) {
			return null;
		}
		
		$enrollment = saw_lms_get_user_enrollment( $user_id, $course_id );
		
		if ( ! $enrollment ) {
			return null;
		}
		
		$enrollment_id = $enrollment->id;
	}
	
	$table_name = $wpdb->prefix . 'saw_lms_progress';
	
	$progress = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} 
			WHERE enrollment_id = %d 
			AND lesson_id = %d 
			LIMIT 1",
			$enrollment_id,
			$lesson_id
		)
	);
	
	return $progress;
}

/**
 * Format duration in minutes to human readable format
 *
 * @since  1.0.0
 * @param  int $minutes Total minutes
 * @return string Formatted duration (e.g., "2 hod 30 min")
 */
function saw_lms_format_duration( $minutes ) {
	if ( $minutes < 1 ) {
		return '0 min';
	}
	
	$hours = floor( $minutes / 60 );
	$mins  = $minutes % 60;
	
	$parts = array();
	
	if ( $hours > 0 ) {
		$parts[] = sprintf( _n( '%d hodina', '%d hodiny', $hours, 'saw-lms' ), $hours );
	}
	
	if ( $mins > 0 ) {
		$parts[] = sprintf( _n( '%d minuta', '%d minut', $mins, 'saw-lms' ), $mins );
	}
	
	return implode( ' ', $parts );
}

/**
 * Format seconds to human readable format
 *
 * @since  1.0.0
 * @param  int $seconds Total seconds
 * @return string Formatted time (e.g., "1:30:45")
 */
function saw_lms_format_seconds( $seconds ) {
	if ( $seconds < 1 ) {
		return '0:00';
	}
	
	$hours   = floor( $seconds / 3600 );
	$minutes = floor( ( $seconds % 3600 ) / 60 );
	$secs    = $seconds % 60;
	
	if ( $hours > 0 ) {
		return sprintf( '%d:%02d:%02d', $hours, $minutes, $secs );
	}
	
	return sprintf( '%d:%02d', $minutes, $secs );
}

/**
 * Sanitize array recursively
 *
 * @since  1.0.0
 * @param  array $array Array to sanitize
 * @return array Sanitized array
 */
function saw_lms_sanitize_array( $array ) {
	$sanitized = array();
	
	foreach ( $array as $key => $value ) {
		$key = sanitize_key( $key );
		
		if ( is_array( $value ) ) {
			$sanitized[ $key ] = saw_lms_sanitize_array( $value );
		} else {
			$sanitized[ $key ] = sanitize_text_field( $value );
		}
	}
	
	return $sanitized;
}

/**
 * Get course structure
 *
 * @since  1.0.0
 * @param  int $course_id Course ID
 * @return array|null Course structure or null
 */
function saw_lms_get_course_structure( $course_id ) {
	$structure_json = get_post_meta( $course_id, '_saw_lms_course_structure', true );
	
	if ( empty( $structure_json ) ) {
		return null;
	}
	
	$structure = json_decode( $structure_json, true );
	
	return $structure;
}

/**
 * Calculate course completion percentage
 *
 * @since  1.0.0
 * @param  int $user_id   User ID
 * @param  int $course_id Course ID
 * @return float Completion percentage (0-100)
 */
function saw_lms_get_course_completion( $user_id, $course_id ) {
	global $wpdb;
	
	// Get enrollment
	$enrollment = saw_lms_get_user_enrollment( $user_id, $course_id );
	
	if ( ! $enrollment ) {
		return 0;
	}
	
	// Get course structure
	$structure = saw_lms_get_course_structure( $course_id );
	
	if ( ! $structure || empty( $structure['sections'] ) ) {
		return 0;
	}
	
	// Count total lessons and quizzes
	$total_items = 0;
	$lesson_ids  = array();
	$quiz_ids    = array();
	
	foreach ( $structure['sections'] as $section ) {
		if ( empty( $section['items'] ) ) {
			continue;
		}
		
		foreach ( $section['items'] as $item ) {
			$total_items++;
			
			if ( $item['type'] === 'lesson' ) {
				$lesson_ids[] = $item['id'];
			} elseif ( $item['type'] === 'quiz' ) {
				$quiz_ids[] = $item['id'];
			}
		}
	}
	
	if ( $total_items === 0 ) {
		return 0;
	}
	
	// Count completed items
	$completed = 0;
	
	// Count completed lessons
	if ( ! empty( $lesson_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $lesson_ids ), '%d' ) );
		$progress_table = $wpdb->prefix . 'saw_lms_progress';
		
		$completed_lessons = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$progress_table} 
				WHERE enrollment_id = %d 
				AND lesson_id IN ({$placeholders}) 
				AND status = 'completed'",
				array_merge( array( $enrollment->id ), $lesson_ids )
			)
		);
		
		$completed += $completed_lessons;
	}
	
	// Count passed quizzes
	if ( ! empty( $quiz_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $quiz_ids ), '%d' ) );
		$attempts_table = $wpdb->prefix . 'saw_lms_quiz_attempts';
		
		$passed_quizzes = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT quiz_id) FROM {$attempts_table} 
				WHERE enrollment_id = %d 
				AND quiz_id IN ({$placeholders}) 
				AND passed = 1",
				array_merge( array( $enrollment->id ), $quiz_ids )
			)
		);
		
		$completed += $passed_quizzes;
	}
	
	// Calculate percentage
	$percentage = ( $completed / $total_items ) * 100;
	
	return round( $percentage, 2 );
}

/**
 * Get user's current points balance
 *
 * @since  1.0.0
 * @param  int $user_id User ID
 * @return int Points balance
 */
function saw_lms_get_user_points( $user_id ) {
	// Try to get from cache first
	$balance = get_user_meta( $user_id, '_saw_lms_points_balance', true );
	
	if ( $balance !== '' ) {
		return (int) $balance;
	}
	
	// Calculate from ledger
	global $wpdb;
	$table_name = $wpdb->prefix . 'saw_lms_points_ledger';
	
	$balance = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(amount), 0) FROM {$table_name} WHERE user_id = %d",
			$user_id
		)
	);
	
	// Update cache
	update_user_meta( $user_id, '_saw_lms_points_balance', $balance );
	
	return (int) $balance;
}

/**
 * Format date for display
 *
 * @since  1.0.0
 * @param  string $date Date string
 * @param  string $format Date format (default: WordPress date format)
 * @return string Formatted date
 */
function saw_lms_format_date( $date, $format = null ) {
	if ( empty( $date ) ) {
		return '';
	}
	
	if ( null === $format ) {
		$format = get_option( 'date_format' );
	}
	
	$timestamp = strtotime( $date );
	
	if ( ! $timestamp ) {
		return $date;
	}
	
	return date_i18n( $format, $timestamp );
}

/**
 * Check if user has capability for action
 *
 * @since  1.0.0
 * @param  string $capability Capability to check
 * @param  int    $user_id    User ID (default: current user)
 * @return bool
 */
function saw_lms_user_can( $capability, $user_id = null ) {
	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}
	
	$user = get_user_by( 'id', $user_id );
	
	if ( ! $user ) {
		return false;
	}
	
	return user_can( $user, $capability );
}

/**
 * Get plugin settings
 *
 * @since  1.0.0
 * @param  string $key     Setting key (optional)
 * @param  mixed  $default Default value if setting not found
 * @return mixed Setting value or all settings
 */
function saw_lms_get_setting( $key = null, $default = null ) {
	$settings = get_option( 'saw_lms_settings', array() );
	
	if ( null === $key ) {
		return $settings;
	}
	
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Update plugin setting
 *
 * @since 1.0.0
 * @param string $key   Setting key
 * @param mixed  $value Setting value
 * @return bool
 */
function saw_lms_update_setting( $key, $value ) {
	$settings = get_option( 'saw_lms_settings', array() );
	$settings[ $key ] = $value;
	
	return update_option( 'saw_lms_settings', $settings );
}

/**
 * Debug helper - only logs if debug mode is enabled
 *
 * @since 1.0.0
 * @param mixed $data Data to log
 * @param string $label Label for the log entry
 */
function saw_lms_debug( $data, $label = 'Debug' ) {
	if ( ! saw_lms_get_setting( 'debug_mode', false ) ) {
		return;
	}
	
	$logger = saw_lms_logger();
	
	if ( is_array( $data ) || is_object( $data ) ) {
		$data = print_r( $data, true );
	}
	
	$logger->debug( $label . ': ' . $data );
}