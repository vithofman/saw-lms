<?php
/**
 * Lesson Post Type
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Lesson class
 *
 * Registers and manages the Lesson custom post type.
 *
 * @since 1.0.0
 */
class SAW_LMS_Lesson {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_saw_lms_lesson', array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Register lesson post type
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Lessons', 'Post Type General Name', 'saw-lms' ),
			'singular_name'         => _x( 'Lesson', 'Post Type Singular Name', 'saw-lms' ),
			'menu_name'             => __( 'Lessons', 'saw-lms' ),
			'name_admin_bar'        => __( 'Lesson', 'saw-lms' ),
			'archives'              => __( 'Lesson Archives', 'saw-lms' ),
			'attributes'            => __( 'Lesson Attributes', 'saw-lms' ),
			'parent_item_colon'     => __( 'Parent Lesson:', 'saw-lms' ),
			'all_items'             => __( 'All Lessons', 'saw-lms' ),
			'add_new_item'          => __( 'Add New Lesson', 'saw-lms' ),
			'add_new'               => __( 'Add New', 'saw-lms' ),
			'new_item'              => __( 'New Lesson', 'saw-lms' ),
			'edit_item'             => __( 'Edit Lesson', 'saw-lms' ),
			'update_item'           => __( 'Update Lesson', 'saw-lms' ),
			'view_item'             => __( 'View Lesson', 'saw-lms' ),
			'view_items'            => __( 'View Lessons', 'saw-lms' ),
			'search_items'          => __( 'Search Lesson', 'saw-lms' ),
			'not_found'             => __( 'Not found', 'saw-lms' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'saw-lms' ),
		);

		$args = array(
			'label'               => __( 'Lesson', 'saw-lms' ),
			'description'         => __( 'Course Lessons', 'saw-lms' ),
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

		register_post_type( 'saw_lms_lesson', $args );
	}

	/**
	 * Add meta boxes
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'saw_lms_lesson_details',
			__( 'Lesson Details', 'saw-lms' ),
			array( $this, 'render_lesson_details_meta_box' ),
			'saw_lms_lesson',
			'normal',
			'high'
		);
	}

	/**
	 * Render lesson details meta box
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_lesson_details_meta_box( $post ) {
		wp_nonce_field( 'saw_lms_lesson_details_nonce', 'saw_lms_lesson_details_nonce' );

		$lesson_type  = get_post_meta( $post->ID, '_saw_lms_lesson_type', true );
		$video_url    = get_post_meta( $post->ID, '_saw_lms_video_url', true );
		$video_length = get_post_meta( $post->ID, '_saw_lms_video_length', true );

		?>
		<div class="saw-lms-meta-box">
			<p>
				<label for="saw_lms_lesson_type"><?php esc_html_e( 'Lesson Type:', 'saw-lms' ); ?></label>
				<select id="saw_lms_lesson_type" name="saw_lms_lesson_type" class="form-select">
					<option value="text" <?php selected( $lesson_type, 'text' ); ?>><?php esc_html_e( 'Text', 'saw-lms' ); ?></option>
					<option value="video" <?php selected( $lesson_type, 'video' ); ?>><?php esc_html_e( 'Video', 'saw-lms' ); ?></option>
				</select>
			</p>

			<div id="saw_lms_video_fields" style="<?php echo 'video' !== $lesson_type ? 'display:none;' : ''; ?>">
				<p>
					<label for="saw_lms_video_url"><?php esc_html_e( 'Video URL:', 'saw-lms' ); ?></label>
					<input type="url" id="saw_lms_video_url" name="saw_lms_video_url" class="form-input" value="<?php echo esc_url( $video_url ); ?>">
				</p>

				<p>
					<label for="saw_lms_video_length"><?php esc_html_e( 'Video Length (minutes):', 'saw-lms' ); ?></label>
					<input type="number" id="saw_lms_video_length" name="saw_lms_video_length" class="form-input" value="<?php echo esc_attr( $video_length ); ?>" min="0">
				</p>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#saw_lms_lesson_type').on('change', function() {
				if ($(this).val() === 'video') {
					$('#saw_lms_video_fields').show();
				} else {
					$('#saw_lms_video_fields').hide();
				}
			});
		});
		</script>
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
		if ( ! isset( $_POST['saw_lms_lesson_details_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['saw_lms_lesson_details_nonce'], 'saw_lms_lesson_details_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['saw_lms_lesson_type'] ) ) {
			update_post_meta( $post_id, '_saw_lms_lesson_type', sanitize_text_field( $_POST['saw_lms_lesson_type'] ) );
		}

		if ( isset( $_POST['saw_lms_video_url'] ) ) {
			update_post_meta( $post_id, '_saw_lms_video_url', esc_url_raw( $_POST['saw_lms_video_url'] ) );
		}

		if ( isset( $_POST['saw_lms_video_length'] ) ) {
			update_post_meta( $post_id, '_saw_lms_video_length', absint( $_POST['saw_lms_video_length'] ) );
		}

		$lesson_model = SAW_LMS_Model_Loader::get_lesson_model();
		$lesson_model->get_lesson( $post_id, true );
	}
}