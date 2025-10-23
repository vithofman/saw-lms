<?php
/**
 * The core plugin class
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * UPDATED in Phase 1.9: Added Admin Assets loader for new design system.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes
 * @since      1.0.0
 * @version    1.9.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS Class
 *
 * Main plugin class (Singleton)
 *
 * @since 1.0.0
 */
class SAW_LMS {

	/**
	 * The loader that's responsible for maintaining and registering all hooks
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS_Loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $version;

	/**
	 * The single instance of the class
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS
	 */
	private static $instance = null;

	/**
	 * Error Handler instance
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS_Error_Handler
	 */
	protected $error_handler;

	/**
	 * Logger instance
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS_Logger
	 */
	protected $logger;

	/**
	 * Cache Manager instance
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS_Cache_Manager
	 */
	protected $cache_manager;

	/**
	 * Get the singleton instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->plugin_name = 'saw-lms';
		$this->version     = SAW_LMS_VERSION;

		$this->load_dependencies();
		$this->setup_error_handling();
		$this->init_cache_system();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies
	 *
	 * UPDATED in Phase 1.9: Added Admin Assets loader.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		/**
		 * Core classes
		 */
		require_once SAW_LMS_PLUGIN_DIR . 'includes/class-loader.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/core/class-error-handler.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/utilities/class-logger.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/utilities/functions.php';

		/**
		 * Cache system
		 */
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/interface-cache-driver.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/drivers/class-redis-driver.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/drivers/class-db-driver.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/drivers/class-transient-driver.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/class-cache-manager.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/class-cache-helper.php';

		/**
		 * Admin classes
		 */
		if ( is_admin() ) {
			require_once SAW_LMS_PLUGIN_DIR . 'admin/class-admin-assets.php';     // NEW in Phase 1.9
			require_once SAW_LMS_PLUGIN_DIR . 'admin/class-admin-menu.php';
			require_once SAW_LMS_PLUGIN_DIR . 'admin/class-cache-test-page.php';
		}

		// Initialize the loader
		$this->loader = new SAW_LMS_Loader();
	}

	/**
	 * Setup error handling
	 *
	 * @since 1.0.0
	 */
	private function setup_error_handling() {
		// Initialize Logger first
		$this->logger = SAW_LMS_Logger::init();

		// Initialize Error Handler
		$this->error_handler = SAW_LMS_Error_Handler::init();

		// Set logger in error handler
		$this->error_handler->set_logger( $this->logger );

		// Setup error handlers
		$this->error_handler->setup_handlers();

		// Log plugin initialization
		$this->logger->info( 'SAW LMS Plugin initialized', array(
			'version'     => $this->version,
			'php_version' => PHP_VERSION,
			'wp_version'  => get_bloginfo( 'version' ),
		) );
	}

	/**
	 * Initialize cache system
	 *
	 * @since 1.0.0
	 */
	private function init_cache_system() {
		// Initialize cache manager (auto-detects best driver)
		$this->cache_manager = SAW_LMS_Cache_Manager::init();

		// Log which driver was selected
		$this->logger->info( 'Cache system ready', array(
			'driver'    => $this->cache_manager->get_driver_name(),
			'available' => $this->cache_manager->is_available(),
		) );
	}

	/**
	 * Define the locale for this plugin for internationalization
	 *
	 * @since 1.0.0
	 */
	private function set_locale() {
		$plugin_i18n = new SAW_LMS_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all hooks related to admin area
	 *
	 * UPDATED in Phase 1.9: Added Admin Assets hooks.
	 *
	 * @since 1.0.0
	 */
	private function define_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		// Admin Assets - NEW in Phase 1.9
		$admin_assets = new SAW_LMS_Admin_Assets( $this->plugin_name, $this->version );
		$admin_assets->init_hooks(); // This registers all enqueue and style hooks

		// Admin menu
		$admin_menu = new SAW_LMS_Admin_Menu( $this->plugin_name, $this->version );
		$this->loader->add_action( 'admin_menu', $admin_menu, 'add_menu' );

		// Cache test page
		$cache_test = new SAW_LMS_Cache_Test_Page( $this->plugin_name, $this->version );
		$this->loader->add_action( 'admin_menu', $cache_test, 'add_test_page' );

		// Add settings link to plugins page
		$plugin_basename = plugin_basename( SAW_LMS_PLUGIN_FILE );
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $this, 'add_action_links' );
	}

	/**
	 * Register all hooks related to public-facing functionality
	 *
	 * @since 1.0.0
	 */
	private function define_public_hooks() {
		// Public hooks will be added here in later phases
	}

	/**
	 * Add action links to plugin page
	 *
	 * @since  1.0.0
	 * @param  array $links Existing links
	 * @return array Modified links
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=saw-lms' ),
			__( 'Dashboard', 'saw-lms' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Run the loader to execute all hooks
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Get the loader
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Get the plugin name
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get the plugin version
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get error handler instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Error_Handler
	 */
	public function get_error_handler() {
		return $this->error_handler;
	}

	/**
	 * Get logger instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Logger
	 */
	public function get_logger() {
		return $this->logger;
	}

	/**
	 * Get cache manager instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Cache_Manager
	 */
	public function get_cache_manager() {
		return $this->cache_manager;
	}
}

/**
 * Simple i18n class for translations
 *
 * @since 1.0.0
 */
class SAW_LMS_i18n {

	/**
	 * Load the plugin text domain for translation
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'saw-lms',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}