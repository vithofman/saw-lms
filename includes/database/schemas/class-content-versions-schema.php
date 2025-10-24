<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Content_Versions_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_content_versions (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			content_type varchar(20) NOT NULL COMMENT 'lesson, quiz, course',
			content_id bigint(20) UNSIGNED NOT NULL,
			version_number int(11) UNSIGNED NOT NULL,
			data longtext NOT NULL COMMENT 'JSON snapshot of content',
			created_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			change_note text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY content_type (content_type),
			KEY content_id (content_id),
			KEY version_number (version_number),
			KEY created_at (created_at)
		) $charset_collate COMMENT='Version history for compliance';";
		return $sql;
	}
}