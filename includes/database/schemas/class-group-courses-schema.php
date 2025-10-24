<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Group_Courses_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_group_courses (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			group_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			deadline datetime DEFAULT NULL COMMENT 'Optional completion deadline',
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY course_id (course_id),
			UNIQUE KEY unique_group_course (group_id, course_id)
		) $charset_collate COMMENT='Courses assigned to groups';";
		return $sql;
	}
}