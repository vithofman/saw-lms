<?php
/**
 * Quiz Fields Configuration
 *
 * Configuration for Quiz custom post type meta boxes and fields.
 * Extracted from existing class-quiz.php implementation.
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
	 * 1. QUIZ SETTINGS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'quiz_settings' => array(
		'title'    => __( 'Quiz Settings', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'high',
		'fields'   => array(

			'_saw_lms_passing_score_percent' => array(
				'label'       => __( 'Passing Score (%)', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 70,
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
				'required'    => true,
				'description' => __( 'Minimum percentage required to pass this quiz (0-100).', 'saw-lms' ),
			),

			'_saw_lms_time_limit_minutes' => array(
				'label'       => __( 'Time Limit (minutes)', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
				'description' => __( 'Maximum time allowed to complete the quiz (0 = no limit).', 'saw-lms' ),
			),

			'_saw_lms_max_attempts' => array(
				'label'       => __( 'Maximum Attempts', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
				'description' => __( 'Number of times a student can take this quiz (0 = unlimited).', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 2. QUIZ BEHAVIOR
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'quiz_behavior' => array(
		'title'    => __( 'Quiz Behavior', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_randomize_questions' => array(
				'label'          => __( 'Randomize Questions', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Display questions in random order for each student', 'saw-lms' ),
				'description'    => __( 'Helps prevent cheating by randomizing question order.', 'saw-lms' ),
			),

			'_saw_lms_randomize_answers' => array(
				'label'          => __( 'Randomize Answers', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Display answer choices in random order', 'saw-lms' ),
				'description'    => __( 'Randomizes the order of answer options within each question.', 'saw-lms' ),
			),

			'_saw_lms_show_correct_answers' => array(
				'label'   => __( 'Show Correct Answers', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'after_last_attempt',
				'options' => array(
					'immediately'         => __( 'Immediately after submission', 'saw-lms' ),
					'after_last_attempt'  => __( 'After last attempt only', 'saw-lms' ),
					'never'               => __( 'Never show correct answers', 'saw-lms' ),
				),
				'description' => __( 'When to display the correct answers to students.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 3. QUIZ ASSIGNMENT
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'quiz_assignment' => array(
		'title'    => __( 'Quiz Assignment', 'saw-lms' ),
		'context'  => 'side',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_course_id' => array(
				'label'       => __( 'Course', 'saw-lms' ),
				'type'        => 'post_select',
				'post_type'   => 'saw_course',
				'multiple'    => false,
				'default'     => '',
				'description' => __( 'Assign this quiz to a course (optional).', 'saw-lms' ),
			),

			'_saw_lms_section_id' => array(
				'label'       => __( 'Section', 'saw-lms' ),
				'type'        => 'post_select',
				'post_type'   => 'saw_section',
				'multiple'    => false,
				'default'     => '',
				'description' => __( 'Assign this quiz to a section (optional).', 'saw-lms' ),
			),

		),
	),

);