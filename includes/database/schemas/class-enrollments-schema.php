<?php
/**
 * Enrollments Table Schema
 *
 * Defines the SQL structure for the wp_saw_lms_enrollments table.
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
 * SAW_LMS_Enrollments_Schema Class
 *
 * Provides SQL definition for the enrollments table.
 *
 * @since 3.0.0
 */
class SAW_LMS_Enrollments_Schema {

	/**
	 * Get SQL for creating the enrollments table
	 *
	 * Stores user enrollments in courses.
	 *
	 * @since 3.0.0
	 * @param string $prefix Database table prefix.
	 * @param string $charset_collate Charset and collation.
	 * @return array Array of SQL statements.
	 */
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();

		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_enrollments (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			group_id bigint(20) UNSIGNED DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, completed, suspended, expired',
			enrolled_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at datetime DEFAULT NULL,
			progress decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage (0-100)',
			last_activity_at datetime DEFAULT NULL,
			certificate_url varchar(500) DEFAULT NULL,
			certificate_issued_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY group_id (group_id),
			KEY status (status),
			KEY enrolled_at (enrolled_at),
			UNIQUE KEY unique_enrollment (user_id, course_id)
		) $charset_collate COMMENT='User course enrollments';";

		return $sql;
	}
}