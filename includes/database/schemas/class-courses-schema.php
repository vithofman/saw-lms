<?php
/**
 * Courses Table Schema
 *
 * Defines the SQL structure for the wp_saw_lms_courses table.
 *
 * NEW in v3.0.0: Full Ownership Architecture
 * This table stores complete course data independently of WordPress custom post types.
 * Allows complete ownership of course data with enhanced performance and flexibility.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/database/schemas
 * @since      3.0.0
 * @version    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Courses_Schema Class
 *
 * Provides SQL definition for the courses table.
 *
 * @since 3.0.0
 */
class SAW_LMS_Courses_Schema {

	/**
	 * Get SQL for creating the courses table
	 *
	 * NEW in v3.0.0: Full Ownership Architecture
	 *
	 * Table structure:
	 * - Basic info: id, post_id, title, slug, content, excerpt, status, author_id, featured_image_id
	 * - Settings: duration_minutes, passing_score_percent
	 * - Access control: access_mode, enrollment_type, student_limit, start_date, end_date, access_period_days
	 * - Pricing: price, currency
	 * - Progression: progression_mode, require_all_lessons, require_all_quizzes
	 * - Certificate: certificate_id, certificate_require_passing
	 * - Recurring (BOZP!): is_recurring, recurrence_type, recurrence_interval, grace_period_days
	 * - Marketing: promo_video_url
	 * - JSON fields: prerequisites, email_notifications, custom_fields
	 * - Timestamps: created_at, updated_at, deleted_at (soft delete)
	 *
	 * @since 3.0.0
	 * @param string $prefix Database table prefix.
	 * @param string $charset_collate Charset and collation.
	 * @return array Array of SQL statements.
	 */
	public static function get_sql( $prefix, $charset_collate ) {
		$sql = array();

		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_courses (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Link to WP post (for backward compatibility)',
			product_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Primary WooCommerce product ID',
			title varchar(255) NOT NULL,
			slug varchar(200) NOT NULL,
			content longtext DEFAULT NULL,
			excerpt text DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, published, archived',
			author_id bigint(20) UNSIGNED NOT NULL,
			featured_image_id bigint(20) UNSIGNED DEFAULT NULL,
			
			duration_minutes int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total course duration',
			passing_score_percent int(11) UNSIGNED NOT NULL DEFAULT 70 COMMENT 'Minimum % to pass',
			
			access_mode varchar(20) NOT NULL DEFAULT 'open' COMMENT 'open, free, purchase, closed',
			enrollment_type varchar(20) NOT NULL DEFAULT 'automatic' COMMENT 'automatic, manual, approval',
			student_limit int(11) UNSIGNED DEFAULT NULL COMMENT 'NULL = unlimited',
			start_date datetime DEFAULT NULL COMMENT 'Course availability start',
			end_date datetime DEFAULT NULL COMMENT 'Course availability end',
			access_period_days int(11) UNSIGNED DEFAULT NULL COMMENT 'Days after enrollment',
			
			price decimal(10,2) DEFAULT NULL COMMENT 'Course price',
			currency varchar(3) DEFAULT 'CZK' COMMENT 'Currency code',
			
			progression_mode varchar(20) NOT NULL DEFAULT 'linear' COMMENT 'linear, freeform',
			require_all_lessons tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Must complete all lessons',
			require_all_quizzes tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Must pass all quizzes',
			
			certificate_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Certificate template ID',
			certificate_require_passing tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Need passing score',
			
			is_recurring tinyint(1) NOT NULL DEFAULT 0 COMMENT 'BOZP recurring course',
			recurrence_type varchar(20) DEFAULT NULL COMMENT 'monthly, yearly, custom',
			recurrence_interval int(11) UNSIGNED DEFAULT NULL COMMENT 'Interval in months',
			grace_period_days int(11) UNSIGNED DEFAULT 0 COMMENT 'Days before expiration',
			
			promo_video_url varchar(500) DEFAULT NULL COMMENT 'YouTube/Vimeo URL',
			
			prerequisites longtext DEFAULT NULL COMMENT 'JSON array of course IDs',
			email_notifications longtext DEFAULT NULL COMMENT 'JSON notification settings',
			custom_fields longtext DEFAULT NULL COMMENT 'JSON additional data',
			
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			deleted_at datetime DEFAULT NULL COMMENT 'Soft delete timestamp',
			
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY product_id (product_id),
			KEY slug (slug),
			KEY status (status),
			KEY author_id (author_id),
			KEY access_mode (access_mode),
			KEY is_recurring (is_recurring),
			KEY created_at (created_at),
			KEY deleted_at (deleted_at),
			UNIQUE KEY unique_slug (slug)
		) $charset_collate COMMENT='NEW v3.0: Full course ownership';";

		return $sql;
	}
}