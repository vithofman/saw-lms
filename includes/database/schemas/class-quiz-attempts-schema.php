<?php
/**
 * Quiz Attempts Table Schema
 *
 * Defines the SQL structure for the wp_saw_lms_quiz_attempts table.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/database/schemas
 * @since      3.0.0
 * @version    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Quiz_Attempts_Schema Class
 *
 * Provides SQL definition for the quiz attempts table.
 *
 * @since 3.0.0
 */
class SAW_LMS_Quiz_Attempts_Schema {

	/**
	 * Get SQL for creating the quiz attempts table
	 *
	 * Stores all quiz attempt data including answers and scores.
	 *
	 * @since 3.0.0
	 * @param string $prefix Database table prefix.
	 * @param string $charset_collate Charset and collation.
	 * @return array Array of SQL statements.
	 */
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();

		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_quiz_attempts (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) UNSIGNED NOT NULL,
			quiz_id bigint(20) UNSIGNED NOT NULL,
			attempt_number int(11) UNSIGNED NOT NULL DEFAULT 1,
			started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at datetime DEFAULT NULL,
			time_spent int(11) UNSIGNED DEFAULT 0 COMMENT 'Seconds spent on quiz',
			score decimal(5,2) DEFAULT NULL,
			max_score decimal(5,2) NOT NULL,
			percentage decimal(5,2) DEFAULT NULL,
			passed tinyint(1) DEFAULT NULL,
			answers longtext DEFAULT NULL COMMENT 'JSON array of answers',
			PRIMARY KEY (id),
			KEY enrollment_id (enrollment_id),
			KEY quiz_id (quiz_id),
			KEY started_at (started_at)
		) $charset_collate COMMENT='Quiz attempt records';";

		return $sql;
	}
}