<?php
/**
 * Course Fields Configuration
 *
 * Complete configuration for all Course custom post type meta boxes and fields.
 * This config-based approach allows adding new fields without modifying PHP classes.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/config
 * @since      3.0.0
 * @version    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 1. BASIC SETTINGS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'basic_settings' => array(
		'title'    => __( 'Basic Settings', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'high',
		'fields'   => array(

			'_saw_lms_duration_minutes' => array(
				'label'       => __( 'Duration (Minutes)', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
				'description' => __( 'Total course duration in minutes.', 'saw-lms' ),
			),

			'_saw_lms_estimated_hours' => array(
				'label'       => __( 'Estimated Hours', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'step'        => 0.5,
				'description' => __( 'Estimated learning time in hours.', 'saw-lms' ),
			),

			'_saw_lms_passing_score_percent' => array(
				'label'       => __( 'Passing Score (%)', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 70,
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
				'description' => __( 'Minimum percentage required to pass the course.', 'saw-lms' ),
			),

			'_saw_lms_progression_mode' => array(
				'label'       => __( 'Progression Mode', 'saw-lms' ),
				'type'        => 'select',
				'default'     => 'linear',
				'options'     => array(
					'linear'   => __( 'Linear (Sequential)', 'saw-lms' ),
					'freeform' => __( 'Freeform (Any Order)', 'saw-lms' ),
				),
				'description' => __( 'How students progress through the course.', 'saw-lms' ),
			),

			'_saw_lms_require_all_lessons' => array(
				'label'          => __( 'Require All Lessons', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Students must complete all lessons to finish the course', 'saw-lms' ),
			),

			'_saw_lms_require_all_quizzes' => array(
				'label'          => __( 'Require All Quizzes', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Students must pass all quizzes to finish the course', 'saw-lms' ),
			),

			'_saw_lms_require_all_assignments' => array(
				'label'          => __( 'Require All Assignments', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Students must submit all assignments to finish the course', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 2. ACCESS & PRICING
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'access_pricing' => array(
		'title'    => __( 'Access & Pricing', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'high',
		'fields'   => array(

			'_saw_lms_access_mode' => array(
				'label'       => __( 'Access Mode', 'saw-lms' ),
				'type'        => 'select',
				'default'     => 'open',
				'options'     => array(
					'open'      => __( 'Open (Anyone can enroll)', 'saw-lms' ),
					'free'      => __( 'Free (Requires registration)', 'saw-lms' ),
					'buy_now'   => __( 'Buy Now (One-time payment)', 'saw-lms' ),
					'recurring' => __( 'Recurring Subscription', 'saw-lms' ),
					'closed'    => __( 'Closed (No enrollment)', 'saw-lms' ),
				),
				'description' => __( 'How users can access this course.', 'saw-lms' ),
			),

			'_saw_lms_price' => array(
				'label'       => __( 'Price', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'step'        => 0.01,
				'description' => __( 'One-time purchase price.', 'saw-lms' ),
			),

			'_saw_lms_currency' => array(
				'label'   => __( 'Currency', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'CZK',
				'options' => array(
					'CZK' => __( 'CZK (Czech Koruna)', 'saw-lms' ),
					'EUR' => __( 'EUR (Euro)', 'saw-lms' ),
					'USD' => __( 'USD (US Dollar)', 'saw-lms' ),
				),
			),

			'_saw_lms_recurring_interval' => array(
				'label'       => __( 'Recurring Interval', 'saw-lms' ),
				'type'        => 'select',
				'default'     => 'monthly',
				'options'     => array(
					'monthly' => __( 'Monthly', 'saw-lms' ),
					'yearly'  => __( 'Yearly', 'saw-lms' ),
				),
				'description' => __( 'For recurring subscriptions.', 'saw-lms' ),
			),

			'_saw_lms_recurring_price' => array(
				'label'       => __( 'Recurring Price', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'step'        => 0.01,
				'description' => __( 'Price per billing interval.', 'saw-lms' ),
			),

			'_saw_lms_payment_gateway' => array(
				'label'   => __( 'Payment Gateway', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'woocommerce',
				'options' => array(
					'stripe'      => __( 'Stripe', 'saw-lms' ),
					'paypal'      => __( 'PayPal', 'saw-lms' ),
					'woocommerce' => __( 'WooCommerce', 'saw-lms' ),
				),
			),

			'_saw_lms_button_url' => array(
				'label'       => __( 'Custom Button URL', 'saw-lms' ),
				'type'        => 'url',
				'default'     => '',
				'placeholder' => 'https://example.com/enroll',
				'description' => __( 'Override enrollment button URL (e.g., external payment page).', 'saw-lms' ),
			),

			'_saw_lms_button_text' => array(
				'label'       => __( 'Button Text', 'saw-lms' ),
				'type'        => 'text',
				'default'     => 'Enroll Now',
				'placeholder' => 'Enroll Now',
				'description' => __( 'Custom text for enrollment button.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 3. ENROLLMENT & CAPACITY
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'enrollment_capacity' => array(
		'title'    => __( 'Enrollment & Capacity', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_enrollment_type' => array(
				'label'   => __( 'Enrollment Type', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'open',
				'options' => array(
					'open'       => __( 'Open (Self-enrollment)', 'saw-lms' ),
					'approval'   => __( 'Requires Approval', 'saw-lms' ),
					'invitation' => __( 'Invitation Only', 'saw-lms' ),
					'code'       => __( 'Enrollment Code Required', 'saw-lms' ),
				),
			),

			'_saw_lms_student_limit' => array(
				'label'       => __( 'Student Limit', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Maximum number of students (0 = unlimited).', 'saw-lms' ),
			),

			'_saw_lms_waitlist_enabled' => array(
				'label'          => __( 'Enable Waitlist', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Allow waitlist when course is full', 'saw-lms' ),
			),

			'_saw_lms_enrollment_deadline' => array(
				'label'       => __( 'Enrollment Deadline', 'saw-lms' ),
				'type'        => 'date',
				'default'     => '',
				'description' => __( 'Last day to enroll in this course.', 'saw-lms' ),
			),

			'_saw_lms_auto_enroll_waitlist' => array(
				'label'          => __( 'Auto-enroll from Waitlist', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Automatically enroll waitlist students when spots open', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 4. TIME & SCHEDULE
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'time_schedule' => array(
		'title'    => __( 'Time & Schedule', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_start_date' => array(
				'label'       => __( 'Start Date', 'saw-lms' ),
				'type'        => 'date',
				'default'     => '',
				'description' => __( 'Course becomes available on this date.', 'saw-lms' ),
			),

			'_saw_lms_end_date' => array(
				'label'       => __( 'End Date', 'saw-lms' ),
				'type'        => 'date',
				'default'     => '',
				'description' => __( 'Course access ends on this date.', 'saw-lms' ),
			),

			'_saw_lms_access_period_days' => array(
				'label'       => __( 'Access Period (Days)', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Number of days students have access after enrollment (0 = unlimited).', 'saw-lms' ),
			),

			'_saw_lms_delete_progress_on_expire' => array(
				'label'          => __( 'Delete Progress on Expiry', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Delete student progress when access expires', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 5. PREREQUISITES
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'prerequisites' => array(
		'title'    => __( 'Prerequisites', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_prerequisites' => array(
				'label'       => __( 'Required Courses', 'saw-lms' ),
				'type'        => 'post_select',
				'post_type'   => 'saw_course',
				'multiple'    => true,
				'default'     => array(),
				'description' => __( 'Students must complete these courses before enrolling.', 'saw-lms' ),
			),

			'_saw_lms_prerequisite_type' => array(
				'label'   => __( 'Prerequisite Logic', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'all',
				'options' => array(
					'all'  => __( 'All (Must complete all prerequisites)', 'saw-lms' ),
					'any'  => __( 'Any (Complete at least one)', 'saw-lms' ),
					'x_of' => __( 'X of Y (Complete X number)', 'saw-lms' ),
				),
			),

			'_saw_lms_points_required' => array(
				'label'       => __( 'Points Required', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Minimum points required to enroll (0 = no requirement).', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 6. GAMIFICATION
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'gamification' => array(
		'title'    => __( 'Gamification', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_points_awarded' => array(
				'label'       => __( 'Points on Completion', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Points awarded when student completes the course.', 'saw-lms' ),
			),

			'_saw_lms_points_on_enrollment' => array(
				'label'       => __( 'Points on Enrollment', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Points awarded immediately upon enrollment.', 'saw-lms' ),
			),

			'_saw_lms_badge_on_completion' => array(
				'label'       => __( 'Badge on Completion', 'saw-lms' ),
				'type'        => 'post_select',
				'post_type'   => 'saw_badge',
				'multiple'    => false,
				'default'     => '',
				'description' => __( 'Badge awarded when course is completed.', 'saw-lms' ),
			),

			'_saw_lms_xp_on_completion' => array(
				'label'       => __( 'XP on Completion', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Experience points awarded on completion.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 7. CERTIFICATE
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'certificate' => array(
		'title'    => __( 'Certificate', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_certificate_id' => array(
				'label'       => __( 'Certificate Template', 'saw-lms' ),
				'type'        => 'post_select',
				'post_type'   => 'saw_certificate',
				'multiple'    => false,
				'default'     => '',
				'description' => __( 'Certificate template to use for this course.', 'saw-lms' ),
			),

			'_saw_lms_certificate_expiry_enabled' => array(
				'label'          => __( 'Certificate Expiry', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Certificates expire after a period', 'saw-lms' ),
			),

			'_saw_lms_certificate_expiry_months' => array(
				'label'       => __( 'Expiry Period (Months)', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 12,
				'min'         => 1,
				'description' => __( 'Certificate validity period in months.', 'saw-lms' ),
			),

			'_saw_lms_certificate_require_passing' => array(
				'label'          => __( 'Require Passing Score', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Student must achieve passing score to receive certificate', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 8. DRIP CONTENT
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'drip_content' => array(
		'title'    => __( 'Drip Content', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_drip_enabled' => array(
				'label'          => __( 'Enable Drip Content', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Unlock lessons gradually over time', 'saw-lms' ),
			),

			'_saw_lms_drip_type' => array(
				'label'   => __( 'Drip Type', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'immediate',
				'options' => array(
					'immediate'        => __( 'Immediate (All unlocked)', 'saw-lms' ),
					'enrollment_based' => __( 'Enrollment-based (Days after enrollment)', 'saw-lms' ),
					'specific_date'    => __( 'Specific Date (Fixed schedule)', 'saw-lms' ),
				),
			),

			'_saw_lms_drip_interval_days' => array(
				'label'       => __( 'Unlock Interval (Days)', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Days between unlocking each lesson.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 9. RECURRING COURSES
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'recurring_courses' => array(
		'title'    => __( 'Recurring Courses', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'low',
		'fields'   => array(

			'_saw_lms_is_recurring' => array(
				'label'          => __( 'Recurring Course', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'This course repeats on a schedule', 'saw-lms' ),
			),

			'_saw_lms_recurrence_type' => array(
				'label'   => __( 'Recurrence Type', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'monthly',
				'options' => array(
					'daily'   => __( 'Daily', 'saw-lms' ),
					'weekly'  => __( 'Weekly', 'saw-lms' ),
					'monthly' => __( 'Monthly', 'saw-lms' ),
					'yearly'  => __( 'Yearly', 'saw-lms' ),
					'custom'  => __( 'Custom Interval', 'saw-lms' ),
				),
			),

			'_saw_lms_recurrence_interval' => array(
				'label'       => __( 'Recurrence Interval', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 6,
				'min'         => 1,
				'description' => __( 'Number of months between recurrences (for custom type).', 'saw-lms' ),
			),

			'_saw_lms_recurrence_end_type' => array(
				'label'   => __( 'End Type', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'never',
				'options' => array(
					'never'          => __( 'Never (Indefinite)', 'saw-lms' ),
					'after_x_times'  => __( 'After X Occurrences', 'saw-lms' ),
					'on_date'        => __( 'On Specific Date', 'saw-lms' ),
				),
			),

			'_saw_lms_recurrence_count' => array(
				'label'       => __( 'Number of Occurrences', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'How many times course repeats (0 = unlimited).', 'saw-lms' ),
			),

			'_saw_lms_recurrence_end_date' => array(
				'label'       => __( 'End Date', 'saw-lms' ),
				'type'        => 'date',
				'default'     => '',
				'description' => __( 'Last date for course recurrence.', 'saw-lms' ),
			),

			'_saw_lms_grace_period_days' => array(
				'label'       => __( 'Grace Period (Days)', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Days of grace period before access expires.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 10. CONTENT & MATERIALS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'content_materials' => array(
		'title'    => __( 'Content & Materials', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'low',
		'fields'   => array(

			'_saw_lms_course_materials' => array(
				'label'       => __( 'Course Materials', 'saw-lms' ),
				'type'        => 'textarea',
				'rows'        => 10,
				'default'     => '',
				'placeholder' => __( 'List course materials, recommended readings, required software, etc.', 'saw-lms' ),
				'description' => __( 'Additional materials students need for the course.', 'saw-lms' ),
			),

			'_saw_lms_downloadable_resources' => array(
				'label'       => __( 'Downloadable Resources', 'saw-lms' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => __( 'File IDs or URLs (comma-separated)', 'saw-lms' ),
				'description' => __( 'Placeholder for file uploader integration.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 11. DISPLAY SETTINGS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'display_settings' => array(
		'title'    => __( 'Display Settings', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'low',
		'fields'   => array(

			'_saw_lms_content_visibility' => array(
				'label'   => __( 'Content Visibility', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'visible',
				'options' => array(
					'visible'             => __( 'Visible to All', 'saw-lms' ),
					'hidden_non_enrolled' => __( 'Hidden from Non-enrolled Users', 'saw-lms' ),
				),
			),

			'_saw_lms_pagination_lessons' => array(
				'label'       => __( 'Lessons per Page', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 10,
				'min'         => 1,
				'description' => __( 'Number of lessons to show per page in course curriculum.', 'saw-lms' ),
			),

			'_saw_lms_pagination_topics' => array(
				'label'       => __( 'Topics per Page', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 10,
				'min'         => 1,
				'description' => __( 'Number of topics to show per page.', 'saw-lms' ),
			),

			'_saw_lms_grid_short_description' => array(
				'label'       => __( 'Grid Short Description', 'saw-lms' ),
				'type'        => 'textarea',
				'rows'        => 3,
				'default'     => '',
				'placeholder' => __( 'Brief description for course grid/card view...', 'saw-lms' ),
				'description' => __( 'Short description shown in course grid view.', 'saw-lms' ),
			),

			'_saw_lms_grid_show_duration' => array(
				'label'          => __( 'Show Duration in Grid', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Display course duration in grid view', 'saw-lms' ),
			),

			'_saw_lms_grid_ribbon_text' => array(
				'label'       => __( 'Grid Ribbon Text', 'saw-lms' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => 'NEW',
				'description' => __( 'Text for ribbon badge (e.g., "NEW", "POPULAR", "FEATURED").', 'saw-lms' ),
			),

			'_saw_lms_grid_ribbon_color' => array(
				'label'       => __( 'Ribbon Color', 'saw-lms' ),
				'type'        => 'text',
				'default'     => '#ff0000',
				'placeholder' => '#ff0000',
				'description' => __( 'Hex color code for ribbon badge.', 'saw-lms' ),
			),

			'_saw_lms_promo_video_url' => array(
				'label'       => __( 'Promo Video URL', 'saw-lms' ),
				'type'        => 'url',
				'default'     => '',
				'placeholder' => 'https://youtube.com/watch?v=...',
				'description' => __( 'YouTube, Vimeo, or direct video URL.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 12. INTERACTIONS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'interactions' => array(
		'title'    => __( 'Interactions', 'saw-lms' ),
		'context'  => 'side',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_discussion_enabled' => array(
				'label'          => __( 'Discussion Forum', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Enable discussion forum for this course', 'saw-lms' ),
			),

			'_saw_lms_qa_enabled' => array(
				'label'          => __( 'Q&A Section', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Enable Q&A section', 'saw-lms' ),
			),

			'_saw_lms_peer_review_enabled' => array(
				'label'          => __( 'Peer Review', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Enable peer review for assignments', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 13. NOTIFICATIONS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'notifications' => array(
		'title'    => __( 'Notifications', 'saw-lms' ),
		'context'  => 'side',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_email_enrollment' => array(
				'label'          => __( 'Enrollment Email', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Send email on enrollment', 'saw-lms' ),
			),

			'_saw_lms_email_completion' => array(
				'label'          => __( 'Completion Email', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Send email on course completion', 'saw-lms' ),
			),

			'_saw_lms_email_certificate' => array(
				'label'          => __( 'Certificate Email', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Send certificate via email', 'saw-lms' ),
			),

			'_saw_lms_email_quiz_failed' => array(
				'label'          => __( 'Quiz Failed Email', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Send email when quiz is failed', 'saw-lms' ),
			),

			'_saw_lms_email_drip_unlock' => array(
				'label'          => __( 'Drip Unlock Email', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Send email when new lesson unlocks', 'saw-lms' ),
			),

			'_saw_lms_email_deadline_reminder' => array(
				'label'          => __( 'Deadline Reminder', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Send reminder before enrollment deadline', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 14. ADVANCED
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'advanced' => array(
		'title'    => __( 'Advanced', 'saw-lms' ),
		'context'  => 'side',
		'priority' => 'low',
		'fields'   => array(

			'_saw_lms_instructors' => array(
				'label'       => __( 'Instructors', 'saw-lms' ),
				'type'        => 'post_select',
				'post_type'   => 'user',
				'multiple'    => true,
				'default'     => array(),
				'description' => __( 'Primary instructors for this course.', 'saw-lms' ),
			),

			'_saw_lms_co_instructors' => array(
				'label'       => __( 'Co-instructors', 'saw-lms' ),
				'type'        => 'post_select',
				'post_type'   => 'user',
				'multiple'    => true,
				'default'     => array(),
				'description' => __( 'Assistant instructors.', 'saw-lms' ),
			),

			'_saw_lms_language' => array(
				'label'   => __( 'Language', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'cs',
				'options' => array(
					'cs' => __( 'Czech', 'saw-lms' ),
					'en' => __( 'English', 'saw-lms' ),
					'sk' => __( 'Slovak', 'saw-lms' ),
				),
			),

			'_saw_lms_age_restriction' => array(
				'label'       => __( 'Age Restriction', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Minimum age required (0 = no restriction).', 'saw-lms' ),
			),

			'_saw_lms_is_archived' => array(
				'label'          => __( 'Archived', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Archive this course (hidden from listings)', 'saw-lms' ),
			),

			'_saw_lms_version' => array(
				'label'       => __( 'Course Version', 'saw-lms' ),
				'type'        => 'text',
				'default'     => '1.0',
				'placeholder' => '1.0',
				'description' => __( 'Track course version for updates.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 15. SEO
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'seo' => array(
		'title'    => __( 'SEO', 'saw-lms' ),
		'context'  => 'side',
		'priority' => 'low',
		'fields'   => array(

			'_saw_lms_seo_title' => array(
				'label'       => __( 'SEO Title', 'saw-lms' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => __( 'Custom title for search engines', 'saw-lms' ),
				'description' => __( 'Override default title for SEO.', 'saw-lms' ),
			),

			'_saw_lms_seo_description' => array(
				'label'       => __( 'SEO Description', 'saw-lms' ),
				'type'        => 'textarea',
				'rows'        => 3,
				'default'     => '',
				'placeholder' => __( 'Meta description for search engines...', 'saw-lms' ),
				'description' => __( 'Brief description for search results.', 'saw-lms' ),
			),

			'_saw_lms_featured_order' => array(
				'label'       => __( 'Featured Order', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Display order in featured courses (0 = not featured).', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 16. STATISTICS (READ-ONLY)
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'statistics' => array(
		'title'    => __( 'Statistics', 'saw-lms' ),
		'context'  => 'side',
		'priority' => 'low',
		'fields'   => array(

			'_saw_lms_total_enrollments' => array(
				'label'    => __( 'Total Enrollments', 'saw-lms' ),
				'type'     => 'readonly',
				'default'  => '0',
				'readonly' => true,
			),

			'_saw_lms_active_enrollments' => array(
				'label'    => __( 'Active Students', 'saw-lms' ),
				'type'     => 'readonly',
				'default'  => '0',
				'readonly' => true,
			),

			'_saw_lms_completed_enrollments' => array(
				'label'    => __( 'Completions', 'saw-lms' ),
				'type'     => 'readonly',
				'default'  => '0',
				'readonly' => true,
			),

			'_saw_lms_avg_score' => array(
				'label'    => __( 'Average Score', 'saw-lms' ),
				'type'     => 'readonly',
				'default'  => '—',
				'readonly' => true,
			),

			'_saw_lms_pass_rate' => array(
				'label'    => __( 'Pass Rate', 'saw-lms' ),
				'type'     => 'readonly',
				'default'  => '—',
				'readonly' => true,
			),

		),
	),

);