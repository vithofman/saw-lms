<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Email_Queue_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_email_queue (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			to_email varchar(255) NOT NULL,
			subject varchar(500) NOT NULL,
			message longtext NOT NULL,
			headers text DEFAULT NULL,
			attachments longtext DEFAULT NULL COMMENT 'JSON array of file paths',
			status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, sent, failed',
			scheduled_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			sent_at datetime DEFAULT NULL,
			attempts int(11) UNSIGNED NOT NULL DEFAULT 0,
			last_error text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY status (status),
			KEY scheduled_at (scheduled_at)
		) $charset_collate COMMENT='Async email queue';";
		return $sql;
	}
}