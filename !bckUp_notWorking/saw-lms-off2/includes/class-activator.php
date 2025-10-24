<?php
/**
 * Plugin Activator
 *
 * Handles plugin activation - creates database tables and sets up initial configuration
 *
 * UPDATED in Phase 2.1: Added Custom Post Types registration, capabilities, and flush rewrite rules.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes
 * @since      1.0.0
 * @version    2.1.0
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
	 * Database version
	 */
	const DB_VERSION = '2.1.0';

	/**
	 * Plugin activation hook
	 *
	 * Creates all database tables and sets up initial configuration
	 *
	 * UPDATED in Phase 2.1: Added CPT registration, capabilities, and flush rewrite rules.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		global $wpdb;

		// Get WordPress DB prefix
		$prefix = $wpdb->prefix;

		// Charset and collation
		$charset_collate = $wpdb->get_charset_collate();

		// Require upgrade file for dbDelta
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create all tables
		self::create_core_tables( $prefix, $charset_collate );
		self::create_group_tables( $prefix, $charset_collate );
		self::create_versioning_tables( $prefix, $charset_collate );
		self::create_scheduling_tables( $prefix, $charset_collate );
		self::create_system_tables( $prefix, $charset_collate );

		// Create upload directories
		self::create_upload_directories();

		// Set default options
		self::set_default_options();

		// --- NEW in Phase 2.1: Register CPTs before flush ---

		// Require CPT classes
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-course.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-section.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-lesson.php';
		require_once SAW_LMS_PLUGIN_DIR . 'includes/post-types/class-quiz.php';

		// Initialize CPTs (registers post types)
		SAW_LMS_Course::init();
		SAW_LMS_Section::init();
		SAW_LMS_Lesson::init();
		SAW_LMS_Quiz::init();

		// --- NEW in Phase 2.1: Add custom capabilities to roles ---
		self::add_capabilities();

		// Flush rewrite rules - CRITICAL!
		flush_rewrite_rules();

		// Log activation if logger is available
		if ( class_exists( 'SAW_LMS_Logger' ) ) {
			$logger = SAW_LMS_Logger::init();
			$logger->info(
				'Plugin activated - post types registered and capabilities added',
				array(
					'db_version' => self::DB_VERSION,
					'wp_version' => get_bloginfo( 'version' ),
				)
			);
		}

		// Save DB version
		update_option( 'saw_lms_db_version', self::DB_VERSION );
	}

	/**
	 * Add custom capabilities to WordPress roles
	 *
	 * Adds capabilities for managing SAW LMS custom post types.
	 * Based on SECURITY.md guidelines.
	 *
	 * @since 2.1.0
	 * @return void
	 */
	private static function add_capabilities() {
		// Get administrator role
		$admin = get_role( 'administrator' );

		if ( $admin ) {
			// Core LMS management
			$admin->add_cap( 'manage_saw_lms' );

			// Course capabilities
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

			// Section capabilities
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

			// Lesson capabilities
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

			// Quiz capabilities
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

		// TODO: Add capabilities for future roles (Instructor, Student)
		// This will be implemented in Phase 2.2 when user roles are created
	}

	/**
	 * Create core LMS tables
	 *
	 * @since 1.0.0
	 * @param string $prefix Database prefix
	 * @param string $charset_collate Charset and collation
	 */
	private static function create_core_tables( $prefix, $charset_collate ) {

		// 1. Enrollments table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_enrollments (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			group_id bigint(20) UNSIGNED DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			enrolled_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at datetime DEFAULT NULL,
			progress decimal(5,2) NOT NULL DEFAULT 0.00,
			last_activity_at datetime DEFAULT NULL,
			certificate_url varchar(500) DEFAULT NULL,
			certificate_issued_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY group_id (group_id),
			KEY status (status),
			KEY enrolled_at (enrolled_at),
			UNIQUE KEY unique_enrollment (user_id, course_id)
		) $charset_collate;";

		dbDelta( $sql );

		// 2. Progress table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_progress (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) UNSIGNED NOT NULL,
			content_type varchar(20) NOT NULL,
			content_id bigint(20) UNSIGNED NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'not_started',
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			time_spent int(11) UNSIGNED DEFAULT 0,
			last_position text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY enrollment_id (enrollment_id),
			KEY content_type (content_type),
			KEY content_id (content_id),
			KEY status (status),
			UNIQUE KEY unique_progress (enrollment_id, content_type, content_id)
		) $charset_collate;";

		dbDelta( $sql );

		// 3. Quiz Attempts table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_quiz_attempts (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) UNSIGNED NOT NULL,
			quiz_id bigint(20) UNSIGNED NOT NULL,
			attempt_number int(11) UNSIGNED NOT NULL DEFAULT 1,
			started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at datetime DEFAULT NULL,
			time_spent int(11) UNSIGNED DEFAULT 0,
			score decimal(5,2) DEFAULT NULL,
			max_score decimal(5,2) NOT NULL,
			percentage decimal(5,2) DEFAULT NULL,
			passed tinyint(1) DEFAULT NULL,
			answers longtext DEFAULT NULL,
			PRIMARY KEY (id),
			KEY enrollment_id (enrollment_id),
			KEY quiz_id (quiz_id),
			KEY started_at (started_at)
		) $charset_collate;";

		dbDelta( $sql );

		// 4. Certificates table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_certificates (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) UNSIGNED NOT NULL,
			certificate_code varchar(100) NOT NULL,
			issued_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			template_id bigint(20) UNSIGNED DEFAULT NULL,
			file_url varchar(500) DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			PRIMARY KEY (id),
			KEY enrollment_id (enrollment_id),
			UNIQUE KEY certificate_code (certificate_code),
			KEY issued_at (issued_at)
		) $charset_collate;";

		dbDelta( $sql );

		// 5. Points table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_points (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			points int(11) NOT NULL,
			reason varchar(255) NOT NULL,
			reference_type varchar(50) DEFAULT NULL,
			reference_id bigint(20) UNSIGNED DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY created_at (created_at),
			KEY reference_type (reference_type, reference_id)
		) $charset_collate;";

		dbDelta( $sql );

		// 6. Achievements table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_achievements (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			achievement_type varchar(50) NOT NULL,
			achievement_id varchar(100) NOT NULL,
			earned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			metadata longtext DEFAULT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY achievement_type (achievement_type),
			UNIQUE KEY unique_achievement (user_id, achievement_id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create group-related tables
	 *
	 * @since 1.0.0
	 * @param string $prefix Database prefix
	 * @param string $charset_collate Charset and collation
	 */
	private static function create_group_tables( $prefix, $charset_collate ) {

		// 7. Groups table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_groups (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text DEFAULT NULL,
			created_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			status varchar(20) NOT NULL DEFAULT 'active',
			max_members int(11) UNSIGNED DEFAULT NULL,
			PRIMARY KEY (id),
			KEY created_by (created_by),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql );

		// 8. Group Members table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_group_members (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			group_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			role varchar(20) NOT NULL DEFAULT 'member',
			joined_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY user_id (user_id),
			UNIQUE KEY unique_membership (group_id, user_id)
		) $charset_collate;";

		dbDelta( $sql );

		// 9. Group Courses table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_group_courses (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			group_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			deadline datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY course_id (course_id),
			UNIQUE KEY unique_group_course (group_id, course_id)
		) $charset_collate;";

		dbDelta( $sql );

		// 10. Group Content table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_group_content (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			group_id bigint(20) UNSIGNED NOT NULL,
			title varchar(255) NOT NULL,
			content longtext DEFAULT NULL,
			file_url varchar(500) DEFAULT NULL,
			created_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY created_by (created_by),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create versioning tables
	 *
	 * @since 1.0.0
	 * @param string $prefix Database prefix
	 * @param string $charset_collate Charset and collation
	 */
	private static function create_versioning_tables( $prefix, $charset_collate ) {

		// 11. Content Versions table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_content_versions (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			content_type varchar(20) NOT NULL,
			content_id bigint(20) UNSIGNED NOT NULL,
			version_number int(11) UNSIGNED NOT NULL,
			data longtext NOT NULL,
			created_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			change_note text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY content_type (content_type),
			KEY content_id (content_id),
			KEY version_number (version_number),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql );

		// 12. Content Archives table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_content_archives (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			content_type varchar(20) NOT NULL,
			content_id bigint(20) UNSIGNED NOT NULL,
			archive_url varchar(500) NOT NULL,
			archived_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			archived_by bigint(20) UNSIGNED NOT NULL,
			reason text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY content_type (content_type),
			KEY content_id (content_id),
			KEY archived_at (archived_at)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create scheduling tables
	 *
	 * @since 1.0.0
	 * @param string $prefix Database prefix
	 * @param string $charset_collate Charset and collation
	 */
	private static function create_scheduling_tables( $prefix, $charset_collate ) {

		// 13. Scheduled Content table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_scheduled_content (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			content_type varchar(20) NOT NULL,
			content_id bigint(20) UNSIGNED NOT NULL,
			scheduled_at datetime NOT NULL,
			repeat_type varchar(20) DEFAULT NULL,
			repeat_period int(11) UNSIGNED DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY content_type (content_type),
			KEY content_id (content_id),
			KEY scheduled_at (scheduled_at),
			KEY status (status)
		) $charset_collate;";

		dbDelta( $sql );

		// 14. Drip Content table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_drip_content (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			course_id bigint(20) UNSIGNED NOT NULL,
			content_type varchar(20) NOT NULL,
			content_id bigint(20) UNSIGNED NOT NULL,
			unlock_after_days int(11) UNSIGNED NOT NULL,
			unlock_condition varchar(50) DEFAULT NULL,
			PRIMARY KEY (id),
			KEY course_id (course_id),
			KEY content_type (content_type),
			KEY content_id (content_id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create system tables
	 *
	 * @since 1.0.0
	 * @param string $prefix Database prefix
	 * @param string $charset_collate Charset and collation
	 */
	private static function create_system_tables( $prefix, $charset_collate ) {

		// 15. Error Log table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_error_log (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			level varchar(20) NOT NULL,
			message text NOT NULL,
			context longtext DEFAULT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			url varchar(500) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY level (level),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql );

		// 16. Cache table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_cache (
			cache_key varchar(191) NOT NULL,
			cache_value longtext NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY (cache_key),
			KEY expires_at (expires_at)
		) $charset_collate;";

		dbDelta( $sql );

		// 17. Question Bank table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_question_bank (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			quiz_id bigint(20) UNSIGNED NOT NULL,
			question_type varchar(50) NOT NULL,
			question_text longtext NOT NULL,
			options longtext DEFAULT NULL,
			correct_answer longtext DEFAULT NULL,
			points decimal(5,2) NOT NULL DEFAULT 1.00,
			order_index int(11) UNSIGNED NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY quiz_id (quiz_id),
			KEY question_type (question_type),
			KEY order_index (order_index)
		) $charset_collate;";

		dbDelta( $sql );

		// 18. Email Queue table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_email_queue (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			to_email varchar(255) NOT NULL,
			subject varchar(500) NOT NULL,
			message longtext NOT NULL,
			headers text DEFAULT NULL,
			attachments longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			scheduled_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			sent_at datetime DEFAULT NULL,
			attempts int(11) UNSIGNED NOT NULL DEFAULT 0,
			last_error text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY status (status),
			KEY scheduled_at (scheduled_at)
		) $charset_collate;";

		dbDelta( $sql );

		// 19. Notification Preferences table
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_notification_preferences (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			notification_type varchar(50) NOT NULL,
			enabled tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			UNIQUE KEY unique_user_notification (user_id, notification_type)
		) $charset_collate;";

		dbDelta( $sql );

		// 20. API Keys table (for future REST API authentication)
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_api_keys (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			api_key varchar(64) NOT NULL,
			api_secret varchar(64) NOT NULL,
			name varchar(255) NOT NULL,
			permissions longtext DEFAULT NULL,
			last_used_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at datetime DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY (id),
			UNIQUE KEY api_key (api_key),
			KEY user_id (user_id),
			KEY status (status)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create upload directories
	 *
	 * @since 1.0.0
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

				// Add index.php to prevent directory listing
				$index_file = $dir . '/index.php';
				if ( ! file_exists( $index_file ) ) {
					file_put_contents( $index_file, '<?php // Silence is golden.' );
				}

				// Add .htaccess for security
				$htaccess_file = $dir . '/.htaccess';
				if ( ! file_exists( $htaccess_file ) ) {
					file_put_contents( $htaccess_file, 'deny from all' );
				}
			}
		}
	}

	/**
	 * Set default plugin options
	 *
	 * @since 1.0.0
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
			'max_upload_size'         => 10, // MB
			'allowed_file_types'      => array( 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'mp3' ),
			'drip_content_enabled'    => true,
			'group_licenses_enabled'  => true,
			'woocommerce_integration' => false,
		);

		// Only set if not already set
		if ( ! get_option( 'saw_lms_settings' ) ) {
			add_option( 'saw_lms_settings', $defaults );
		}

		// Set installed timestamp
		if ( ! get_option( 'saw_lms_installed_at' ) ) {
			add_option( 'saw_lms_installed_at', current_time( 'mysql' ) );
		}
	}
}