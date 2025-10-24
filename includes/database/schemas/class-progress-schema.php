<?php
/**
 * Progress Table Schema
 *
 * Defines the SQL structure for the wp_saw_lms_progress table.
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
 * SAW_LMS_Progress_Schema Class
 *
 * Provides SQL definition for the progress table.
 *
 * @since 3.0.0
 */
class SAW_LMS_Progress_Schema {

	/**
	 * Get SQL for creating the progress table
	 *
	 * Tracks student progress through individual content items.
	 *
	 * @since 3.0.0
	 * @param string $prefix Database table prefix.
	 * @param string $charset_collate Charset and collation.
	 * @return array Array of SQL statements.
	 */
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();

		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_progress (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) UNSIGNED NOT NULL,
			content_type varchar(20) NOT NULL COMMENT 'lesson, quiz, assignment',
			content_id bigint(20) UNSIGNED NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'not_started' COMMENT 'not_started, in_progress, completed, failed',
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			time_spent int(11) UNSIGNED DEFAULT 0 COMMENT 'Seconds spent on content',
			last_position text DEFAULT NULL COMMENT 'JSON: video timestamp, page number, etc.',
			PRIMARY KEY (id),
			KEY enrollment_id (enrollment_id),
			KEY content_type (content_type),
			KEY content_id (content_id),
			KEY status (status),
			UNIQUE KEY unique_progress (enrollment_id, content_type, content_id)
		) $charset_collate COMMENT='Detailed progress tracking';";

		return $sql;
	}
}