<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Notification_Preferences_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_notification_preferences (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			notification_type varchar(50) NOT NULL COMMENT 'enrollment, completion, certificate, reminder',
			enabled tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			UNIQUE KEY unique_user_notification (user_id, notification_type)
		) $charset_collate COMMENT='User notification settings';";
		return $sql;
	}
}