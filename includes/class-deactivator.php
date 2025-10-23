<?php
/**
 * Deaktivátor pluginu
 * Vyčistí dočasná data, ale zachová tabulky a uživatelská data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SAW_LMS_Deactivator {

	/**
	 * Spustí se při deaktivaci pluginu
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

		// Log pro debug
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'SAW LMS: Plugin byl deaktivován. Data zůstávají zachována.' );
		}

		// POZNÁMKA: Tabulky a uživatelská data NEMAZAT!
		// To se děje pouze v uninstall.php když uživatel plugin kompletně smaže
	}
}
