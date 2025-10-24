<?php
/**
 * Course Products Table Schema
 *
 * Defines the SQL structure for the wp_saw_lms_course_products table.
 * This table enables many-to-many relationship between courses and WooCommerce products.
 *
 * Use cases:
 * - One course linked to multiple products (Basic, Pro, Enterprise editions)
 * - Bundle products (1 product = multiple courses)
 * - Different pricing tiers for same course
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/database/schemas
 * @since      3.1.0
 * @version    3.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Course_Products_Schema Class
 *
 * Provides SQL definition for the course_products linking table.
 *
 * @since 3.1.0
 */
class SAW_LMS_Course_Products_Schema {

	/**
	 * Get SQL for creating the course_products table
	 *
	 * NEW in v3.1.0: WooCommerce Integration Enhancement
	 *
	 * Table structure:
	 * - Links: course_id, product_id (many-to-many)
	 * - Access control: access_duration_days (overrides course default)
	 * - Metadata: priority (for bundle resolution)
	 * - Timestamps: created_at
	 *
	 * Example scenarios:
	 *
	 * Scenario 1: Multiple tiers for one course
	 * course_id=10, product_id=100, access_duration_days=30  (Basic - 1 month)
	 * course_id=10, product_id=101, access_duration_days=365 (Pro - 1 year)
	 * course_id=10, product_id=102, access_duration_days=NULL (Enterprise - lifetime)
	 *
	 * Scenario 2: Bundle product (3 courses in 1 product)
	 * course_id=10, product_id=200, priority=1
	 * course_id=11, product_id=200, priority=2
	 * course_id=12, product_id=200, priority=3
	 *
	 * @since 3.1.0
	 * @param string $prefix Database table prefix.
	 * @param string $charset_collate Charset and collation.
	 * @return array Array of SQL statements.
	 */
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();

		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_course_products (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			course_id bigint(20) UNSIGNED NOT NULL COMMENT 'FK to saw_lms_courses.id',
			product_id bigint(20) UNSIGNED NOT NULL COMMENT 'WooCommerce product ID',
			access_duration_days int(11) UNSIGNED DEFAULT NULL COMMENT 'Overrides course.access_period_days; NULL = lifetime',
			priority int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Order in bundles (lower = first)',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			
			PRIMARY KEY (id),
			KEY course_id (course_id),
			KEY product_id (product_id),
			KEY priority (priority),
			UNIQUE KEY unique_course_product (course_id, product_id)
		) $charset_collate COMMENT='Many-to-many: courses <-> WooCommerce products';";

		return $sql;
	}
}