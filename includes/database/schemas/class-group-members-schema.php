<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Group_Members_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_group_members (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			group_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			role varchar(20) NOT NULL DEFAULT 'member' COMMENT 'member, admin',
			joined_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY user_id (user_id),
			UNIQUE KEY unique_membership (group_id, user_id)
		) $charset_collate COMMENT='Group membership';";
		return $sql;
	}
}