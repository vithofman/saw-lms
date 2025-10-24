<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Group_Content_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_group_content (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			group_id bigint(20) UNSIGNED NOT NULL,
			title varchar(255) NOT NULL,
			content longtext DEFAULT NULL,
			file_url varchar(500) DEFAULT NULL,
			created_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY created_by (created_by),
			KEY created_at (created_at)
		) $charset_collate COMMENT='Custom documents for groups';";
		return $sql;
	}
}