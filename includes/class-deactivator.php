<?php
/**
 * Deaktivátor pluginu
 * Vyčistí dočasná data, ale zachová tabulky a uživatelská data
 *
 * UPDATED in Phase 2.1: Added flush rewrite rules to clean up CPT permalinks.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes
 * @since      1.0.0
 * @version    2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Deactivator Class
 *
 * @since 1.0.0
 */
class SAW_LMS_Deactivator {

	/**
	 * Spustí se při deaktivaci pluginu
	 *
	 * UPDATED in Phase 2.1: Added flush_rewrite_rules() to clean up CPT permalinks.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Vymazání všech transients pluginu
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_saw_lms_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_saw_lms_%'" );

		// Zrušení všech cron jobs
		$cron_hooks = array(
			'saw_lms_check_renewals',
			'saw_lms_send_reminders',
			'saw_lms_cleanup_temp',
		);

		foreach ( $cron_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}

		// Vyčištění cache
		wp_cache_flush();

		// NEW in Phase 2.1: Flush rewrite rules to clean up CPT permalinks
		flush_rewrite_rules();

		// Log pro debug
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'SAW LMS: Plugin byl deaktivován. Data zůstávají zachována.' );
		}

		// Log deactivation if logger is available
		if ( class_exists( 'SAW_LMS_Logger' ) ) {
			try {
				$logger = SAW_LMS_Logger::init();
				$logger->info( 'Plugin deactivated - rewrite rules flushed' );
			} catch ( Exception $e ) {
				// Logger may not be available during deactivation
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'SAW LMS: Could not log deactivation - ' . $e->getMessage() );
				}
			}
		}

		// POZNÁMKA: Tabulky a uživatelská data NEMAZAT!
		// To se děje pouze v uninstall.php když uživatel plugin kompletně smaže
	}
}