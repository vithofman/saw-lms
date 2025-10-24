<?php
/**
 * Course Builder Tab Fields Configuration
 *
 * Defines fields for the "Builder" tab in Course meta box.
 * This is a placeholder for future drag-and-drop course builder.
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
	'_saw_lms_builder_placeholder' => array(
		'type'        => 'readonly',
		'label'       => __( 'Course Builder', 'saw-lms' ),
		'description' => __( 'The visual course builder is coming soon. For now, use the Gutenberg editor above to add course content.', 'saw-lms' ),
		'default'     => __( 'Coming soon...', 'saw-lms' ),
	),
);