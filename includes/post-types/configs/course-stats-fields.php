<?php
/**
 * Course Stats Tab Fields Configuration
 *
 * Defines fields for the "Stats" tab in Course meta box.
 * These are read-only fields showing course statistics.
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
	'_saw_lms_total_enrollments' => array(
		'type'        => 'readonly',
		'label'       => __( 'Total Enrollments', 'saw-lms' ),
		'description' => __( 'Number of users enrolled in this course.', 'saw-lms' ),
		'default'     => '0',
	),

	'_saw_lms_active_enrollments' => array(
		'type'        => 'readonly',
		'label'       => __( 'Active Enrollments', 'saw-lms' ),
		'description' => __( 'Number of users currently taking this course.', 'saw-lms' ),
		'default'     => '0',
	),

	'_saw_lms_completions' => array(
		'type'        => 'readonly',
		'label'       => __( 'Completions', 'saw-lms' ),
		'description' => __( 'Number of users who completed this course.', 'saw-lms' ),
		'default'     => '0',
	),

	'_saw_lms_completion_rate' => array(
		'type'        => 'readonly',
		'label'       => __( 'Completion Rate', 'saw-lms' ),
		'description' => __( 'Percentage of enrolled users who completed the course.', 'saw-lms' ),
		'default'     => '0%',
	),
);