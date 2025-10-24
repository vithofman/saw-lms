<?php
/**
 * Database Schema
 *
 * Defines the database schema for SAW LMS plugin.
 * Creates all necessary tables on plugin activation.
 *
 * UPDATED in v3.0.0: Added structured content tables (courses, sections, lessons, quizzes).
 * These tables replace the old postmeta approach for better performance.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/database
 * @since      1.0.0
 * @version    3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Schema Class
 *
 * Handles database schema creation and updates.
 *
 * @since 1.0.0
 */
class SAW_LMS_Schema {

	/**
	 * Database version
	 *
	 * UPDATED: Incremented to 3.0.0 for new structured content tables.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	const DB_VERSION = '3.0.0';

	/**
	 * Create all database tables
	 *
	 * Main entry point for creating all SAW LMS tables.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();

		// Require upgrade file for dbDelta.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create all table groups.
		self::create_structured_content_tables( $prefix, $charset_collate );
		self::create_core_tables( $prefix, $charset_collate );
		self::create_group_tables( $prefix, $charset_collate );
		self::create_versioning_tables( $prefix, $charset_collate );
		self::create_scheduling_tables( $prefix, $charset_collate );
		self::create_system_tables( $prefix, $charset_collate );
	}

	/**
	 * Create structured content tables
	 *
	 * NEW in v3.0.0: Creates tables for structured storage of course data.
	 * Replaces the old postmeta approach with single-table storage.
	 *
	 * Performance improvement: ~80 SQL queries per course → 1 SQL query.
	 *
	 * @since  3.0.0
	 * @param  string $prefix          Database prefix.
	 * @param  string $charset_collate Charset and collation.
	 * @return void
	 */
	private static function create_structured_content_tables( $prefix, $charset_collate ) {

		// 1. Courses table.
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_courses (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id bigint(20) UNSIGNED NOT NULL,
			duration_minutes int(11) UNSIGNED DEFAULT 0,
			estimated_hours decimal(5,2) DEFAULT 0.00,
			passing_score_percent decimal(5,2) DEFAULT 70.00,
			progression_mode varchar(20) DEFAULT 'flexible',
			require_all_lessons tinyint(1) DEFAULT 0,
			require_all_quizzes tinyint(1) DEFAULT 0,
			require_all_assignments tinyint(1) DEFAULT 0,
			access_mode varchar(20) DEFAULT 'open',
			price decimal(10,2) DEFAULT 0.00,
			currency varchar(10) DEFAULT 'USD',
			recurring_interval varchar(20) DEFAULT NULL,
			recurring_price decimal(10,2) DEFAULT NULL,
			payment_gateway varchar(50) DEFAULT NULL,
			button_url varchar(500) DEFAULT NULL,
			button_text varchar(255) DEFAULT NULL,
			enrollment_type varchar(20) DEFAULT 'open',
			student_limit int(11) UNSIGNED DEFAULT NULL,
			waitlist_enabled tinyint(1) DEFAULT 0,
			enrollment_deadline datetime DEFAULT NULL,
			start_date datetime DEFAULT NULL,
			end_date datetime DEFAULT NULL,
			access_duration_days int(11) UNSIGNED DEFAULT NULL,
			timezone varchar(50) DEFAULT 'UTC',
			drip_enabled tinyint(1) DEFAULT 0,
			drip_type varchar(20) DEFAULT NULL,
			drip_interval_days int(11) UNSIGNED DEFAULT NULL,
			prerequisites_enabled tinyint(1) DEFAULT 0,
			prerequisite_courses longtext DEFAULT NULL,
			prerequisite_achievements longtext DEFAULT NULL,
			repeat_enabled tinyint(1) DEFAULT 0,
			repeat_period_months int(11) UNSIGNED DEFAULT NULL,
			retake_count int(11) UNSIGNED DEFAULT NULL,
			retake_cooldown_days int(11) UNSIGNED DEFAULT NULL,
			certificate_enabled tinyint(1) DEFAULT 0,
			certificate_template_id bigint(20) UNSIGNED DEFAULT NULL,
			certificate_passing_score decimal(5,2) DEFAULT NULL,
			points_enabled tinyint(1) DEFAULT 0,
			points_completion int(11) UNSIGNED DEFAULT 0,
			points_per_lesson int(11) UNSIGNED DEFAULT 0,
			points_per_quiz int(11) UNSIGNED DEFAULT 0,
			badge_enabled tinyint(1) DEFAULT 0,
			badge_id bigint(20) UNSIGNED DEFAULT NULL,
			leaderboard_enabled tinyint(1) DEFAULT 0,
			completion_criteria varchar(50) DEFAULT 'all_content',
			completion_percentage decimal(5,2) DEFAULT 100.00,
			featured tinyint(1) DEFAULT 0,
			featured_order int(11) UNSIGNED DEFAULT 0,
			promo_video_url varchar(500) DEFAULT NULL,
			discussion_enabled tinyint(1) DEFAULT 0,
			qa_enabled tinyint(1) DEFAULT 0,
			peer_review_enabled tinyint(1) DEFAULT 0,
			email_enrollment tinyint(1) DEFAULT 1,
			email_completion tinyint(1) DEFAULT 1,
			email_certificate tinyint(1) DEFAULT 1,
			email_quiz_failed tinyint(1) DEFAULT 1,
			instructors longtext DEFAULT NULL,
			co_instructors longtext DEFAULT NULL,
			language varchar(10) DEFAULT 'en',
			age_restriction int(11) UNSIGNED DEFAULT NULL,
			is_archived tinyint(1) DEFAULT 0,
			version int(11) UNSIGNED DEFAULT 1,
			seo_title varchar(255) DEFAULT NULL,
			seo_description text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY post_id (post_id),
			KEY access_mode (access_mode),
			KEY start_date (start_date),
			KEY end_date (end_date),
			KEY featured (featured),
			KEY is_archived (is_archived),
			KEY price (price),
			KEY enrollment_type (enrollment_type)
		) $charset_collate;";

		dbDelta( $sql );

		// 2. Sections table.
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_sections (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			section_order int(11) UNSIGNED DEFAULT 0,
			video_url varchar(500) DEFAULT NULL,
			documents longtext DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY post_id (post_id),
			KEY course_id (course_id),
			KEY section_order (section_order)
		) $charset_collate;";

		dbDelta( $sql );

		// 3. Lessons table.
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_lessons (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id bigint(20) UNSIGNED NOT NULL,
			section_id bigint(20) UNSIGNED NOT NULL,
			lesson_type varchar(20) DEFAULT 'video',
			lesson_order int(11) UNSIGNED DEFAULT 0,
			duration_minutes int(11) UNSIGNED DEFAULT 0,
			video_source varchar(20) DEFAULT NULL,
			video_url varchar(500) DEFAULT NULL,
			document_url varchar(500) DEFAULT NULL,
			assignment_max_points decimal(5,2) DEFAULT NULL,
			assignment_passing_points decimal(5,2) DEFAULT NULL,
			assignment_allow_resubmit tinyint(1) DEFAULT 0,
			is_required tinyint(1) DEFAULT 1,
			preview_enabled tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY post_id (post_id),
			KEY section_id (section_id),
			KEY lesson_order (lesson_order),
			KEY lesson_type (lesson_type)
		) $charset_collate;";

		dbDelta( $sql );

		// 4. Quizzes table.
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_quizzes (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED DEFAULT NULL,
			section_id bigint(20) UNSIGNED DEFAULT NULL,
			passing_score_percent decimal(5,2) DEFAULT 70.00,
			time_limit_minutes int(11) UNSIGNED DEFAULT NULL,
			max_attempts int(11) UNSIGNED DEFAULT NULL,
			randomize_questions tinyint(1) DEFAULT 0,
			randomize_answers tinyint(1) DEFAULT 0,
			show_correct_answers varchar(20) DEFAULT 'after_last_attempt',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY post_id (post_id),
			KEY course_id (course_id),
			KEY section_id (section_id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create core LMS tables
	 *
	 * Creates tables for enrollments, progress, quiz attempts, etc.
	 *
	 * @since  1.0.0
	 * @param  string $prefix          Database prefix.
	 * @param  string $charset_collate Charset and collation.
	 * @return void
	 */
	private static function create_core_tables( $prefix, $charset_collate ) {

		// 1. Enrollments table.
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

		// 2. Progress table.
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

		// 3. Quiz Attempts table.
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

		// 4. Certificates table.
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

		// 5. Points table.
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

		// 6. Achievements table.
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
	 * @since  1.0.0
	 * @param  string $prefix          Database prefix.
	 * @param  string $charset_collate Charset and collation.
	 * @return void
	 */
	private static function create_group_tables( $prefix, $charset_collate ) {

		// 7. Groups table.
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

		// 8. Group Members table.
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

		// 9. Group Courses table.
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

		// 10. Group Content table.
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
	 * @since  1.0.0
	 * @param  string $prefix          Database prefix.
	 * @param  string $charset_collate Charset and collation.
	 * @return void
	 */
	private static function create_versioning_tables( $prefix, $charset_collate ) {

		// 11. Content Versions table.
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

		// 12. Content Archives table.
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
	 * @since  1.0.0
	 * @param  string $prefix          Database prefix.
	 * @param  string $charset_collate Charset and collation.
	 * @return void
	 */
	private static function create_scheduling_tables( $prefix, $charset_collate ) {

		// 13. Scheduled Content table.
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

		// 14. Drip Content table.
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
	 * @since  1.0.0
	 * @param  string $prefix          Database prefix.
	 * @param  string $charset_collate Charset and collation.
	 * @return void
	 */
	private static function create_system_tables( $prefix, $charset_collate ) {

		// 15. Error Log table.
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

		// 16. Cache table.
		$sql = "CREATE TABLE IF NOT EXISTS {$prefix}saw_lms_cache (
			cache_key varchar(191) NOT NULL,
			cache_value longtext NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY (cache_key),
			KEY expires_at (expires_at)
		) $charset_collate;";

		dbDelta( $sql );

		// 17. Question Bank table.
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

		// 18. Email Queue table.
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

		// 19. Notification Preferences table.
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
	}
}
