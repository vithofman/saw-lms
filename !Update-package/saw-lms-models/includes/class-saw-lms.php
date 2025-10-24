<?php
/**
 * The core plugin class
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * UPDATED in Phase 1.9: Added Admin Assets loader for new design system.
 * UPDATED in Phase 2.1: Added Custom Post Types initialization.
 * UPDATED in v3.0.0: Added Model Loader for structured database tables.
 * FIXED: Added file_exists() checks before loading CPT files to prevent fatal errors.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes
 * @since      1.0.0
 * @version    3.0.0
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
	 * UPDATED in Phase 2.1: Added init_post_types() call.
	 * UPDATED in v3.0.0: Added load_models() call BEFORE init_post_types().
	 * FIXED: Moved init_post_types() AFTER error handling is set up.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->plugin_name = 'saw-lms';
		$this->version     = SAW_LMS_VERSION;

		$this->load_dependencies();
		$this->setup_error_handling();  // ✅ FIRST: Setup error handling.
		$this->init_cache_system();     // ✅ SECOND: Init cache.
		$this->load_models();           // ✅ THIRD: Load models (NEW in v3.0.0).
		$this->init_post_types();       // ✅ FOURTH: Load CPTs (with models available).
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
			require_once SAW_LMS_PLUGIN_DIR . 'admin/class-admin-assets.php';     // NEW in Phase 1.9.
			require_once SAW_LMS_PLUGIN_DIR . 'admin/class-admin-menu.php';
			require_once SAW_LMS_PLUGIN_DIR . 'admin/class-cache-test-page.php';
		}

		// Initialize the loader.
		$this->loader = new SAW_LMS_Loader();
	}

	/**
	 * Load models
	 *
	 * NEW in v3.0.0: Loads all model classes for structured database access.
	 * Models must be loaded BEFORE post types so that save_meta_boxes() can use them.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	private function load_models() {
		$model_loader = SAW_LMS_PLUGIN_DIR . 'includes/models/class-model-loader.php';

		if ( file_exists( $model_loader ) ) {
			require_once $model_loader;
			SAW_LMS_Model_Loader::load_models();

			if ( $this->logger ) {
				$this->logger->info(
					'Models loaded',
					array( 'count' => count( SAW_LMS_Model_Loader::get_loaded_models() ) )
				);
			}
		} else {
			if ( $this->logger ) {
				$this->logger->warning(
					'Model loader not found - post types will use postmeta fallback',
					array( 'path' => $model_loader )
				);
			}
		}
	}

	/**
	 * Initialize Custom Post Types
	 *
	 * Loads and initializes all custom post type classes.
	 * FIXED: Added file_exists() checks to prevent fatal errors.
	 *
	 * @since 2.1.0
	 * @since 2.1.1 Added safety checks for file existence.
	 * @return void
	 */
	private function init_post_types() {
		// Define CPT files.
		$cpt_files = array(
			'course'  => SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-course.php',
			'section' => SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-section.php',
			'lesson'  => SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-lesson.php',
			'quiz'    => SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-quiz.php',
		);

		// Check if ALL CPT files exist before loading any.
		$all_files_exist = true;
		$missing_files   = array();

		foreach ( $cpt_files as $name => $file ) {
			if ( ! file_exists( $file ) ) {
				$all_files_exist = false;
				$missing_files[] = $name;
			}
		}

		// If any files are missing, log error and return.
		if ( ! $all_files_exist ) {
			if ( $this->logger ) {
				$this->logger->warning(
					'CPT files missing - skipping post type registration',
					array(
						'missing_files' => $missing_files,
						'note'          => 'This is normal if you have not yet uploaded Phase 2.1 files',
					)
				);
			}

			// Add admin notice for missing CPT files.
			add_action(
				'admin_notices',
				function () use ( $missing_files ) {
					?>
					<div class="notice notice-warning is-dismissible">
						<p>
							<strong>SAW LMS:</strong>
							<?php
							printf(
								/* translators: %s: list of missing file names */
								esc_html__( 'Custom Post Type files are missing: %s. Upload all files from Phase 2.1 to enable courses, sections, lessons, and quizzes.', 'saw-lms' ),
								esc_html( implode( ', ', $missing_files ) )
							);
							?>
						</p>
					</div>
					<?php
				}
			);

			return; // Exit early - don't try to load CPTs.
		}

		// All files exist - safe to load.
		require_once $cpt_files['course'];
		require_once $cpt_files['section'];
		require_once $cpt_files['lesson'];
		require_once $cpt_files['quiz'];

		// Initialize singletons.
		SAW_LMS_Course::init();
		SAW_LMS_Section::init();
		SAW_LMS_Lesson::init();
		SAW_LMS_Quiz::init();

		/**
		 * Fires after all post types are initialized.
		 *
		 * @since 2.1.0
		 */
		do_action( 'saw_lms_post_types_initialized' );

		// Log successful initialization.
		if ( $this->logger ) {
			$this->logger->info( 'Custom Post Types initialized successfully' );
		}
	}

	/**
	 * Setup error handling
	 *
	 * @since 1.0.0
	 */
	private function setup_error_handling() {
		// Initialize Logger first.
		$this->logger = SAW_LMS_Logger::init();

		// Initialize Error Handler.
		$this->error_handler = SAW_LMS_Error_Handler::init();

		// Set logger in error handler.
		$this->error_handler->set_logger( $this->logger );

		// Setup error handlers.
		$this->error_handler->setup_handlers();

		// Log plugin initialization.
		$this->logger->info(
			'SAW LMS Plugin initialized',
			array(
				'version'     => $this->version,
				'php_version' => PHP_VERSION,
				'wp_version'  => get_bloginfo( 'version' ),
			)
		);
	}

	/**
	 * Initialize cache system
	 *
	 * @since 1.0.0
	 */
	private function init_cache_system() {
		// Initialize cache manager (auto-detects best driver).
		$this->cache_manager = SAW_LMS_Cache_Manager::init();

		// Log which driver was selected.
		$this->logger->info(
			'Cache system ready',
			array(
				'driver'    => $this->cache_manager->get_driver_name(),
				'available' => $this->cache_manager->is_available(),
			)
		);
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

		// Admin Assets (NEW in Phase 1.9).
		$admin_assets = new SAW_LMS_Admin_Assets( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin_assets, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin_assets, 'enqueue_scripts' );

		// Admin Menu.
		$admin_menu = new SAW_LMS_Admin_Menu( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $admin_menu, 'add_menu' );

		// Cache Test Page.
		$cache_test = new SAW_LMS_Cache_Test_Page( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $cache_test, 'add_menu_page' );
	}

	/**
	 * Register all hooks related to public-facing functionality
	 *
	 * @since 1.0.0
	 */
	private function define_public_hooks() {
		// TODO: Add public-facing hooks here when frontend is implemented.
	}

	/**
	 * Run the loader to execute all of the hooks
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
