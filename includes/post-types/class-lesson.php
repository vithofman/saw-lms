<?php
/**
 * Lesson Custom Post Type
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
 * SAW_LMS_Lesson class
 *
 * Manages the Lesson custom post type.
 *
 * @since 2.1.0
 */
class SAW_LMS_Lesson {

	/**
	 * Post type slug
	 *
	 * @var string
	 */
	const POST_TYPE = 'saw_lesson';

	/**
	 * Singleton instance
	 *
	 * @var SAW_LMS_Lesson|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return SAW_LMS_Lesson
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
	 * Register lesson post type
	 *
	 * @since 2.1.0
	 * @return void
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
			'saw_lesson_details',
			__( 'Lesson Details', 'saw-lms' ),
			array( $this, 'render_details_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render lesson details meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_details_meta_box( $post ) {
		wp_nonce_field( 'saw_lesson_details', 'saw_lesson_details_nonce' );

		$section_id   = get_post_meta( $post->ID, '_saw_lms_section_id', true );
		$lesson_type  = get_post_meta( $post->ID, '_saw_lms_lesson_type', true );
		$video_url    = get_post_meta( $post->ID, '_saw_lms_video_url', true );
		$duration     = get_post_meta( $post->ID, '_saw_lms_duration', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="saw_lms_section_id"><?php esc_html_e( 'Section', 'saw-lms' ); ?></label></th>
				<td>
					<?php
					wp_dropdown_pages(
						array(
							'post_type'        => 'saw_section',
							'selected'         => $section_id,
							'name'             => 'saw_lms_section_id',
							'id'               => 'saw_lms_section_id',
							'show_option_none' => __( 'Select Section', 'saw-lms' ),
						)
					);
					?>
				</td>
			</tr>
			<tr>
				<th><label for="saw_lms_lesson_type"><?php esc_html_e( 'Lesson Type', 'saw-lms' ); ?></label></th>
				<td>
					<select name="saw_lms_lesson_type" id="saw_lms_lesson_type">
						<option value="video" <?php selected( $lesson_type, 'video' ); ?>><?php esc_html_e( 'Video', 'saw-lms' ); ?></option>
						<option value="text" <?php selected( $lesson_type, 'text' ); ?>><?php esc_html_e( 'Text/Article', 'saw-lms' ); ?></option>
						<option value="document" <?php selected( $lesson_type, 'document' ); ?>><?php esc_html_e( 'Document/PDF', 'saw-lms' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="saw-lesson-video-field" style="<?php echo ( 'video' !== $lesson_type ) ? 'display:none;' : ''; ?>">
				<th><label for="saw_lms_video_url"><?php esc_html_e( 'Video URL', 'saw-lms' ); ?></label></th>
				<td>
					<input type="url" id="saw_lms_video_url" name="saw_lms_video_url" value="<?php echo esc_attr( $video_url ); ?>" class="large-text" />
					<p class="description"><?php esc_html_e( 'YouTube, Vimeo, or direct video URL', 'saw-lms' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="saw_lms_duration"><?php esc_html_e( 'Duration (minutes)', 'saw-lms' ); ?></label></th>
				<td>
					<input type="number" id="saw_lms_duration" name="saw_lms_duration" value="<?php echo esc_attr( $duration ); ?>" class="regular-text" min="0" />
				</td>
			</tr>
		</table>

		<script>
		jQuery(document).ready(function($) {
			$('#saw_lms_lesson_type').on('change', function() {
				if ($(this).val() === 'video') {
					$('.saw-lesson-video-field').show();
				} else {
					$('.saw-lesson-video-field').hide();
				}
			});
		});
		</script>
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
		if ( ! isset( $_POST['saw_lesson_details_nonce'] ) || ! wp_verify_nonce( $_POST['saw_lesson_details_nonce'], 'saw_lesson_details' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['saw_lms_section_id'] ) ) {
			update_post_meta( $post_id, '_saw_lms_section_id', absint( $_POST['saw_lms_section_id'] ) );
		}

		if ( isset( $_POST['saw_lms_lesson_type'] ) ) {
			update_post_meta( $post_id, '_saw_lms_lesson_type', sanitize_text_field( $_POST['saw_lms_lesson_type'] ) );
		}

		if ( isset( $_POST['saw_lms_video_url'] ) ) {
			update_post_meta( $post_id, '_saw_lms_video_url', esc_url_raw( $_POST['saw_lms_video_url'] ) );
		}

		if ( isset( $_POST['saw_lms_duration'] ) ) {
			update_post_meta( $post_id, '_saw_lms_duration', absint( $_POST['saw_lms_duration'] ) );
		}
	}
}