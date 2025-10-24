<?php
/**
 * Course Post Type
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Course class
 *
 * Registers and manages the Course custom post type.
 *
 * @since 1.0.0
 */
class SAW_LMS_Course {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_saw_lms_course', array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Register course post type
	 *
	 * @since 1.0.0
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
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-welcome-learn-more',
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

		register_post_type( 'saw_lms_course', $args );
	}

	/**
	 * Register course taxonomies
	 *
	 * @since 1.0.0
	 */
	public function register_taxonomies() {
		// Course Category.
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

		register_taxonomy( 'saw_lms_course_category', array( 'saw_lms_course' ), $category_args );

		// Course Tag.
		$tag_labels = array(
			'name'                       => _x( 'Course Tags', 'taxonomy general name', 'saw-lms' ),
			'singular_name'              => _x( 'Course Tag', 'taxonomy singular name', 'saw-lms' ),
			'search_items'               => __( 'Search Course Tags', 'saw-lms' ),
			'popular_items'              => __( 'Popular Course Tags', 'saw-lms' ),
			'all_items'                  => __( 'All Course Tags', 'saw-lms' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Course Tag', 'saw-lms' ),
			'update_item'                => __( 'Update Course Tag', 'saw-lms' ),
			'add_new_item'               => __( 'Add New Course Tag', 'saw-lms' ),
			'new_item_name'              => __( 'New Course Tag Name', 'saw-lms' ),
			'separate_items_with_commas' => __( 'Separate course tags with commas', 'saw-lms' ),
			'add_or_remove_items'        => __( 'Add or remove course tags', 'saw-lms' ),
			'choose_from_most_used'      => __( 'Choose from the most used course tags', 'saw-lms' ),
			'not_found'                  => __( 'No course tags found.', 'saw-lms' ),
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

		register_taxonomy( 'saw_lms_course_tag', array( 'saw_lms_course' ), $tag_args );
	}

	/**
	 * Add meta boxes
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'saw_lms_course_details',
			__( 'Course Details', 'saw-lms' ),
			array( $this, 'render_course_details_meta_box' ),
			'saw_lms_course',
			'normal',
			'high'
		);
	}

	/**
	 * Render course details meta box
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_course_details_meta_box( $post ) {
		wp_nonce_field( 'saw_lms_course_details_nonce', 'saw_lms_course_details_nonce' );

		$level    = get_post_meta( $post->ID, '_saw_lms_course_level', true );
		$duration = get_post_meta( $post->ID, '_saw_lms_course_duration', true );
		$price    = get_post_meta( $post->ID, '_saw_lms_course_price', true );

		?>
		<div class="saw-lms-meta-box">
			<p>
				<label for="saw_lms_course_level"><?php esc_html_e( 'Course Level:', 'saw-lms' ); ?></label>
				<select id="saw_lms_course_level" name="saw_lms_course_level" class="form-select">
					<option value="beginner" <?php selected( $level, 'beginner' ); ?>><?php esc_html_e( 'Beginner', 'saw-lms' ); ?></option>
					<option value="intermediate" <?php selected( $level, 'intermediate' ); ?>><?php esc_html_e( 'Intermediate', 'saw-lms' ); ?></option>
					<option value="advanced" <?php selected( $level, 'advanced' ); ?>><?php esc_html_e( 'Advanced', 'saw-lms' ); ?></option>
				</select>
			</p>

			<p>
				<label for="saw_lms_course_duration"><?php esc_html_e( 'Course Duration (hours):', 'saw-lms' ); ?></label>
				<input type="number" id="saw_lms_course_duration" name="saw_lms_course_duration" class="form-input" value="<?php echo esc_attr( $duration ); ?>" min="0" step="0.5">
			</p>

			<p>
				<label for="saw_lms_course_price"><?php esc_html_e( 'Course Price:', 'saw-lms' ); ?></label>
				<input type="number" id="saw_lms_course_price" name="saw_lms_course_price" class="form-input" value="<?php echo esc_attr( $price ); ?>" min="0" step="0.01">
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta boxes
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		if ( ! isset( $_POST['saw_lms_course_details_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['saw_lms_course_details_nonce'], 'saw_lms_course_details_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['saw_lms_course_level'] ) ) {
			update_post_meta( $post_id, '_saw_lms_course_level', sanitize_text_field( $_POST['saw_lms_course_level'] ) );
		}

		if ( isset( $_POST['saw_lms_course_duration'] ) ) {
			update_post_meta( $post_id, '_saw_lms_course_duration', absint( $_POST['saw_lms_course_duration'] ) );
		}

		if ( isset( $_POST['saw_lms_course_price'] ) ) {
			update_post_meta( $post_id, '_saw_lms_course_price', floatval( $_POST['saw_lms_course_price'] ) );
		}

		$course_model = SAW_LMS_Model_Loader::get_course_model();
		$course_model->get_course( $post_id, true );
	}
}