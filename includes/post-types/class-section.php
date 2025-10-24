<?php
/**
 * Section Custom Post Type
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       2.1.0
 * @version     2.1.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Section class
 *
 * Manages the Section custom post type.
 *
 * @since 2.1.0
 */
class SAW_LMS_Section {

	/**
	 * Post type slug
	 *
	 * @var string
	 */
	const POST_TYPE = 'saw_section';

	/**
	 * Singleton instance
	 *
	 * @var SAW_LMS_Section|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return SAW_LMS_Section
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 2.1.0
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Register section post type
	 *
	 * @since 2.1.0
	 * @return void
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
			'show_in_menu'        => false, // We'll add to custom menu
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Add meta boxes
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'saw_section_details',
			__( 'Section Details', 'saw-lms' ),
			array( $this, 'render_details_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render section details meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_details_meta_box( $post ) {
		wp_nonce_field( 'saw_section_details', 'saw_section_details_nonce' );

		$course_id = get_post_meta( $post->ID, '_saw_lms_course_id', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="saw_lms_course_id"><?php esc_html_e( 'Course', 'saw-lms' ); ?></label></th>
				<td>
					<?php
					wp_dropdown_pages(
						array(
							'post_type'        => 'saw_course',
							'selected'         => $course_id,
							'name'             => 'saw_lms_course_id',
							'id'               => 'saw_lms_course_id',
							'show_option_none' => __( 'Select Course', 'saw-lms' ),
						)
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save meta boxes
	 *
	 * @since 2.1.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {
		if ( ! isset( $_POST['saw_section_details_nonce'] ) || ! wp_verify_nonce( $_POST['saw_section_details_nonce'], 'saw_section_details' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['saw_lms_course_id'] ) ) {
			update_post_meta( $post_id, '_saw_lms_course_id', absint( $_POST['saw_lms_course_id'] ) );
		}
	}
}