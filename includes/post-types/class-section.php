<?php
/**
 * Section Post Type
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Section class
 *
 * Registers and manages the Section custom post type.
 *
 * @since 1.0.0
 */
class SAW_LMS_Section {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'save_post_saw_lms_section', array( $this, 'invalidate_cache' ), 10, 2 );
	}

	/**
	 * Register section post type
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Sections', 'Post Type General Name', 'saw-lms' ),
			'singular_name'         => _x( 'Section', 'Post Type Singular Name', 'saw-lms' ),
			'menu_name'             => __( 'Sections', 'saw-lms' ),
			'name_admin_bar'        => __( 'Section', 'saw-lms' ),
			'archives'              => __( 'Section Archives', 'saw-lms' ),
			'attributes'            => __( 'Section Attributes', 'saw-lms' ),
			'parent_item_colon'     => __( 'Parent Section:', 'saw-lms' ),
			'all_items'             => __( 'All Sections', 'saw-lms' ),
			'add_new_item'          => __( 'Add New Section', 'saw-lms' ),
			'add_new'               => __( 'Add New', 'saw-lms' ),
			'new_item'              => __( 'New Section', 'saw-lms' ),
			'edit_item'             => __( 'Edit Section', 'saw-lms' ),
			'update_item'           => __( 'Update Section', 'saw-lms' ),
			'view_item'             => __( 'View Section', 'saw-lms' ),
			'view_items'            => __( 'View Sections', 'saw-lms' ),
			'search_items'          => __( 'Search Section', 'saw-lms' ),
			'not_found'             => __( 'Not found', 'saw-lms' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'saw-lms' ),
		);

		$args = array(
			'label'               => __( 'Section', 'saw-lms' ),
			'description'         => __( 'Course Sections', 'saw-lms' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=saw_lms_course',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
		);

		register_post_type( 'saw_lms_section', $args );
	}

	/**
	 * Invalidate cache on save
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function invalidate_cache( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$section_model = SAW_LMS_Model_Loader::get_section_model();
		$section_model->get_section( $post_id, true );
	}
}