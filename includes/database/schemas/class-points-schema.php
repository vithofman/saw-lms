<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Points_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_points (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			points int(11) NOT NULL COMMENT 'Can be negative',
			reason varchar(255) NOT NULL,
			reference_type varchar(50) DEFAULT NULL COMMENT 'course, lesson, quiz',
			reference_id bigint(20) UNSIGNED DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY created_at (created_at),
			KEY reference_type (reference_type, reference_id)
		) $charset_collate COMMENT='Gamification points ledger';";
		return $sql;
	}
}