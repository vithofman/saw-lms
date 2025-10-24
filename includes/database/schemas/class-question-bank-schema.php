<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Question_Bank_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_question_bank (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) UNSIGNED NOT NULL,
			question_type varchar(50) NOT NULL COMMENT 'multiple_choice, true_false, essay, fill_blank',
			question_text longtext NOT NULL,
			options longtext DEFAULT NULL COMMENT 'JSON answer options',
			correct_answer longtext DEFAULT NULL COMMENT 'JSON correct answer(s)',
			points decimal(5,2) NOT NULL DEFAULT 1.00,
			order_index int(11) UNSIGNED NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY quiz_id (quiz_id),
			KEY question_type (question_type),
			KEY order_index (order_index)
		) $charset_collate COMMENT='Quiz question repository';";
		return $sql;
	}
}