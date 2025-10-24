<?php
/**
 * Course Content Tab Fields Configuration
 *
 * Defines fields for the "Content" tab in Course meta box.
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
	'_saw_lms_course_description' => array(
		'type'        => 'textarea',
		'label'       => __( 'Course Description', 'saw-lms' ),
		'description' => __( 'Brief description of the course content.', 'saw-lms' ),
		'placeholder' => __( 'Enter course description...', 'saw-lms' ),
		'default'     => '',
	),
);