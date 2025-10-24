<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Content_Archives_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_content_archives (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			content_type varchar(20) NOT NULL COMMENT 'lesson, quiz, document',
			content_id bigint(20) UNSIGNED NOT NULL,
			archive_url varchar(500) NOT NULL COMMENT 'Path to archived file',
			archived_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			archived_by bigint(20) UNSIGNED NOT NULL,
			reason text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY content_type (content_type),
			KEY content_id (content_id),
			KEY archived_at (archived_at)
		) $charset_collate COMMENT='Archived content files';";
		return $sql;
	}
}