<?php
/**
 * SAW LMS - Learning Management System
 *
 * @package           SAW_LMS
 * @author            SAW Development Team
 * @copyright         2025 SAW Development Team
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       SAW LMS
 * Plugin URI:        https://sawuh.cz/lms
 * Description:       Komplexní Learning Management System pro WordPress s pokročilou správou kurzů, studentů a certifikátů.
 * Version:           2.1.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            SAW Development Team
 * Author URI:        https://sawuh.cz
 * Text Domain:       saw-lms
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * EMERGENCY FIX v2.1.2: Fixed timing issue with wp_mail() by delaying initialization.
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Currently plugin version
 * Start at version 2.1.2 (EMERGENCY FIX)
 */
define( 'SAW_LMS_VERSION', '2.1.2' );

/**
 * Database version
 */
define( 'SAW_LMS_DB_VERSION', '1.0.0' );

/**
 * Plugin directory path
 */
define( 'SAW_LMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL
 */
define( 'SAW_LMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin file path
 */
define( 'SAW_LMS_PLUGIN_FILE', __FILE__ );

/**
 * Minimum required versions
 */
define( 'SAW_LMS_MIN_WP_VERSION', '5.8' );
define( 'SAW_LMS_MIN_PHP_VERSION', '7.4' );

/**
 * Check requirements before loading plugin
 *
 * @since 1.0.0
 */
function saw_lms_check_requirements() {
	global $wp_version;

	$errors = array();

	// Check WordPress version
	if ( version_compare( $wp_version, SAW_LMS_MIN_WP_VERSION, '<' ) ) {
		$errors[] = sprintf(
			/* translators: %s: required WordPress version */
			__( 'SAW LMS requires WordPress %s or higher.', 'saw-lms' ),
			SAW_LMS_MIN_WP_VERSION
		);
	}

	// Check PHP version
	if ( version_compare( PHP_VERSION, SAW_LMS_MIN_PHP_VERSION, '<' ) ) {
		$errors[] = sprintf(
			/* translators: %s: required PHP version */
			__( 'SAW LMS requires PHP %s or higher.', 'saw-lms' ),
			SAW_LMS_MIN_PHP_VERSION
		);
	}

	// If there are errors, show them and deactivate
	if ( ! empty( $errors ) ) {
		add_action(
			'admin_notices',
			function () use ( $errors ) {
				?>
				<div class="notice notice-error">
					<p><strong><?php esc_html_e( 'SAW LMS Plugin Error:', 'saw-lms' ); ?></strong></p>
					<ul>
						<?php foreach ( $errors as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php
			}
		);

		// Deactivate plugin
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( __FILE__ ) );

		return false;
	}

	return true;
}

/**
 * Load the main plugin class
 *
 * @since 1.0.0
 */
function saw_lms_load_plugin() {
	// Load the main class
	require_once SAW_LMS_PLUGIN_DIR . 'includes/class-saw-lms.php';

	// Initialize the plugin
	SAW_LMS::init();
}

/**
 * Plugin activation hook
 *
 * @since 1.0.0
 */
function saw_lms_activate() {
	// Check requirements
	if ( ! saw_lms_check_requirements() ) {
		return;
	}

	// Load activation class
	require_once SAW_LMS_PLUGIN_DIR . 'includes/class-activator.php';

	// Run activation
	SAW_LMS_Activator::activate();
}

/**
 * Plugin deactivation hook
 *
 * @since 1.0.0
 */
function saw_lms_deactivate() {
	// Load deactivation class
	require_once SAW_LMS_PLUGIN_DIR . 'includes/class-deactivator.php';

	// Run deactivation
	SAW_LMS_Deactivator::deactivate();
}

/**
 * Register activation and deactivation hooks
 */
register_activation_hook( __FILE__, 'saw_lms_activate' );
register_deactivation_hook( __FILE__, 'saw_lms_deactivate' );

/**
 * ✅ CRITICAL FIX v2.1.2: Load plugin ONLY after WordPress is fully initialized
 * 
 * This prevents "Call to undefined function wp_mail()" errors by ensuring
 * all WordPress core functions (including pluggable functions) are available
 * before we initialize our error handling system.
 *
 * Priority 10 is the default, which runs after WordPress core has loaded
 * all pluggable functions (wp_mail, get_bloginfo, etc.)
 */
add_action( 'plugins_loaded', 'saw_lms_load_plugin', 10 );

/**
 * Check requirements on every page load (admin only)
 */
if ( is_admin() ) {
	saw_lms_check_requirements();
}