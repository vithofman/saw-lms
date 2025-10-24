<?php
/**
 * Migration Tool
 *
 * @package     SAW_LMS
 * @subpackage  Database
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Migration_Tool class
 *
 * Handles database migrations for SAW LMS plugin.
 *
 * @since 1.0.0
 */
class SAW_LMS_Migration_Tool {

	/**
	 * Run migration
	 *
	 * @since  1.0.0
	 * @param  string $version Target version to migrate to.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function migrate( $version ) {
		$current_version = get_option( 'saw_lms_db_version', '0.0.0' );

		if ( version_compare( $current_version, $version, '>=' ) ) {
			return new WP_Error( 'already_migrated', sprintf( __( 'Database is already at version %s or higher.', 'saw-lms' ), $version ) );
		}

		$migration_method = 'migrate_to_' . str_replace( '.', '_', $version );

		if ( ! method_exists( __CLASS__, $migration_method ) ) {
			return new WP_Error( 'migration_not_found', sprintf( __( 'Migration method %s not found.', 'saw-lms' ), $migration_method ) );
		}

		$result = call_user_func( array( __CLASS__, $migration_method ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_option( 'saw_lms_db_version', $version );

		return true;
	}

	/**
	 * Migrate to version 1.0.0
	 *
	 * @since  1.0.0
	 * @return bool|WP_Error
	 */
	private static function migrate_to_1_0_0() {
		require_once SAW_LMS_PLUGIN_DIR . 'includes/database/class-schema.php';

		$schema = new SAW_LMS_Schema();
		$schema->create_tables();

		return true;
	}

	/**
	 * Rollback migration
	 *
	 * @since  1.0.0
	 * @param  string $version Version to rollback to.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function rollback( $version ) {
		$current_version = get_option( 'saw_lms_db_version', '0.0.0' );

		if ( version_compare( $current_version, $version, '<=' ) ) {
			return new WP_Error( 'invalid_rollback', sprintf( __( 'Cannot rollback to version %s from %s.', 'saw-lms' ), $version, $current_version ) );
		}

		$rollback_method = 'rollback_from_' . str_replace( '.', '_', $current_version );

		if ( ! method_exists( __CLASS__, $rollback_method ) ) {
			return new WP_Error( 'rollback_not_found', sprintf( __( 'Rollback method %s not found.', 'saw-lms' ), $rollback_method ) );
		}

		$result = call_user_func( array( __CLASS__, $rollback_method ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_option( 'saw_lms_db_version', $version );

		return true;
	}

	/**
	 * Rollback from version 1.0.0
	 *
	 * @since  1.0.0
	 * @return bool|WP_Error
	 */
	private static function rollback_from_1_0_0() {
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

		return true;
	}

	/**
	 * Get current database version
	 *
	 * @since  1.0.0
	 * @return string Current database version.
	 */
	public static function get_current_version() {
		return get_option( 'saw_lms_db_version', '0.0.0' );
	}

	/**
	 * Check if migration is needed
	 *
	 * @since  1.0.0
	 * @param  string $target_version Target version.
	 * @return bool True if migration is needed.
	 */
	public static function needs_migration( $target_version ) {
		$current_version = self::get_current_version();

		return version_compare( $current_version, $target_version, '<' );
	}
}