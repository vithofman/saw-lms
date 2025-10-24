<?php
/**
 * Plugin Name: SAW LMS
 * Plugin URI: https://sawlms.com
 * Description: Kompletní Learning Management System pro WordPress s WooCommerce integrací, skupinovými licencemi a content versioning
 * Version: 0.1.0
 * Author: SAW Team
 * Author URI: https://sawlms.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: saw-lms
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package SAW_LMS
 * @since   1.0.0
 * @version 0.1.0 (fixed activation output issue)
 */

// Zabránit přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Definice konstant.
define( 'SAW_LMS_VERSION', '0.1.0' );
define( 'SAW_LMS_PLUGIN_FILE', __FILE__ );
define( 'SAW_LMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAW_LMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SAW_LMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Flag indikující, zda probíhá aktivace pluginu
 * KRITICKÉ: Použije se k potlačení logování během aktivace
 *
 * @since 0.1.0
 */
if ( ! defined( 'SAW_LMS_ACTIVATING' ) ) {
	define( 'SAW_LMS_ACTIVATING', false );
}

/**
 * Kontrola požadavků před aktivací
 *
 * @since 1.0.0
 * @return void
 */
function saw_lms_check_requirements() {
	$errors = array();

	// Kontrola PHP verze.
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		$errors[] = sprintf(
			/* translators: %s: PHP version */
			__( 'SAW LMS vyžaduje PHP verzi 7.4 nebo vyšší. Vaše verze: %s', 'saw-lms' ),
			PHP_VERSION
		);
	}

	// Kontrola WordPress verze.
	global $wp_version;
	if ( version_compare( $wp_version, '5.8', '<' ) ) {
		$errors[] = sprintf(
			/* translators: %s: WordPress version */
			__( 'SAW LMS vyžaduje WordPress verzi 5.8 nebo vyšší. Vaše verze: %s', 'saw-lms' ),
			$wp_version
		);
	}

	// Pokud jsou chyby, zobraz je a zastav aktivaci.
	if ( ! empty( $errors ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			'<h1>' . esc_html__( 'Plugin nelze aktivovat', 'saw-lms' ) . '</h1>' .
			'<p>' . implode( '</p><p>', array_map( 'esc_html', $errors ) ) . '</p>',
			esc_html__( 'Požadavky pluginu nesplněny', 'saw-lms' ),
			array( 'back_link' => true )
		);
	}
}

/**
 * Hook pro aktivaci pluginu
 *
 * FIXED in v0.1.0: Nastavuje SAW_LMS_ACTIVATING flag pro potlačení logování.
 *
 * @since 1.0.0
 * @version 0.1.0
 * @return void
 */
function saw_lms_activate() {
	// KRITICKÉ: Nastavíme flag PŘED jakýmkoliv kódem!
	if ( ! defined( 'SAW_LMS_ACTIVATING' ) ) {
		define( 'SAW_LMS_ACTIVATING', true );
	}

	// Kontrola požadavků.
	saw_lms_check_requirements();

	// Načteme Activator třídu.
	require_once SAW_LMS_PLUGIN_DIR . 'includes/class-activator.php';

	// Spustíme aktivaci.
	SAW_LMS_Activator::activate();
}
register_activation_hook( __FILE__, 'saw_lms_activate' );

/**
 * Hook pro deaktivaci pluginu
 *
 * @since 1.0.0
 * @return void
 */
function saw_lms_deactivate() {
	require_once SAW_LMS_PLUGIN_DIR . 'includes/class-deactivator.php';
	SAW_LMS_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'saw_lms_deactivate' );

/**
 * Načtení hlavní třídy pluginu
 *
 * @since 1.0.0
 */
require_once SAW_LMS_PLUGIN_DIR . 'includes/class-saw-lms.php';

/**
 * Spuštění pluginu
 *
 * FIXED in v0.1.0: Kontroluje SAW_LMS_ACTIVATING flag před inicializací.
 *
 * @since 1.0.0
 * @version 0.1.0
 * @return void
 */
function saw_lms_run() {
	// KRITICKÉ: Nespouštět během aktivace!
	if ( defined( 'SAW_LMS_ACTIVATING' ) && SAW_LMS_ACTIVATING ) {
		return;
	}

	$plugin = SAW_LMS::init();
	$plugin->run();
}

// Start! (ale ne během aktivace)
saw_lms_run();