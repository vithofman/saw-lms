<?php
/**
 * Plugin Activator
 *
 * Handles plugin activation - delegates table creation to Schema Manager.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes
 * @since      1.0.0
 * @version    3.1.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Activator Class
 *
 * @since 1.0.0
 */
class SAW_LMS_Activator {

	/**
	 * Plugin activation hook
	 *
	 * Creates all database tables and sets up initial configuration.
	 *
	 * @since 1.0.0
	 * @version 3.1.1
	 */
	public static function activate() {
		// Require Schema Manager.
		require_once SAW_LMS_PLUGIN_DIR . 'includes/database/class-schema.php';

		// Create all database tables via Schema Manager.
		SAW_LMS_Schema::create_tables();

		// Create upload directories.
		self::create_upload_directories();

		// Set default options.
		self::set_default_options();

		// --- Register CPTs before flush (Phase 2.1) ---

		// Require CPT classes.
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-course.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-section.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-lesson.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-quiz.php';

		// Initialize CPTs (registers post types).
		SAW_LMS_Course::init();
		SAW_LMS_Section::init();
		SAW_LMS_Lesson::init();
		SAW_LMS_Quiz::init();

		// --- Add custom capabilities to roles ---
		self::add_capabilities();

		// Flush rewrite rules - CRITICAL!
		flush_rewrite_rules();

		// Set activation flag for deferred logging.
		set_transient( 'saw_lms_activation_pending', true, 300 );
	}

	/**
	 * Add custom capabilities to WordPress roles
	 *
	 * @since 2.1.0
	 * @return void
	 */
	private static function add_capabilities() {
		$admin = get_role( 'administrator' );

		if ( ! $admin ) {
			return;
		}

		// Course capabilities.
		$admin->add_cap( 'edit_saw_course' );
		$admin->add_cap( 'read_saw_course' );
		$admin->add_cap( 'delete_saw_course' );
		$admin->add_cap( 'edit_saw_courses' );
		$admin->add_cap( 'edit_others_saw_courses' );
		$admin->add_cap( 'publish_saw_courses' );
		$admin->add_cap( 'read_private_saw_courses' );
		$admin->add_cap( 'delete_saw_courses' );
		$admin->add_cap( 'delete_private_saw_courses' );
		$admin->add_cap( 'delete_published_saw_courses' );
		$admin->add_cap( 'delete_others_saw_courses' );
		$admin->add_cap( 'edit_private_saw_courses' );
		$admin->add_cap( 'edit_published_saw_courses' );

		// Section capabilities.
		$admin->add_cap( 'edit_saw_section' );
		$admin->add_cap( 'read_saw_section' );
		$admin->add_cap( 'delete_saw_section' );
		$admin->add_cap( 'edit_saw_sections' );
		$admin->add_cap( 'edit_others_saw_sections' );
		$admin->add_cap( 'publish_saw_sections' );
		$admin->add_cap( 'read_private_saw_sections' );
		$admin->add_cap( 'delete_saw_sections' );
		$admin->add_cap( 'delete_private_saw_sections' );
		$admin->add_cap( 'delete_published_saw_sections' );
		$admin->add_cap( 'delete_others_saw_sections' );
		$admin->add_cap( 'edit_private_saw_sections' );
		$admin->add_cap( 'edit_published_saw_sections' );

		// Lesson capabilities.
		$admin->add_cap( 'edit_saw_lesson' );
		$admin->add_cap( 'read_saw_lesson' );
		$admin->add_cap( 'delete_saw_lesson' );
		$admin->add_cap( 'edit_saw_lessons' );
		$admin->add_cap( 'edit_others_saw_lessons' );
		$admin->add_cap( 'publish_saw_lessons' );
		$admin->add_cap( 'read_private_saw_lessons' );
		$admin->add_cap( 'delete_saw_lessons' );
		$admin->add_cap( 'delete_private_saw_lessons' );
		$admin->add_cap( 'delete_published_saw_lessons' );
		$admin->add_cap( 'delete_others_saw_lessons' );
		$admin->add_cap( 'edit_private_saw_lessons' );
		$admin->add_cap( 'edit_published_saw_lessons' );

		// Quiz capabilities.
		$admin->add_cap( 'edit_saw_quiz' );
		$admin->add_cap( 'read_saw_quiz' );
		$admin->add_cap( 'delete_saw_quiz' );
		$admin->add_cap( 'edit_saw_quizzes' );
		$admin->add_cap( 'edit_others_saw_quizzes' );
		$admin->add_cap( 'publish_saw_quizzes' );
		$admin->add_cap( 'read_private_saw_quizzes' );
		$admin->add_cap( 'delete_saw_quizzes' );
		$admin->add_cap( 'delete_private_saw_quizzes' );
		$admin->add_cap( 'delete_published_saw_quizzes' );
		$admin->add_cap( 'delete_others_saw_quizzes' );
		$admin->add_cap( 'edit_private_saw_quizzes' );
		$admin->add_cap( 'edit_published_saw_quizzes' );
	}

	/**
	 * Create upload directories
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_upload_directories() {
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'] . '/saw-lms';

		$directories = array(
			$base_dir,
			$base_dir . '/certificates',
			$base_dir . '/group-content',
			$base_dir . '/archives',
			$base_dir . '/temp',
			$base_dir . '/logs',
		);

		foreach ( $directories as $dir ) {
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );

				// Add index.php to prevent directory listing.
				$index_file = $dir . '/index.php';
				if ( ! file_exists( $index_file ) ) {
					file_put_contents( $index_file, '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				}

				// Add .htaccess for security.
				$htaccess_file = $dir . '/.htaccess';
				if ( ! file_exists( $htaccess_file ) ) {
					file_put_contents( $htaccess_file, 'deny from all' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				}
			}
		}
	}

	/**
	 * Set default plugin options
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function set_default_options() {
		$defaults = array(
			'version'                 => SAW_LMS_VERSION,
			'installed_at'            => current_time( 'mysql' ),
			'debug_mode'              => false,
			'cache_enabled'           => true,
			'cache_ttl'               => 3600,
			'points_per_lesson'       => 10,
			'points_per_quiz'         => 50,
			'certificate_enabled'     => true,
			'email_notifications'     => true,
			'course_archive_slug'     => 'courses',
			'max_upload_size'         => 10, // MB.
			'allowed_file_types'      => array( 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'mp3' ),
			'drip_content_enabled'    => true,
			'group_licenses_enabled'  => true,
			'woocommerce_integration' => false,
		);

		// Only set if not already set.
		if ( ! get_option( 'saw_lms_settings' ) ) {
			add_option( 'saw_lms_settings', $defaults );
		}

		// Set installed timestamp.
		if ( ! get_option( 'saw_lms_installed_at' ) ) {
			add_option( 'saw_lms_installed_at', current_time( 'mysql' ) );
		}
	}
}