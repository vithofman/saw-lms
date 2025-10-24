<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Groups_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_groups (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text DEFAULT NULL,
			created_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			status varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, suspended, archived',
			max_members int(11) UNSIGNED DEFAULT NULL COMMENT 'NULL = unlimited',
			PRIMARY KEY (id),
			KEY created_by (created_by),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate COMMENT='Corporate license groups';";
		return $sql;
	}
}