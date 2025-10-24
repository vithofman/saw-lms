<?php
/**
 * Quiz Custom Post Type
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
 * SAW_LMS_Quiz class
 *
 * Manages the Quiz custom post type.
 *
 * @since 2.1.0
 */
class SAW_LMS_Quiz {

	/**
	 * Post type slug
	 *
	 * @var string
	 */
	const POST_TYPE = 'saw_quiz';

	/**
	 * Singleton instance
	 *
	 * @var SAW_LMS_Quiz|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return SAW_LMS_Quiz
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
	 * Register quiz post type
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Quizzes', 'Post Type General Name', 'saw-lms' ),
			'singular_name'         => _x( 'Quiz', 'Post Type Singular Name', 'saw-lms' ),
			'menu_name'             => __( 'Quizzes', 'saw-lms' ),
			'name_admin_bar'        => __( 'Quiz', 'saw-lms' ),
			'archives'              => __( 'Quiz Archives', 'saw-lms' ),
			'attributes'            => __( 'Quiz Attributes', 'saw-lms' ),
			'parent_item_colon'     => __( 'Parent Quiz:', 'saw-lms' ),
			'all_items'             => __( 'All Quizzes', 'saw-lms' ),
			'add_new_item'          => __( 'Add New Quiz', 'saw-lms' ),
			'add_new'               => __( 'Add New', 'saw-lms' ),
			'new_item'              => __( 'New Quiz', 'saw-lms' ),
			'edit_item'             => __( 'Edit Quiz', 'saw-lms' ),
			'update_item'           => __( 'Update Quiz', 'saw-lms' ),
			'view_item'             => __( 'View Quiz', 'saw-lms' ),
			'view_items'            => __( 'View Quizzes', 'saw-lms' ),
			'search_items'          => __( 'Search Quiz', 'saw-lms' ),
			'not_found'             => __( 'Not found', 'saw-lms' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'saw-lms' ),
		);

		$args = array(
			'label'               => __( 'Quiz', 'saw-lms' ),
			'description'         => __( 'Course Quizzes', 'saw-lms' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor' ),
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
			'saw_quiz_settings',
			__( 'Quiz Settings', 'saw-lms' ),
			array( $this, 'render_settings_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render quiz settings meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_settings_meta_box( $post ) {
		wp_nonce_field( 'saw_quiz_settings', 'saw_quiz_settings_nonce' );

		$section_id      = get_post_meta( $post->ID, '_saw_lms_section_id', true );
		$pass_percentage = get_post_meta( $post->ID, '_saw_lms_pass_percentage', true );
		$time_limit      = get_post_meta( $post->ID, '_saw_lms_time_limit', true );
		$max_attempts    = get_post_meta( $post->ID, '_saw_lms_max_attempts', true );
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
				<th><label for="saw_lms_pass_percentage"><?php esc_html_e( 'Pass Percentage', 'saw-lms' ); ?></label></th>
				<td>
					<input type="number" id="saw_lms_pass_percentage" name="saw_lms_pass_percentage" value="<?php echo esc_attr( $pass_percentage ? $pass_percentage : 70 ); ?>" class="regular-text" min="0" max="100" />
					<p class="description"><?php esc_html_e( 'Minimum percentage required to pass the quiz.', 'saw-lms' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="saw_lms_time_limit"><?php esc_html_e( 'Time Limit (minutes)', 'saw-lms' ); ?></label></th>
				<td>
					<input type="number" id="saw_lms_time_limit" name="saw_lms_time_limit" value="<?php echo esc_attr( $time_limit ); ?>" class="regular-text" min="0" />
					<p class="description"><?php esc_html_e( 'Leave empty for no time limit.', 'saw-lms' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="saw_lms_max_attempts"><?php esc_html_e( 'Maximum Attempts', 'saw-lms' ); ?></label></th>
				<td>
					<input type="number" id="saw_lms_max_attempts" name="saw_lms_max_attempts" value="<?php echo esc_attr( $max_attempts ? $max_attempts : 3 ); ?>" class="regular-text" min="1" />
					<p class="description"><?php esc_html_e( 'Maximum number of attempts allowed.', 'saw-lms' ); ?></p>
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
		if ( ! isset( $_POST['saw_quiz_settings_nonce'] ) || ! wp_verify_nonce( $_POST['saw_quiz_settings_nonce'], 'saw_quiz_settings' ) ) {
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

		if ( isset( $_POST['saw_lms_pass_percentage'] ) ) {
			$percentage = absint( $_POST['saw_lms_pass_percentage'] );
			if ( $percentage > 100 ) {
				$percentage = 100;
			}
			update_post_meta( $post_id, '_saw_lms_pass_percentage', $percentage );
		}

		if ( isset( $_POST['saw_lms_time_limit'] ) ) {
			update_post_meta( $post_id, '_saw_lms_time_limit', absint( $_POST['saw_lms_time_limit'] ) );
		}

		if ( isset( $_POST['saw_lms_max_attempts'] ) ) {
			$attempts = absint( $_POST['saw_lms_max_attempts'] );
			if ( $attempts < 1 ) {
				$attempts = 1;
			}
			update_post_meta( $post_id, '_saw_lms_max_attempts', $attempts );
		}
	}
}