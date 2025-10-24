<?php
/**
 * Course Settings Tab Fields Configuration
 *
 * Defines fields for the "Settings" tab in Course meta box.
 * UPDATED v3.1.0: Added difficulty field from sidebar.
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types/Configs
 * @since       3.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'_saw_lms_duration' => array(
		'type'        => 'number',
		'label'       => __( 'Duration (hours)', 'saw-lms' ),
		'description' => __( 'Estimated time to complete the course.', 'saw-lms' ),
		'placeholder' => '0',
		'default'     => '',
	),

	'_saw_lms_pass_percentage' => array(
		'type'        => 'number',
		'label'       => __( 'Pass Percentage', 'saw-lms' ),
		'description' => __( 'Minimum percentage required to pass the course.', 'saw-lms' ),
		'placeholder' => '70',
		'default'     => 70,
	),

	'_saw_lms_certificate_enable' => array(
		'type'        => 'checkbox',
		'label'       => __( 'Enable Certificate', 'saw-lms' ),
		'description' => __( 'Award a certificate upon course completion.', 'saw-lms' ),
		'default'     => '',
	),

	'_saw_lms_points' => array(
		'type'        => 'number',
		'label'       => __( 'Points', 'saw-lms' ),
		'description' => __( 'Points awarded for completing this course.', 'saw-lms' ),
		'placeholder' => '0',
		'default'     => 0,
	),

	'_saw_lms_repeatable' => array(
		'type'        => 'checkbox',
		'label'       => __( 'Repeatable', 'saw-lms' ),
		'description' => __( 'Allow users to retake this course.', 'saw-lms' ),
		'default'     => '',
	),

	'_saw_lms_repeat_period' => array(
		'type'        => 'number',
		'label'       => __( 'Repeat Period (days)', 'saw-lms' ),
		'description' => __( 'Number of days before the course can be retaken (0 = no limit).', 'saw-lms' ),
		'placeholder' => '0',
		'default'     => 0,
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
);