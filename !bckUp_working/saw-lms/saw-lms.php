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
 */

// Zabránit přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definice konstant
define('SAW_LMS_VERSION', '0.1.0');
define('SAW_LMS_PLUGIN_FILE', __FILE__);
define('SAW_LMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SAW_LMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SAW_LMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Kontrola požadavků před aktivací
 */
function saw_lms_check_requirements() {
    $errors = array();
    
    // Kontrola PHP verze
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = sprintf(
            __('SAW LMS vyžaduje PHP verzi 7.4 nebo vyšší. Vaše verze: %s', 'saw-lms'),
            PHP_VERSION
        );
    }
    
    // Kontrola WordPress verze
    global $wp_version;
    if (version_compare($wp_version, '5.8', '<')) {
        $errors[] = sprintf(
            __('SAW LMS vyžaduje WordPress verzi 5.8 nebo vyšší. Vaše verze: %s', 'saw-lms'),
            $wp_version
        );
    }
    
    // Pokud jsou chyby, zobraz je a zastav aktivaci
    if (!empty($errors)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>' . __('Plugin nelze aktivovat', 'saw-lms') . '</h1>' .
            '<p>' . implode('</p><p>', $errors) . '</p>',
            __('Požadavky pluginu nesplněny', 'saw-lms'),
            array('back_link' => true)
        );
    }
}

/**
 * Hook pro aktivaci pluginu
 */
function saw_lms_activate() {
    // Kontrola požadavků
    saw_lms_check_requirements();
    
    // OPRAVENO: Správná cesta
    require_once SAW_LMS_PLUGIN_DIR . 'includes/class-activator.php';
    SAW_LMS_Activator::activate();
}
register_activation_hook(__FILE__, 'saw_lms_activate');

/**
 * Hook pro deaktivaci pluginu
 */
function saw_lms_deactivate() {
    // OPRAVENO: Správná cesta
    require_once SAW_LMS_PLUGIN_DIR . 'includes/class-deactivator.php';
    SAW_LMS_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'saw_lms_deactivate');

/**
 * Načtení hlavní třídy pluginu
 */
require_once SAW_LMS_PLUGIN_DIR . 'includes/class-saw-lms.php';

/**
 * Spuštění pluginu
 */
function saw_lms_run() {
    $plugin = SAW_LMS::init();
    $plugin->run();
}

// Start!
saw_lms_run();