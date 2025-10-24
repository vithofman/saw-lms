<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Certificates_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_certificates (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) UNSIGNED NOT NULL,
			certificate_code varchar(100) NOT NULL,
			issued_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			template_id bigint(20) UNSIGNED DEFAULT NULL,
			file_url varchar(500) DEFAULT NULL,
			metadata longtext DEFAULT NULL COMMENT 'JSON additional data',
			PRIMARY KEY (id),
			KEY enrollment_id (enrollment_id),
			UNIQUE KEY certificate_code (certificate_code),
			KEY issued_at (issued_at)
		) $charset_collate COMMENT='Issued certificates';";
		return $sql;
	}
}