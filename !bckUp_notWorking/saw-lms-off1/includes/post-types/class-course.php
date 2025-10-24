<?php
/**
 * Course Custom Post Type
 *
 * Handles registration and functionality for the Course CPT.
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
 * SAW_LMS_Course Class
 *
 * Manages the Course custom post type including registration,
 * meta boxes, admin columns, and course-specific functionality.
 *
 * @since 2.1.0
 */
class SAW_LMS_Course {

	/**
	 * Post type slug
	 *
	 * @var string
	 */
	const POST_TYPE = 'saw_course';

	/**
	 * Singleton instance
	 *
	 * @var SAW_LMS_Course|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return SAW_LMS_Course
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
	 * Register hooks for the Course CPT.
	 *
	 * @since 2.1.0
	 */
	private function __construct() {
		// Register post type
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Register taxonomies
		add_action( 'init', array( $this, 'register_taxonomies' ) );

		// Meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Register Course post type
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Courses', 'Post Type General Name', 'saw-lms' ),
			'singular_name'         => _x( 'Course', 'Post Type Singular Name', 'saw-lms' ),
			'menu_name'             => __( 'Courses', 'saw-lms' ),
			'name_admin_bar'        => __( 'Course', 'saw-lms' ),
			'archives'              => __( 'Course Archives', 'saw-lms' ),
			'attributes'            => __( 'Course Attributes', 'saw-lms' ),
			'parent_item_colon'     => __( 'Parent Course:', 'saw-lms' ),
			'all_items'             => __( 'All Courses', 'saw-lms' ),
			'add_new_item'          => __( 'Add New Course', 'saw-lms' ),
			'add_new'               => __( 'Add New', 'saw-lms' ),
			'new_item'              => __( 'New Course', 'saw-lms' ),
			'edit_item'             => __( 'Edit Course', 'saw-lms' ),
			'update_item'           => __( 'Update Course', 'saw-lms' ),
			'view_item'             => __( 'View Course', 'saw-lms' ),
			'view_items'            => __( 'View Courses', 'saw-lms' ),
			'search_items'          => __( 'Search Course', 'saw-lms' ),
			'not_found'             => __( 'Not found', 'saw-lms' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'saw-lms' ),
			'featured_image'        => __( 'Course Image', 'saw-lms' ),
			'set_featured_image'    => __( 'Set course image', 'saw-lms' ),
			'remove_featured_image' => __( 'Remove course image', 'saw-lms' ),
			'use_featured_image'    => __( 'Use as course image', 'saw-lms' ),
			'insert_into_item'      => __( 'Insert into course', 'saw-lms' ),
			'uploaded_to_this_item' => __( 'Uploaded to this course', 'saw-lms' ),
			'items_list'            => __( 'Courses list', 'saw-lms' ),
			'items_list_navigation' => __( 'Courses list navigation', 'saw-lms' ),
			'filter_items_list'     => __( 'Filter courses list', 'saw-lms' ),
		);

		$args = array(
			'label'               => __( 'Course', 'saw-lms' ),
			'description'         => __( 'SAW LMS Courses', 'saw-lms' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false, // We'll add to custom menu
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-book-alt',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rewrite'             => array( 'slug' => 'courses' ),
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register course taxonomies
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function register_taxonomies() {
		// Course Category
		$category_labels = array(
			'name'              => _x( 'Course Categories', 'taxonomy general name', 'saw-lms' ),
			'singular_name'     => _x( 'Course Category', 'taxonomy singular name', 'saw-lms' ),
			'search_items'      => __( 'Search Course Categories', 'saw-lms' ),
			'all_items'         => __( 'All Course Categories', 'saw-lms' ),
			'parent_item'       => __( 'Parent Course Category', 'saw-lms' ),
			'parent_item_colon' => __( 'Parent Course Category:', 'saw-lms' ),
			'edit_item'         => __( 'Edit Course Category', 'saw-lms' ),
			'update_item'       => __( 'Update Course Category', 'saw-lms' ),
			'add_new_item'      => __( 'Add New Course Category', 'saw-lms' ),
			'new_item_name'     => __( 'New Course Category Name', 'saw-lms' ),
			'menu_name'         => __( 'Categories', 'saw-lms' ),
		);

		$category_args = array(
			'labels'            => $category_labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'course-category' ),
		);

		register_taxonomy( 'saw_course_category', array( self::POST_TYPE ), $category_args );

		// Course Tag
		$tag_labels = array(
			'name'                       => _x( 'Course Tags', 'taxonomy general name', 'saw-lms' ),
			'singular_name'              => _x( 'Course Tag', 'taxonomy singular name', 'saw-lms' ),
			'search_items'               => __( 'Search Course Tags', 'saw-lms' ),
			'popular_items'              => __( 'Popular Course Tags', 'saw-lms' ),
			'all_items'                  => __( 'All Course Tags', 'saw-lms' ),
			'edit_item'                  => __( 'Edit Course Tag', 'saw-lms' ),
			'update_item'                => __( 'Update Course Tag', 'saw-lms' ),
			'add_new_item'               => __( 'Add New Course Tag', 'saw-lms' ),
			'new_item_name'              => __( 'New Course Tag Name', 'saw-lms' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'saw-lms' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'saw-lms' ),
			'choose_from_most_used'      => __( 'Choose from most used tags', 'saw-lms' ),
			'menu_name'                  => __( 'Tags', 'saw-lms' ),
		);

		$tag_args = array(
			'labels'            => $tag_labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'course-tag' ),
		);

		register_taxonomy( 'saw_course_tag', array( self::POST_TYPE ), $tag_args );
	}

	/**
	 * Add meta boxes
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'saw_course_settings',
			__( 'Course Settings', 'saw-lms' ),
			array( $this, 'render_settings_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render Course Settings meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_settings_meta_box( $post ) {
		wp_nonce_field( 'saw_course_settings', 'saw_course_settings_nonce' );

		$duration        = get_post_meta( $post->ID, '_saw_lms_duration', true );
		$pass_percentage = get_post_meta( $post->ID, '_saw_lms_pass_percentage', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="saw_lms_duration"><?php esc_html_e( 'Duration (minutes)', 'saw-lms' ); ?></label></th>
				<td>
					<input type="number" id="saw_lms_duration" name="saw_lms_duration" value="<?php echo esc_attr( $duration ); ?>" class="regular-text" min="0" />
				</td>
			</tr>
			<tr>
				<th><label for="saw_lms_pass_percentage"><?php esc_html_e( 'Pass Percentage', 'saw-lms' ); ?></label></th>
				<td>
					<input type="number" id="saw_lms_pass_percentage" name="saw_lms_pass_percentage" value="<?php echo esc_attr( $pass_percentage ? $pass_percentage : 70 ); ?>" class="regular-text" min="0" max="100" />
					<p class="description"><?php esc_html_e( 'Minimum percentage required to pass the course.', 'saw-lms' ); ?></p>
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
		// Verify nonce
		if ( ! isset( $_POST['saw_course_settings_nonce'] ) || ! wp_verify_nonce( $_POST['saw_course_settings_nonce'], 'saw_course_settings' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save duration
		if ( isset( $_POST['saw_lms_duration'] ) ) {
			update_post_meta( $post_id, '_saw_lms_duration', absint( $_POST['saw_lms_duration'] ) );
		}

		// Save pass percentage
		if ( isset( $_POST['saw_lms_pass_percentage'] ) ) {
			$percentage = absint( $_POST['saw_lms_pass_percentage'] );
			if ( $percentage > 100 ) {
				$percentage = 100;
			}
			update_post_meta( $post_id, '_saw_lms_pass_percentage', $percentage );
		}
	}
}
}