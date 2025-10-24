<?php
/**
 * Main Plugin Class
 *
 * @package     SAW_LMS
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS class
 *
 * Main plugin class that handles initialization and loading.
 *
 * @since 1.0.0
 */
final class SAW_LMS {

	/**
	 * Plugin instance
	 *
	 * @var SAW_LMS
	 */
	private static $instance = null;

	/**
	 * Cache manager instance
	 *
	 * @var SAW_LMS_Cache_Manager
	 */
	public $cache_manager;

	/**
	 * Get plugin instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS
	 */
	public static function get_instance() {
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
		$this->define_constants();
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Define plugin constants
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		if ( ! defined( 'SAW_LMS_VERSION' ) ) {
			define( 'SAW_LMS_VERSION', '1.0.0' );
		}

		if ( ! defined( 'SAW_LMS_DB_VERSION' ) ) {
			define( 'SAW_LMS_DB_VERSION', '1.0.0' );
		}

		if ( ! defined( 'SAW_LMS_PLUGIN_DIR' ) ) {
			define( 'SAW_LMS_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		}

		if ( ! defined( 'SAW_LMS_PLUGIN_URL' ) ) {
			define( 'SAW_LMS_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
		}

		if ( ! defined( 'SAW_LMS_PLUGIN_FILE' ) ) {
			define( 'SAW_LMS_PLUGIN_FILE', dirname( dirname( __FILE__ ) ) . '/saw-lms.php' );
		}
	}

	/**
	 * Load plugin dependencies
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Core.
		require_once SAW_LMS_PLUGIN_DIR . 'includes/class-autoloader.php';

		// Database.
		require_once SAW_LMS_PLUGIN_DIR . 'includes/database/class-schema.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/database/class-migration-tool.php';

		// Cache system.
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/interface-cache-driver.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/class-redis-driver.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/class-database-driver.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/class-transient-driver.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/cache/class-cache-manager.php';

		// Models.
		require_once SAW_LMS_PLUGIN_DIR . 'includes/models/class-course-model.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/models/class-section-model.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/models/class-lesson-model.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/models/class-quiz-model.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/models/class-model-loader.php';

		// Post types.
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-course.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-section.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-lesson.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-quiz.php';

		// Admin.
		if ( is_admin() ) {
			require_once SAW_LMS_PLUGIN_DIR . 'admin/class-admin.php';
		}

		// Initialize cache manager.
		$this->cache_manager = SAW_LMS_Cache_Manager::get_instance();
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		register_activation_hook( SAW_LMS_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( SAW_LMS_PLUGIN_FILE, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Check if database migration is needed.
		if ( SAW_LMS_Migration_Tool::needs_migration( SAW_LMS_DB_VERSION ) ) {
			SAW_LMS_Migration_Tool::migrate( SAW_LMS_DB_VERSION );
		}

		// Initialize post types.
		new SAW_LMS_Course();
		new SAW_LMS_Section();
		new SAW_LMS_Lesson();
		new SAW_LMS_Quiz();

		// Initialize admin.
		if ( is_admin() ) {
			new SAW_LMS_Admin();
		}

		do_action( 'saw_lms_initialized' );
	}

	/**
	 * Plugin activation
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		// Create database tables.
		$schema = new SAW_LMS_Schema();
		$schema->create_tables();

		// Flush rewrite rules.
		flush_rewrite_rules();

		do_action( 'saw_lms_activated' );
	}

	/**
	 * Plugin deactivation
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();

		do_action( 'saw_lms_deactivated' );
	}
}