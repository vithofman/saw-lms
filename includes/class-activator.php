<?php
/**
 * Plugin Activator
 * 
 * Handles plugin activation - creates database tables and sets up initial configuration
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes
 * @since      1.0.0
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
	const DB_VERSION = '1.0.0';

	/**
	 * Plugin activation hook
	 * 
	 * Creates all database tables and sets up initial configuration
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
		
		// Flush rewrite rules
		flush_rewrite_rules();
		
		// OPRAVENO: Uložení DB verze
		update_option( 'saw_lms_db_version', self::DB_VERSION );
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
		$sql = "CREATE TABLE {$prefix}saw_lms_enrollments (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			attempt_number int(11) NOT NULL DEFAULT 1,
			status varchar(20) NOT NULL DEFAULT 'enrolled',
			source varchar(50) DEFAULT NULL,
			source_id bigint(20) unsigned DEFAULT NULL,
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			expires_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY status (status),
			KEY user_course (user_id, course_id),
			KEY expires_at (expires_at)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 2. Progress table
		$sql = "CREATE TABLE {$prefix}saw_lms_progress (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) unsigned NOT NULL,
			lesson_id bigint(20) unsigned NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'not_started',
			completion_percentage decimal(5,2) NOT NULL DEFAULT 0.00,
			time_spent int(11) NOT NULL DEFAULT 0,
			video_watched_seconds int(11) DEFAULT NULL,
			video_total_seconds int(11) DEFAULT NULL,
			last_position int(11) DEFAULT NULL,
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY enrollment_lesson (enrollment_id, lesson_id),
			KEY lesson_id (lesson_id),
			KEY status (status)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 3. Quiz attempts table
		$sql = "CREATE TABLE {$prefix}saw_lms_quiz_attempts (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) unsigned NOT NULL,
			quiz_id bigint(20) unsigned NOT NULL,
			attempt_number int(11) NOT NULL DEFAULT 1,
			answers_json longtext NOT NULL,
			score decimal(5,2) NOT NULL DEFAULT 0.00,
			passed tinyint(1) NOT NULL DEFAULT 0,
			time_taken int(11) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			started_at datetime NOT NULL,
			completed_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY enrollment_id (enrollment_id),
			KEY quiz_id (quiz_id),
			KEY passed (passed)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 4. Certificates table
		$sql = "CREATE TABLE {$prefix}saw_lms_certificates (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			enrollment_id bigint(20) unsigned NOT NULL,
			certificate_code varchar(100) NOT NULL,
			certificate_url varchar(255) DEFAULT NULL,
			score decimal(5,2) DEFAULT NULL,
			issued_at datetime NOT NULL,
			expires_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY certificate_code (certificate_code),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY enrollment_id (enrollment_id)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 5. Points ledger table
		$sql = "CREATE TABLE {$prefix}saw_lms_points_ledger (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			amount int(11) NOT NULL,
			balance int(11) NOT NULL,
			reason varchar(255) NOT NULL,
			reference_type varchar(50) DEFAULT NULL,
			reference_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 6. Activity log table
		$sql = "CREATE TABLE {$prefix}saw_lms_activity_log (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned DEFAULT NULL,
			action varchar(100) NOT NULL,
			entity_type varchar(50) DEFAULT NULL,
			entity_id bigint(20) unsigned DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY entity (entity_type, entity_id),
			KEY created_at (created_at)
		) $charset_collate;";
		dbDelta( $sql );
	}
	
	/**
	 * Create group management tables
	 *
	 * @since 1.0.0
	 * @param string $prefix Database prefix
	 * @param string $charset_collate Charset and collation
	 */
	private static function create_group_tables( $prefix, $charset_collate ) {
		
		// 7. Groups table
		$sql = "CREATE TABLE {$prefix}saw_lms_groups (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			admin_user_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			total_seats int(11) NOT NULL,
			used_seats int(11) NOT NULL DEFAULT 0,
			order_id bigint(20) unsigned DEFAULT NULL,
			expires_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY admin_user_id (admin_user_id),
			KEY course_id (course_id),
			KEY order_id (order_id)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 8. Group members table
		$sql = "CREATE TABLE {$prefix}saw_lms_group_members (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			role varchar(20) NOT NULL DEFAULT 'member',
			added_by bigint(20) unsigned DEFAULT NULL,
			joined_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			removed_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY group_user (group_id, user_id),
			KEY user_id (user_id),
			KEY role (role)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 9. Custom documents table
		$sql = "CREATE TABLE {$prefix}saw_lms_custom_documents (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			lesson_id bigint(20) unsigned NOT NULL,
			file_name varchar(255) NOT NULL,
			file_path varchar(255) NOT NULL,
			file_size bigint(20) unsigned NOT NULL,
			file_type varchar(50) NOT NULL,
			uploaded_by bigint(20) unsigned NOT NULL,
			uploaded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY lesson_id (lesson_id)
		) $charset_collate;";
		dbDelta( $sql );
	}
	
	/**
	 * Create versioning and compliance tables
	 *
	 * @since 1.0.0
	 * @param string $prefix Database prefix
	 * @param string $charset_collate Charset and collation
	 */
	private static function create_versioning_tables( $prefix, $charset_collate ) {
		
		// 10. Content versions table
		$sql = "CREATE TABLE {$prefix}saw_lms_content_versions (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entity_type varchar(50) NOT NULL,
			entity_id bigint(20) unsigned NOT NULL,
			version_number varchar(20) NOT NULL,
			content_hash varchar(64) NOT NULL,
			snapshot_json longtext NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY entity (entity_type, entity_id),
			KEY version_number (version_number),
			KEY content_hash (content_hash)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 11. Enrollment content versions table
		$sql = "CREATE TABLE {$prefix}saw_lms_enrollment_content_versions (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) unsigned NOT NULL,
			entity_type varchar(50) NOT NULL,
			entity_id bigint(20) unsigned NOT NULL,
			version_id bigint(20) unsigned NOT NULL,
			viewed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY enrollment_id (enrollment_id),
			KEY entity (entity_type, entity_id),
			KEY version_id (version_id)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 12. Content changelog table
		$sql = "CREATE TABLE {$prefix}saw_lms_content_changelog (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entity_type varchar(50) NOT NULL,
			entity_id bigint(20) unsigned NOT NULL,
			old_version_id bigint(20) unsigned DEFAULT NULL,
			new_version_id bigint(20) unsigned NOT NULL,
			change_summary text DEFAULT NULL,
			changed_by bigint(20) unsigned NOT NULL,
			changed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY entity (entity_type, entity_id),
			KEY changed_by (changed_by)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 13. Course completion snapshots table
		$sql = "CREATE TABLE {$prefix}saw_lms_course_completion_snapshots (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) unsigned NOT NULL,
			snapshot_json longtext NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY enrollment_id (enrollment_id)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 14. Document snapshots table
		$sql = "CREATE TABLE {$prefix}saw_lms_document_snapshots (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			enrollment_id bigint(20) unsigned NOT NULL,
			document_id bigint(20) unsigned NOT NULL,
			file_name varchar(255) NOT NULL,
			file_hash varchar(64) NOT NULL,
			snapshot_path varchar(255) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY enrollment_id (enrollment_id),
			KEY file_hash (file_hash)
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
		
		// 15. Course schedules table
		$sql = "CREATE TABLE {$prefix}saw_lms_course_schedules (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			enrollment_id bigint(20) unsigned NOT NULL,
			repeat_period_months int(11) NOT NULL,
			last_completed_at datetime NOT NULL,
			next_due_date datetime NOT NULL,
			reminder_sent_at datetime DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY next_due_date (next_due_date),
			KEY status (status)
		) $charset_collate;";
		dbDelta( $sql );
	}
	
	/**
	 * Create system tables (error log, cache)
	 *
	 * @since 1.0.0
	 * @param string $prefix Database prefix
	 * @param string $charset_collate Charset and collation
	 */
	private static function create_system_tables( $prefix, $charset_collate ) {
		
		// 16. Error log table
		$sql = "CREATE TABLE {$prefix}saw_lms_error_log (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			error_type varchar(20) NOT NULL,
			message text NOT NULL,
			context longtext DEFAULT NULL,
			file varchar(255) DEFAULT NULL,
			line int(11) DEFAULT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			url varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY error_type (error_type),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate;";
		dbDelta( $sql );
		
		// 17. Cache table (pro DB driver)
		$sql = "CREATE TABLE {$prefix}saw_lms_cache (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			cache_key varchar(255) NOT NULL,
			cache_value longtext NOT NULL,
			expires_at datetime NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY cache_key (cache_key),
			KEY expires_at (expires_at)
		) $charset_collate;";
		dbDelta( $sql );
	}
	
	/**
	 * Create upload directories with security
	 *
	 * @since 1.0.0
	 */
	private static function create_upload_directories() {
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'] . '/saw-lms';
		
		// Directories to create
		$directories = array(
			$base_dir,
			$base_dir . '/certificates',
			$base_dir . '/group-content',
			$base_dir . '/archives',
			$base_dir . '/temp',
			$base_dir . '/logs',
		);
		
		// .htaccess content to deny direct access
		$htaccess_content = "deny from all\n";
		
		// index.php content to prevent directory listing
		$index_content = "<?php\n// Silence is golden.\n";
		
		// Create each directory
		foreach ( $directories as $dir ) {
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			
			// Add .htaccess
			$htaccess_file = $dir . '/.htaccess';
			if ( ! file_exists( $htaccess_file ) ) {
				file_put_contents( $htaccess_file, $htaccess_content );
			}
			
			// Add index.php
			$index_file = $dir . '/index.php';
			if ( ! file_exists( $index_file ) ) {
				file_put_contents( $index_file, $index_content );
			}
		}
	}
	
	/**
	 * Set default plugin options
	 *
	 * @since 1.0.0
	 */
	private static function set_default_options() {
		// OPRAVENO: Ukládáme jako jednotlivé options
		update_option( 'saw_lms_version', SAW_LMS_VERSION );
		update_option( 'saw_lms_installed_at', current_time( 'mysql' ) );
		update_option( 'saw_lms_enable_certificates', true );
		update_option( 'saw_lms_enable_gamification', true );
		update_option( 'saw_lms_points_per_lesson', 10 );
		update_option( 'saw_lms_points_per_quiz', 20 );
		update_option( 'saw_lms_min_watch_percentage', 80 );
		
		// Také můžeme uložit kompletní settings array
		$default_settings = array(
			'version'           => SAW_LMS_VERSION,
			'installed_at'      => current_time( 'mysql' ),
			
			// General settings
			'course_slug'       => 'kurzy',
			'lesson_slug'       => 'lekce',
			'quiz_slug'         => 'kviz',
			
			// Video settings
			'min_watch_percent' => 80,
			'tracking_interval' => 10,
			
			// Quiz settings
			'default_passing_score' => 70,
			'default_max_attempts'  => 3,
			'randomize_questions'   => false,
			
			// Certificates
			'certificates_enabled'  => true,
			'enable_qr_code'        => true,
			'enable_verification'   => true,
			
			// Points
			'points_per_lesson'     => 10,
			'points_per_quiz'       => 20,
			'points_per_course'     => 100,
			'bonus_perfect_score'   => 50,
			'bonus_first_attempt'   => 25,
			
			// Groups
			'enable_custom_docs'    => true,
			'max_file_size'         => 10485760, // 10MB
			'allowed_file_types'    => 'pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
			
			// Notifications
			'email_from_name'       => get_bloginfo( 'name' ),
			'email_from_address'    => get_bloginfo( 'admin_email' ),
			
			// Compliance
			'enable_versioning'     => true,
			'retention_years'       => 7,
			
			// Advanced
			'debug_mode'            => false,
			'cache_driver'          => 'auto', // auto, redis, database, transient
		);
		
		add_option( 'saw_lms_settings', $default_settings );
	}
}