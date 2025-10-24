<?php
/**
 * Section Fields Configuration
 *
 * Configuration for Section custom post type meta boxes and fields.
 * Extracted from existing class-section.php implementation.
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
	 * 1. SECTION SETTINGS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'section_settings' => array(
		'title'    => __( 'Section Settings', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'high',
		'fields'   => array(

			'_saw_lms_course_id' => array(
				'label'       => __( 'Parent Course', 'saw-lms' ),
				'type'        => 'post_select',
				'post_type'   => 'saw_course',
				'multiple'    => false,
				'default'     => '',
				'required'    => true,
				'description' => __( 'Select the course this section belongs to.', 'saw-lms' ),
			),

			'_saw_lms_section_order' => array(
				'label'       => __( 'Section Order', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
				'description' => __( 'Display order within the course (0 = first).', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 2. OPTIONAL CONTENT
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'section_content' => array(
		'title'    => __( 'Section Optional Content', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_section_video_url' => array(
				'label'       => __( 'Intro Video URL', 'saw-lms' ),
				'type'        => 'url',
				'default'     => '',
				'placeholder' => 'https://www.youtube.com/watch?v=...',
				'description' => __( 'Optional: URL to an introductory video for this section (YouTube, Vimeo, or direct link).', 'saw-lms' ),
			),

			'_saw_section_documents' => array(
				'label'       => __( 'Section Materials', 'saw-lms' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Optional: Upload PDF documents, presentations, or other materials for this section. (Placeholder for media uploader)', 'saw-lms' ),
			),

		),
	),

);