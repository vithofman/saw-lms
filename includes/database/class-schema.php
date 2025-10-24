<?php
/**
 * Schema Manager
 *
 * Orchestrates database table creation and management.
 * Loads all schema classes and provides unified interface for table operations.
 *
 * FIXED in v3.1.1: Removed logging during activation to prevent output errors.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/database
 * @since      3.0.0
 * @version    3.1.1
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Schema Class
 *
 * Manages database schema creation, updates, and deletion.
 *
 * @since 3.0.0
 * @version 3.1.1 - Removed logging during activation
 */
class SAW_LMS_Schema {

	/**
	 * Database version
	 *
	 * @var string
	 */
	const DB_VERSION = '3.1.0';

	/**
	 * List of all schema classes
	 *
	 * Each schema class MUST have a static method: get_sql($prefix, $charset_collate)
	 * that returns an array of SQL CREATE TABLE statements.
	 *
	 * @var array
	 */
	private static $schema_classes = array(
		'SAW_LMS_Courses_Schema',
		'SAW_LMS_Course_Products_Schema',
		'SAW_LMS_Enrollments_Schema',
		'SAW_LMS_Progress_Schema',
		'SAW_LMS_Quiz_Attempts_Schema',
		'SAW_LMS_Certificates_Schema',
		'SAW_LMS_Points_Schema',
		'SAW_LMS_Achievements_Schema',
		'SAW_LMS_Groups_Schema',
		'SAW_LMS_Group_Members_Schema',
		'SAW_LMS_Group_Courses_Schema',
		'SAW_LMS_Group_Content_Schema',
		'SAW_LMS_Content_Versions_Schema',
		'SAW_LMS_Content_Archives_Schema',
		'SAW_LMS_Scheduled_Content_Schema',
		'SAW_LMS_Drip_Content_Schema',
		'SAW_LMS_Error_Log_Schema',
		'SAW_LMS_Cache_Schema',
		'SAW_LMS_Question_Bank_Schema',
		'SAW_LMS_Email_Queue_Schema',
		'SAW_LMS_Notification_Preferences_Schema',
		'SAW_LMS_Recurring_Enrollments_Schema',
	);

	/**
	 * Create all database tables
	 *
	 * Loads all schema classes and executes their SQL definitions.
	 *
	 * FIXED in v3.1.1: Removed logging to prevent "headers already sent" error.
	 *
	 * @since 3.0.0
	 * @version 3.1.1
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		// Get WordPress DB prefix.
		$prefix = $wpdb->prefix;

		// Charset and collation.
		$charset_collate = $wpdb->get_charset_collate();

		// Require upgrade file for dbDelta.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Load all schema classes.
		self::load_schema_classes();

		// Execute SQL for each schema.
		foreach ( self::$schema_classes as $schema_class ) {
			if ( ! class_exists( $schema_class ) ) {
				// Silent fail - no logging during activation.
				continue;
			}

			// Get SQL from schema class.
			$sql_statements = call_user_func( array( $schema_class, 'get_sql' ), $prefix, $charset_collate );

			// Execute each SQL statement.
			if ( is_array( $sql_statements ) ) {
				foreach ( $sql_statements as $sql ) {
					dbDelta( $sql );
				}
			}
		}

		// Save DB version.
		update_option( 'saw_lms_db_version', self::DB_VERSION );
	}

	/**
	 * Drop all database tables
	 *
	 * Used during plugin uninstallation.
	 * CRITICAL: This permanently deletes all data!
	 *
	 * @since 3.0.0
	 * @version 3.1.0 - Added course_products table
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		// Get WordPress DB prefix.
		$prefix = $wpdb->prefix;

		// List of all table names (without prefix).
		$table_names = array(
			'saw_lms_courses',
			'saw_lms_course_products',
			'saw_lms_enrollments',
			'saw_lms_progress',
			'saw_lms_quiz_attempts',
			'saw_lms_certificates',
			'saw_lms_points',
			'saw_lms_achievements',
			'saw_lms_groups',
			'saw_lms_group_members',
			'saw_lms_group_courses',
			'saw_lms_group_content',
			'saw_lms_content_versions',
			'saw_lms_content_archives',
			'saw_lms_scheduled_content',
			'saw_lms_drip_content',
			'saw_lms_error_log',
			'saw_lms_cache',
			'saw_lms_question_bank',
			'saw_lms_email_queue',
			'saw_lms_notification_preferences',
			'saw_lms_recurring_enrollments',
		);

		// Drop each table.
		foreach ( $table_names as $table_name ) {
			$table = $prefix . $table_name;
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Delete DB version option.
		delete_option( 'saw_lms_db_version' );
	}

	/**
	 * Load all schema classes
	 *
	 * Requires all schema files from the schemas/ directory.
	 *
	 * FIXED in v3.1.1: Removed logging to prevent output during activation.
	 *
	 * @since 3.0.0
	 * @version 3.1.1
	 * @return void
	 */
	private static function load_schema_classes() {
		$schema_dir = SAW_LMS_PLUGIN_DIR . 'includes/database/schemas/';

		// List of schema files (in order of table names).
		$schema_files = array(
			'class-courses-schema.php',
			'class-course-products-schema.php',
			'class-enrollments-schema.php',
			'class-progress-schema.php',
			'class-quiz-attempts-schema.php',
			'class-certificates-schema.php',
			'class-points-schema.php',
			'class-achievements-schema.php',
			'class-groups-schema.php',
			'class-group-members-schema.php',
			'class-group-courses-schema.php',
			'class-group-content-schema.php',
			'class-content-versions-schema.php',
			'class-content-archives-schema.php',
			'class-scheduled-content-schema.php',
			'class-drip-content-schema.php',
			'class-error-log-schema.php',
			'class-cache-schema.php',
			'class-question-bank-schema.php',
			'class-email-queue-schema.php',
			'class-notification-preferences-schema.php',
			'class-recurring-enrollments-schema.php',
		);

		// Require each schema file.
		foreach ( $schema_files as $file ) {
			$filepath = $schema_dir . $file;
			if ( file_exists( $filepath ) ) {
				require_once $filepath;
			}
			// Silent fail - no logging during activation.
		}
	}

	/**
	 * Check if a table exists
	 *
	 * Helper method to verify table existence.
	 *
	 * @since 3.0.0
	 * @param string $table_name Table name (without prefix).
	 * @return bool True if table exists, false otherwise.
	 */
	public static function table_exists( $table_name ) {
		global $wpdb;

		$table  = $wpdb->prefix . $table_name;
		$result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $result === $table;
	}

	/**
	 * Get count of SAW LMS tables
	 *
	 * Returns the number of SAW LMS tables currently in the database.
	 *
	 * @since 3.0.0
	 * @return int Number of tables.
	 */
	public static function get_table_count() {
		global $wpdb;

		$prefix = $wpdb->prefix;
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name LIKE %s",
				DB_NAME,
				$prefix . 'saw_lms_%'
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return absint( $result );
	}

	/**
	 * Get database version
	 *
	 * Returns the currently installed database schema version.
	 *
	 * @since 3.0.0
	 * @return string Database version or empty string if not set.
	 */
	public static function get_db_version() {
		return get_option( 'saw_lms_db_version', '' );
	}

	/**
	 * Check if database needs update
	 *
	 * Compares current DB version with expected version.
	 *
	 * @since 3.0.0
	 * @return bool True if update needed, false otherwise.
	 */
	public static function needs_update() {
		$current_version = self::get_db_version();
		return version_compare( $current_version, self::DB_VERSION, '<' );
	}
}