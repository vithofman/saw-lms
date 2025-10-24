<?php
if ( ! defined( 'WPINC' ) ) { die; }
class SAW_LMS_Cache_Schema {
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_cache (
			cache_key varchar(191) NOT NULL,
			cache_value longtext NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY (cache_key),
			KEY expires_at (expires_at)
		) $charset_collate COMMENT='Database cache driver';";
		return $sql;
	}
}