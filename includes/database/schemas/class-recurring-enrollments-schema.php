<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Recurring_Enrollments_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_recurring_enrollments (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			enrollment_id bigint(20) UNSIGNED NOT NULL COMMENT 'Current enrollment',
			repeat_period int(11) UNSIGNED NOT NULL COMMENT 'Months between occurrences',
			last_completed_at datetime DEFAULT NULL,
			next_due_date datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, paused, completed',
			reminder_sent_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY enrollment_id (enrollment_id),
			KEY next_due_date (next_due_date),
			KEY status (status),
			UNIQUE KEY unique_recurring (user_id, course_id)
		) $charset_collate COMMENT='BOZP recurring enrollment tracking';";
		return $sql;
	}
}