<?php
/**
 * Database Schema
 *
 * @package     SAW_LMS
 * @subpackage  Database
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Schema class
 *
 * Manages database schema for SAW LMS plugin.
 *
 * @since 1.0.0
 */
class SAW_LMS_Schema {

	/**
	 * Create all plugin tables
	 *
	 * @since 1.0.0
	 */
	public function create_tables() {
		$this->create_enrollments_table();
		$this->create_progress_table();
		$this->create_quiz_attempts_table();
		$this->create_certificates_table();
		$this->create_gamification_table();

		update_option( 'saw_lms_db_version', SAW_LMS_DB_VERSION );
	}

	/**
	 * Create enrollments table
	 *
	 * @since 1.0.0
	 */
	private function create_enrollments_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'saw_lms_enrollments';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			enrolled_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY status (status),
			UNIQUE KEY user_course (user_id, course_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create progress table
	 *
	 * @since 1.0.0
	 */
	private function create_progress_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'saw_lms_progress';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			lesson_id bigint(20) UNSIGNED NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'in_progress',
			completed_at datetime DEFAULT NULL,
			last_accessed datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY lesson_id (lesson_id),
			KEY status (status),
			UNIQUE KEY user_course_lesson (user_id, course_id, lesson_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create quiz attempts table
	 *
	 * @since 1.0.0
	 */
	private function create_quiz_attempts_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'saw_lms_quiz_attempts';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			quiz_id bigint(20) UNSIGNED NOT NULL,
			score decimal(5,2) NOT NULL DEFAULT 0.00,
			answers longtext DEFAULT NULL,
			started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY quiz_id (quiz_id),
			KEY score (score)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create certificates table
	 *
	 * @since 1.0.0
	 */
	private function create_certificates_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'saw_lms_certificates';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			certificate_code varchar(100) NOT NULL,
			issued_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			UNIQUE KEY certificate_code (certificate_code)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create gamification table
	 *
	 * @since 1.0.0
	 */
	private function create_gamification_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'saw_lms_gamification';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			event_type varchar(50) NOT NULL,
			points int(11) NOT NULL DEFAULT 0,
			reference_id bigint(20) UNSIGNED DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop all plugin tables
	 *
	 * @since 1.0.0
	 */
	public function drop_tables() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'saw_lms_enrollments',
			$wpdb->prefix . 'saw_lms_progress',
			$wpdb->prefix . 'saw_lms_quiz_attempts',
			$wpdb->prefix . 'saw_lms_certificates',
			$wpdb->prefix . 'saw_lms_gamification',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		delete_option( 'saw_lms_db_version' );
	}

	/**
	 * Check if tables exist
	 *
	 * @since  1.0.0
	 * @return bool True if all tables exist.
	 */
	public function tables_exist() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'saw_lms_enrollments',
			$wpdb->prefix . 'saw_lms_progress',
			$wpdb->prefix . 'saw_lms_quiz_attempts',
			$wpdb->prefix . 'saw_lms_certificates',
			$wpdb->prefix . 'saw_lms_gamification',
		);

		foreach ( $tables as $table ) {
			$result = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );

			if ( $result !== $table ) {
				return false;
			}
		}

		return true;
	}
}