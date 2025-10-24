<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Drip_Content_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_drip_content (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			course_id bigint(20) UNSIGNED NOT NULL,
			content_type varchar(20) NOT NULL COMMENT 'lesson, quiz',
			content_id bigint(20) UNSIGNED NOT NULL,
			unlock_after_days int(11) UNSIGNED NOT NULL COMMENT 'Days after enrollment',
			unlock_condition varchar(50) DEFAULT NULL COMMENT 'previous_completed, date, manual',
			PRIMARY KEY (id),
			KEY course_id (course_id),
			KEY content_type (content_type),
			KEY content_id (content_id)
		) $charset_collate COMMENT='Drip content rules';";
		return $sql;
	}
}