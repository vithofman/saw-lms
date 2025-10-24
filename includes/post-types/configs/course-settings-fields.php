<?php
/**
 * Course Settings Tab Fields Configuration
 *
 * Defines ALL fields for the "Settings" tab in Course meta box.
 * Maps to ALL columns in wp_saw_lms_courses database table.
 *
 * COMPLETE in v3.1.1: All database fields included.
 * FIXED in v3.1.3: Removed truncated content, added missing fields.
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types/Configs
 * @since       3.1.0
 * @version     3.1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * SEKCE 1: ZÁKLADNÍ NASTAVENÍ
	 * ═══════════════════════════════════════════════════════════════════════
	 */

	'_section_basic' => array(
		'type'        => 'heading',
		'label'       => __( 'Basic Settings', 'saw-lms' ),
		'description' => __( 'Core course settings that affect learning experience.', 'saw-lms' ),
	),

	'_saw_lms_duration_minutes' => array(
		'type'        => 'number',
		'label'       => __( 'Duration (Minutes)', 'saw-lms' ),
		'description' => __( 'Total estimated time to complete the course in minutes.', 'saw-lms' ),
		'placeholder' => '0',
		'default'     => 0,
		'min'         => 0,
		'step'        => 1,
	),

	'_saw_lms_passing_score_percent' => array(
		'type'        => 'number',
		'label'       => __( 'Passing Score (%)', 'saw-lms' ),
		'description' => __( 'Minimum percentage required to pass the course (0-100).', 'saw-lms' ),
		'placeholder' => '70',
		'default'     => 70,
		'min'         => 0,
		'max'         => 100,
		'step'        => 1,
	),

	'_saw_lms_progression_mode' => array(
		'type'        => 'select',
		'label'       => __( 'Progression Mode', 'saw-lms' ),
		'description' => __( 'How students progress through the course.', 'saw-lms' ),
		'options'     => array(
			'linear'   => __( 'Linear (Sequential) - Must complete in order', 'saw-lms' ),
			'freeform' => __( 'Freeform (Any Order) - Can skip around', 'saw-lms' ),
		),
		'default'     => 'linear',
	),

	'_saw_lms_require_all_lessons' => array(
		'type'        => 'checkbox',
		'label'       => __( 'Require All Lessons', 'saw-lms' ),
		'description' => __( 'Students must complete all lessons to finish the course.', 'saw-lms' ),
		'default'     => '1',
	),

	'_saw_lms_require_all_quizzes' => array(
		'type'        => 'checkbox',
		'label'       => __( 'Require All Quizzes', 'saw-lms' ),
		'description' => __( 'Students must pass all quizzes to finish the course.', 'saw-lms' ),
		'default'     => '1',
	),

	'_saw_lms_difficulty' => array(
		'type'        => 'select',
		'label'       => __( 'Course Difficulty', 'saw-lms' ),
		'description' => __( 'Select the difficulty level for this course.', 'saw-lms' ),
		'options'     => array(
			''             => __( '— Select Difficulty —', 'saw-lms' ),
			'beginner'     => __( 'Beginner', 'saw-lms' ),
			'intermediate' => __( 'Intermediate', 'saw-lms' ),
			'advanced'     => __( 'Advanced', 'saw-lms' ),
			'expert'       => __( 'Expert', 'saw-lms' ),
		),
		'default'     => '',
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * SEKCE 2: ACCESS CONTROL & ENROLLMENT
	 * ═══════════════════════════════════════════════════════════════════════
	 */

	'_section_access' => array(
		'type'        => 'heading',
		'label'       => __( 'Access Control & Enrollment', 'saw-lms' ),
		'description' => __( 'Control who can enroll and when the course is available.', 'saw-lms' ),
	),

	'_saw_lms_access_mode' => array(
		'type'        => 'select',
		'label'       => __( 'Access Mode', 'saw-lms' ),
		'description' => __( 'How users can access this course.', 'saw-lms' ),
		'options'     => array(
			'open'     => __( 'Open - Anyone can access', 'saw-lms' ),
			'free'     => __( 'Free - Must enroll (no payment)', 'saw-lms' ),
			'purchase' => __( 'Purchase - Must buy to access', 'saw-lms' ),
			'closed'   => __( 'Closed - Admin-only enrollment', 'saw-lms' ),
		),
		'default'     => 'open',
	),

	'_saw_lms_enrollment_type' => array(
		'type'        => 'select',
		'label'       => __( 'Enrollment Type', 'saw-lms' ),
		'description' => __( 'How students get enrolled in the course.', 'saw-lms' ),
		'options'     => array(
			'automatic'  => __( 'Automatic - Enroll on purchase/signup', 'saw-lms' ),
			'manual'     => __( 'Manual - Admin must approve', 'saw-lms' ),
			'invitation' => __( 'Invitation Only - Must be invited', 'saw-lms' ),
		),
		'default'     => 'automatic',
	),

	'_saw_lms_student_limit' => array(
		'type'        => 'number',
		'label'       => __( 'Student Limit', 'saw-lms' ),
		'description' => __( 'Maximum number of students allowed (0 = unlimited).', 'saw-lms' ),
		'placeholder' => '0',
		'default'     => 0,
		'min'         => 0,
		'step'        => 1,
	),

	'_saw_lms_start_date' => array(
		'type'        => 'date',
		'label'       => __( 'Start Date', 'saw-lms' ),
		'description' => __( 'Course becomes available on this date (leave empty for immediate availability).', 'saw-lms' ),
		'default'     => '',
	),

	'_saw_lms_end_date' => array(
		'type'        => 'date',
		'label'       => __( 'End Date', 'saw-lms' ),
		'description' => __( 'Course access ends on this date (leave empty for no end date).', 'saw-lms' ),
		'default'     => '',
	),

	'_saw_lms_access_period_days' => array(
		'type'        => 'number',
		'label'       => __( 'Access Period (Days)', 'saw-lms' ),
		'description' => __( 'Number of days student has access after enrollment (0 = lifetime).', 'saw-lms' ),
		'placeholder' => '0',
		'default'     => 0,
		'min'         => 0,
		'step'        => 1,
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * SEKCE 3: PRICING (WooCommerce Integration)
	 * ═══════════════════════════════════════════════════════════════════════
	 */

	'_section_pricing' => array(
		'type'        => 'heading',
		'label'       => __( 'Pricing & WooCommerce', 'saw-lms' ),
		'description' => __( 'Pricing information (mainly managed via WooCommerce product).', 'saw-lms' ),
	),

	'_saw_lms_price' => array(
		'type'        => 'number',
		'label'       => __( 'Price', 'saw-lms' ),
		'description' => __( 'Course price (informational - actual pricing via WooCommerce product).', 'saw-lms' ),
		'placeholder' => '0.00',
		'default'     => 0,
		'min'         => 0,
		'step'        => 0.01,
	),

	'_saw_lms_currency' => array(
		'type'        => 'text',
		'label'       => __( 'Currency', 'saw-lms' ),
		'description' => __( 'Currency code (e.g., USD, EUR, CZK).', 'saw-lms' ),
		'placeholder' => 'USD',
		'default'     => 'USD',
	),

	'_saw_lms_product_id' => array(
		'type'        => 'number',
		'label'       => __( 'Primary WooCommerce Product ID', 'saw-lms' ),
		'description' => __( 'ID of the main WooCommerce product linked to this course (optional - can be set automatically).', 'saw-lms' ),
		'placeholder' => '0',
		'default'     => 0,
		'min'         => 0,
		'step'        => 1,
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * SEKCE 4: CERTIFICATES
	 * ═══════════════════════════════════════════════════════════════════════
	 */

	'_section_certificates' => array(
		'type'        => 'heading',
		'label'       => __( 'Certificates', 'saw-lms' ),
		'description' => __( 'Certificate settings for course completion.', 'saw-lms' ),
	),

	'_saw_lms_certificate_id' => array(
		'type'        => 'number',
		'label'       => __( 'Certificate Template ID', 'saw-lms' ),
		'description' => __( 'ID of the certificate template to use (0 = default template).', 'saw-lms' ),
		'placeholder' => '0',
		'default'     => 0,
		'min'         => 0,
		'step'        => 1,
	),

	'_saw_lms_certificate_require_passing' => array(
		'type'        => 'checkbox',
		'label'       => __( 'Require Passing Score', 'saw-lms' ),
		'description' => __( 'Student must achieve passing score to receive certificate.', 'saw-lms' ),
		'default'     => '1',
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * SEKCE 5: RECURRING COURSES (BOZP Compliance)
	 * ═══════════════════════════════════════════════════════════════════════
	 */

	'_section_recurring' => array(
		'type'        => 'heading',
		'label'       => __( 'Recurring Courses (BOZP)', 'saw-lms' ),
		'description' => __( 'Settings for courses that must be retaken periodically (e.g., safety training).', 'saw-lms' ),
	),

	'_saw_lms_is_recurring' => array(
		'type'        => 'checkbox',
		'label'       => __( 'Enable Recurring', 'saw-lms' ),
		'description' => __( 'This course must be retaken periodically (for compliance/BOZP).', 'saw-lms' ),
		'default'     => '',
	),

	'_saw_lms_recurrence_type' => array(
		'type'        => 'select',
		'label'       => __( 'Recurrence Type', 'saw-lms' ),
		'description' => __( 'How often the course must be retaken.', 'saw-lms' ),
		'options'     => array(
			'none'    => __( 'None', 'saw-lms' ),
			'monthly' => __( 'Monthly', 'saw-lms' ),
			'yearly'  => __( 'Yearly', 'saw-lms' ),
			'custom'  => __( 'Custom Interval', 'saw-lms' ),
		),
		'default'     => 'none',
	),

	'_saw_lms_recurrence_interval' => array(
		'type'        => 'number',
		'label'       => __( 'Recurrence Interval (Months)', 'saw-lms' ),
		'description' => __( 'Number of months before course must be retaken (for custom recurrence).', 'saw-lms' ),
		'placeholder' => '12',
		'default'     => 12,
		'min'         => 1,
		'step'        => 1,
	),

	'_saw_lms_grace_period_days' => array(
		'type'        => 'number',
		'label'       => __( 'Grace Period (Days)', 'saw-lms' ),
		'description' => __( 'Number of days after expiration before access is blocked.', 'saw-lms' ),
		'placeholder' => '0',
		'default'     => 0,
		'min'         => 0,
		'step'        => 1,
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * SEKCE 6: GAMIFICATION
	 * ═══════════════════════════════════════════════════════════════════════
	 */

	'_section_gamification' => array(
		'type'        => 'heading',
		'label'       => __( 'Gamification & Points', 'saw-lms' ),
		'description' => __( 'Points and rewards for course completion.', 'saw-lms' ),
	),

	'_saw_lms_points' => array(
		'type'        => 'number',
		'label'       => __( 'Points Awarded', 'saw-lms' ),
		'description' => __( 'Number of points awarded for completing this course.', 'saw-lms' ),
		'placeholder' => '100',
		'default'     => 100,
		'min'         => 0,
		'step'        => 1,
	),

	'_saw_lms_bonus_points_perfect' => array(
		'type'        => 'number',
		'label'       => __( 'Bonus Points (Perfect Score)', 'saw-lms' ),
		'description' => __( 'Extra points for achieving 100% score.', 'saw-lms' ),
		'placeholder' => '50',
		'default'     => 0,
		'min'         => 0,
		'step'        => 1,
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * SEKCE 7: MARKETING & PROMO
	 * ═══════════════════════════════════════════════════════════════════════
	 */

	'_section_marketing' => array(
		'type'        => 'heading',
		'label'       => __( 'Marketing & Promotion', 'saw-lms' ),
		'description' => __( 'Marketing materials and promotional content.', 'saw-lms' ),
	),

	'_saw_lms_promo_video_url' => array(
		'type'        => 'url',
		'label'       => __( 'Promo Video URL', 'saw-lms' ),
		'description' => __( 'URL to promotional/preview video (YouTube, Vimeo, or direct link).', 'saw-lms' ),
		'placeholder' => 'https://www.youtube.com/watch?v=...',
		'default'     => '',
	),

	'_saw_lms_promo_text' => array(
		'type'        => 'textarea',
		'label'       => __( 'Promotional Text', 'saw-lms' ),
		'description' => __( 'Short promotional text for course cards/listings.', 'saw-lms' ),
		'placeholder' => __( 'Learn advanced techniques in...', 'saw-lms' ),
		'default'     => '',
		'rows'        => 3,
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * SEKCE 8: ADVANCED SETTINGS
	 * ═══════════════════════════════════════════════════════════════════════
	 */

	'_section_advanced' => array(
		'type'        => 'heading',
		'label'       => __( 'Advanced Settings', 'saw-lms' ),
		'description' => __( 'Prerequisites, notifications, and custom fields.', 'saw-lms' ),
	),

	'_saw_lms_prerequisites' => array(
		'type'        => 'textarea',
		'label'       => __( 'Prerequisites (JSON)', 'saw-lms' ),
		'description' => __( 'JSON array of required course IDs. Example: [10, 15, 20]', 'saw-lms' ),
		'placeholder' => '[]',
		'default'     => '[]',
		'rows'        => 3,
	),

	'_saw_lms_email_notifications' => array(
		'type'        => 'textarea',
		'label'       => __( 'Email Notifications (JSON)', 'saw-lms' ),
		'description' => __( 'JSON object with email notification settings. Example: {"on_enroll": true, "on_complete": true}', 'saw-lms' ),
		'placeholder' => '{}',
		'default'     => '{}',
		'rows'        => 3,
	),

	'_saw_lms_custom_fields' => array(
		'type'        => 'textarea',
		'label'       => __( 'Custom Fields (JSON)', 'saw-lms' ),
		'description' => __( 'JSON object for storing custom data. Example: {"instructor": "John Doe", "location": "Online"}', 'saw-lms' ),
		'placeholder' => '{}',
		'default'     => '{}',
		'rows'        => 5,
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * SEKCE 9: STATUS & METADATA (Read-only/Auto-managed)
	 * ═══════════════════════════════════════════════════════════════════════
	 */

	'_section_metadata' => array(
		'type'        => 'heading',
		'label'       => __( 'Course Metadata (Informational)', 'saw-lms' ),
		'description' => __( 'System-managed metadata (most fields auto-updated).', 'saw-lms' ),
	),

	'_saw_lms_status' => array(
		'type'        => 'select',
		'label'       => __( 'Course Status', 'saw-lms' ),
		'description' => __( 'Publication status of the course.', 'saw-lms' ),
		'options'     => array(
			'draft'     => __( 'Draft', 'saw-lms' ),
			'published' => __( 'Published', 'saw-lms' ),
			'archived'  => __( 'Archived', 'saw-lms' ),
		),
		'default'     => 'draft',
	),

	'_saw_lms_author_id' => array(
		'type'        => 'readonly',
		'label'       => __( 'Author ID', 'saw-lms' ),
		'description' => __( 'WordPress user ID of course author (auto-set on creation).', 'saw-lms' ),
		'default'     => '',
	),

	'_saw_lms_created_at' => array(
		'type'        => 'readonly',
		'label'       => __( 'Created At', 'saw-lms' ),
		'description' => __( 'Date and time when course was created (auto-set).', 'saw-lms' ),
		'default'     => '',
	),

	'_saw_lms_updated_at' => array(
		'type'        => 'readonly',
		'label'       => __( 'Last Updated', 'saw-lms' ),
		'description' => __( 'Date and time of last modification (auto-updated).', 'saw-lms' ),
		'default'     => '',
	),

);