<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Error_Log_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_error_log (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			level varchar(20) NOT NULL COMMENT 'debug, info, warning, error, critical',
			message text NOT NULL,
			context longtext DEFAULT NULL COMMENT 'JSON context data',
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			url varchar(500) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY level (level),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate COMMENT='Centralized error logging';";
		return $sql;
	}
}