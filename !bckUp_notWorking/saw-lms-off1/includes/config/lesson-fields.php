<?php
/**
 * Lesson Fields Configuration
 *
 * Configuration for Lesson custom post type meta boxes and fields.
 * Extracted from existing class-lesson.php implementation.
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
	 * 1. LESSON SETTINGS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'lesson_settings' => array(
		'title'    => __( 'Lesson Settings', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'high',
		'fields'   => array(

			'_saw_lms_section_id' => array(
				'label'       => __( 'Parent Section', 'saw-lms' ),
				'type'        => 'post_select',
				'post_type'   => 'saw_section',
				'multiple'    => false,
				'default'     => '',
				'required'    => true,
				'description' => __( 'Select the section this lesson belongs to.', 'saw-lms' ),
			),

			'_saw_lms_lesson_type' => array(
				'label'   => __( 'Lesson Type', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'video',
				'options' => array(
					'video'      => __( 'Video', 'saw-lms' ),
					'text'       => __( 'Text/Article', 'saw-lms' ),
					'document'   => __( 'Document/PDF', 'saw-lms' ),
					'assignment' => __( 'Assignment', 'saw-lms' ),
				),
				'description' => __( 'Type of content in this lesson.', 'saw-lms' ),
			),

			'_saw_lms_lesson_order' => array(
				'label'       => __( 'Lesson Order', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
				'description' => __( 'Display order within the section (0 = first).', 'saw-lms' ),
			),

			'_saw_lms_duration' => array(
				'label'       => __( 'Duration (minutes)', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
				'description' => __( 'Estimated time to complete this lesson.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 2. VIDEO CONTENT
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'video_content' => array(
		'title'    => __( 'Video Content', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_video_source' => array(
				'label'   => __( 'Video Source', 'saw-lms' ),
				'type'    => 'select',
				'default' => 'youtube',
				'options' => array(
					'youtube' => __( 'YouTube', 'saw-lms' ),
					'vimeo'   => __( 'Vimeo', 'saw-lms' ),
					'embed'   => __( 'Embed Code', 'saw-lms' ),
				),
			),

			'_saw_lms_video_url' => array(
				'label'       => __( 'Video URL / Embed Code', 'saw-lms' ),
				'type'        => 'textarea',
				'rows'        => 4,
				'default'     => '',
				'placeholder' => 'https://www.youtube.com/watch?v=...',
				'description' => __( 'YouTube: https://www.youtube.com/watch?v=... | Vimeo: https://vimeo.com/... | Embed: Paste iframe code', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 3. DOCUMENT CONTENT
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'document_content' => array(
		'title'    => __( 'Document Content', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_document_url' => array(
				'label'       => __( 'Document', 'saw-lms' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Upload a PDF, Word, PowerPoint, or other document file.', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 4. ASSIGNMENT SETTINGS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'assignment_settings' => array(
		'title'    => __( 'Assignment Settings', 'saw-lms' ),
		'context'  => 'normal',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_assignment_max_points' => array(
				'label'       => __( 'Maximum Points', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 100,
				'min'         => 0,
				'step'        => 1,
				'description' => __( 'Maximum points this assignment is worth.', 'saw-lms' ),
			),

			'_saw_lms_assignment_passing_points' => array(
				'label'       => __( 'Passing Points', 'saw-lms' ),
				'type'        => 'number',
				'default'     => 70,
				'min'         => 0,
				'step'        => 1,
				'description' => __( 'Minimum points required to pass this assignment.', 'saw-lms' ),
			),

			'_saw_lms_assignment_allow_resubmit' => array(
				'label'          => __( 'Resubmissions', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Allow students to resubmit after grading', 'saw-lms' ),
			),

		),
	),

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * 5. PROGRESS REQUIREMENTS
	 * ═══════════════════════════════════════════════════════════════════════
	 */
	'progress_requirements' => array(
		'title'    => __( 'Progress Requirements', 'saw-lms' ),
		'context'  => 'side',
		'priority' => 'default',
		'fields'   => array(

			'_saw_lms_is_required' => array(
				'label'          => __( 'Required Lesson', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '1',
				'checkbox_label' => __( 'Students must complete this lesson to progress', 'saw-lms' ),
			),

			'_saw_lms_preview_enabled' => array(
				'label'          => __( 'Free Preview', 'saw-lms' ),
				'type'           => 'checkbox',
				'default'        => '',
				'checkbox_label' => __( 'Allow non-enrolled users to preview this lesson', 'saw-lms' ),
			),

		),
	),

);