<?php
/**
 * Admin Assets Loader
 *
 * Správné načítání CSS a JavaScript souborů pro admin rozhraní.
 * Všechny assety jsou načteny pouze v admin oblasti.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/admin
 * @since      1.0.0
 * @version    1.9.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Admin_Assets Class
 *
 * Spravuje načítání všech admin assets (CSS/JS).
 *
 * @since 1.0.0
 */
class SAW_LMS_Admin_Assets {

	/**
	 * Plugin name
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private $version;

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @param string $plugin_name Plugin name
	 * @param string $version     Plugin version
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register all admin styles
	 *
	 * Enqueue order je důležitý:
	 * 1. Variables (CSS proměnné)
	 * 2. Utilities (utility třídy)
	 * 3. Components (UI komponenty)
	 * 4. Layouts (page layouts)
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		// Check if we're in admin area and on SAW LMS pages
		if ( ! $this->should_load_assets() ) {
			return;
		}

		$assets_url = SAW_LMS_PLUGIN_URL . 'assets/css/admin/';

		// 1. Variables - MUST be first
		wp_enqueue_style(
			$this->plugin_name . '-admin-variables',
			$assets_url . 'variables.css',
			array(),
			$this->version,
			'all'
		);

		// 2. Utilities
		wp_enqueue_style(
			$this->plugin_name . '-admin-utilities',
			$assets_url . 'utilities.css',
			array( $this->plugin_name . '-admin-variables' ),
			$this->version,
			'all'
		);

		// 3. Components
		wp_enqueue_style(
			$this->plugin_name . '-admin-components',
			$assets_url . 'components.css',
			array(
				$this->plugin_name . '-admin-variables',
				$this->plugin_name . '-admin-utilities',
			),
			$this->version,
			'all'
		);

		// 4. Layouts
		wp_enqueue_style(
			$this->plugin_name . '-admin-layouts',
			$assets_url . 'layouts.css',
			array(
				$this->plugin_name . '-admin-variables',
				$this->plugin_name . '-admin-utilities',
				$this->plugin_name . '-admin-components',
			),
			$this->version,
			'all'
		);
	}

	/**
	 * Register all admin scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Check if we're in admin area and on SAW LMS pages
		if ( ! $this->should_load_assets() ) {
			return;
		}

		$assets_url = SAW_LMS_PLUGIN_URL . 'assets/js/admin/';

		// Admin utilities - vanilla JavaScript
		wp_enqueue_script(
			$this->plugin_name . '-admin-utilities',
			$assets_url . 'utilities.js',
			array(), // NO jQuery dependency - pure vanilla JS
			$this->version,
			true // Load in footer
		);

		// Localize script with AJAX data
		wp_localize_script(
			$this->plugin_name . '-admin-utilities',
			'sawLmsAdmin',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'saw_lms_admin_nonce' ),
				'pluginUrl'   => SAW_LMS_PLUGIN_URL,
				'version'     => $this->version,
				'i18n'        => array(
					'error'           => __( 'Error', 'saw-lms' ),
					'success'         => __( 'Success', 'saw-lms' ),
					'loading'         => __( 'Loading...', 'saw-lms' ),
					'confirmDelete'   => __( 'Are you sure you want to delete this?', 'saw-lms' ),
					'saved'           => __( 'Changes saved successfully', 'saw-lms' ),
					'saveFailed'      => __( 'Failed to save changes', 'saw-lms' ),
					'tryAgain'        => __( 'Please try again', 'saw-lms' ),
					'networkError'    => __( 'Network error. Please check your connection.', 'saw-lms' ),
				),
			)
		);
	}

	/**
	 * Determine if assets should be loaded
	 *
	 * Načteme assety pouze na SAW LMS admin stránkách
	 * pro optimalizaci výkonu.
	 *
	 * @since  1.0.0
	 * @return bool True if assets should be loaded
	 */
	private function should_load_assets() {
		// Check if we're in admin
		if ( ! is_admin() ) {
			return false;
		}

		// Get current screen
		$screen = get_current_screen();
		
		if ( ! $screen ) {
			return false;
		}

		// Load on all SAW LMS pages
		// Check if page parameter starts with 'saw-lms'
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		
		if ( strpos( $page, 'saw-lms' ) === 0 ) {
			return true;
		}

		// Load on SAW LMS post type screens (když budou vytvořeny v Fázi 2)
		if ( isset( $screen->post_type ) ) {
			$saw_post_types = array(
				'saw_course',
				'saw_section',
				'saw_lesson',
				'saw_quiz',
			);
			
			if ( in_array( $screen->post_type, $saw_post_types, true ) ) {
				return true;
			}
		}

		// Don't load on other pages
		return false;
	}

	/**
	 * Add admin body classes
	 *
	 * Přidáme CSS třídy na body tag v admin pro lepší styling.
	 *
	 * @since  1.0.0
	 * @param  string $classes Current body classes
	 * @return string Modified body classes
	 */
	public function add_admin_body_class( $classes ) {
		if ( ! $this->should_load_assets() ) {
			return $classes;
		}

		$classes .= ' saw-lms-admin ';

		return $classes;
	}

	/**
	 * Print custom CSS variables in admin head
	 *
	 * Umožňuje dynamické nastavení proměnných z PHP (pro budoucí Settings page).
	 *
	 * @since 1.0.0
	 */
	public function print_custom_css_vars() {
		if ( ! $this->should_load_assets() ) {
			return;
		}

		// Možnost pro budoucí customizaci barev přes Settings
		$custom_primary_color = get_option( 'saw_lms_primary_color', '' );
		
		if ( ! empty( $custom_primary_color ) ) {
			echo '<style id="saw-lms-custom-vars">';
			echo ':root { --saw-primary: ' . esc_attr( $custom_primary_color ) . '; }';
			echo '</style>';
		}
	}

	/**
	 * Add custom admin menu icon CSS
	 *
	 * WordPress admin menu ikonka styling.
	 *
	 * @since 1.0.0
	 */
	public function add_menu_icon_css() {
		echo '<style>
			#adminmenu #toplevel_page_saw-lms .wp-menu-image img {
				width: 20px;
				height: 20px;
				opacity: 0.6;
			}
			#adminmenu #toplevel_page_saw-lms:hover .wp-menu-image img,
			#adminmenu #toplevel_page_saw-lms.wp-has-current-submenu .wp-menu-image img {
				opacity: 1;
			}
		</style>';
	}

	/**
	 * Remove admin notices on SAW LMS pages
	 *
	 * Skryje cizí admin notices na našich stránkách pro čistší UI.
	 * (Zachováme pouze naše vlastní notices)
	 *
	 * @since 1.0.0
	 */
	public function hide_unrelated_notices() {
		if ( ! $this->should_load_assets() ) {
			return;
		}

		// Remove all notices except our own
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Initialize hooks
	 *
	 * Registruje všechny WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function init_hooks() {
		// Enqueue styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Add custom CSS vars
		add_action( 'admin_head', array( $this, 'print_custom_css_vars' ) );

		// Add menu icon CSS
		add_action( 'admin_head', array( $this, 'add_menu_icon_css' ) );

		// Add body class
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );

		// Hide unrelated notices (optional - uncomment if needed)
		// add_action( 'admin_head', array( $this, 'hide_unrelated_notices' ), 1 );
	}
}