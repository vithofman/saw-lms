<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Achievements_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_achievements (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			achievement_type varchar(50) NOT NULL COMMENT 'badge, milestone, rank',
			achievement_id varchar(100) NOT NULL,
			earned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			metadata longtext DEFAULT NULL COMMENT 'JSON additional data',
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY achievement_type (achievement_type),
			UNIQUE KEY unique_achievement (user_id, achievement_id)
		) $charset_collate COMMENT='User achievements and badges';";
		return $sql;
	}
}