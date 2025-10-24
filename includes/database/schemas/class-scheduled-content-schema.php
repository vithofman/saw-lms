<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Scheduled_Content_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_scheduled_content (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			content_type varchar(20) NOT NULL COMMENT 'lesson, quiz, course',
			content_id bigint(20) UNSIGNED NOT NULL,
			scheduled_at datetime NOT NULL,
			repeat_type varchar(20) DEFAULT NULL COMMENT 'daily, weekly, monthly',
			repeat_period int(11) UNSIGNED DEFAULT NULL COMMENT 'Repeat interval',
			status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, completed, failed',
			created_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY content_type (content_type),
			KEY content_id (content_id),
			KEY scheduled_at (scheduled_at),
			KEY status (status)
		) $charset_collate COMMENT='Scheduled content publishing';";
		return $sql;
	}
}