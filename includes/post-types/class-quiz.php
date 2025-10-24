<?php
/**
 * Quiz Post Type
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Quiz class
 *
 * Registers and manages the Quiz custom post type.
 *
 * @since 1.0.0
 */
class SAW_LMS_Quiz {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_saw_lms_quiz', array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Register quiz post type
	 *
	 * @since 1.0.0
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

		register_post_type( 'saw_lms_quiz', $args );
	}

	/**
	 * Add meta boxes
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'saw_lms_quiz_settings',
			__( 'Quiz Settings', 'saw-lms' ),
			array( $this, 'render_quiz_settings_meta_box' ),
			'saw_lms_quiz',
			'normal',
			'high'
		);
	}

	/**
	 * Render quiz settings meta box
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_quiz_settings_meta_box( $post ) {
		wp_nonce_field( 'saw_lms_quiz_settings_nonce', 'saw_lms_quiz_settings_nonce' );

		$passing_grade       = get_post_meta( $post->ID, '_saw_lms_passing_grade', true );
		$time_limit          = get_post_meta( $post->ID, '_saw_lms_time_limit', true );
		$max_attempts        = get_post_meta( $post->ID, '_saw_lms_max_attempts', true );
		$randomize_questions = get_post_meta( $post->ID, '_saw_lms_randomize_questions', true );

		?>
		<div class="saw-lms-meta-box">
			<p>
				<label for="saw_lms_passing_grade"><?php esc_html_e( 'Passing Grade (%):', 'saw-lms' ); ?></label>
				<input type="number" id="saw_lms_passing_grade" name="saw_lms_passing_grade" class="form-input" value="<?php echo esc_attr( $passing_grade ? $passing_grade : 70 ); ?>" min="0" max="100">
			</p>

			<p>
				<label for="saw_lms_time_limit"><?php esc_html_e( 'Time Limit (minutes, 0 = no limit):', 'saw-lms' ); ?></label>
				<input type="number" id="saw_lms_time_limit" name="saw_lms_time_limit" class="form-input" value="<?php echo esc_attr( $time_limit ); ?>" min="0">
			</p>

			<p>
				<label for="saw_lms_max_attempts"><?php esc_html_e( 'Maximum Attempts (0 = unlimited):', 'saw-lms' ); ?></label>
				<input type="number" id="saw_lms_max_attempts" name="saw_lms_max_attempts" class="form-input" value="<?php echo esc_attr( $max_attempts ); ?>" min="0">
			</p>

			<p>
				<label class="form-checkbox">
					<input type="checkbox" id="saw_lms_randomize_questions" name="saw_lms_randomize_questions" value="1" <?php checked( $randomize_questions, '1' ); ?>>
					<span><?php esc_html_e( 'Randomize Question Order', 'saw-lms' ); ?></span>
				</label>
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
		if ( ! isset( $_POST['saw_lms_quiz_settings_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['saw_lms_quiz_settings_nonce'], 'saw_lms_quiz_settings_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['saw_lms_passing_grade'] ) ) {
			update_post_meta( $post_id, '_saw_lms_passing_grade', absint( $_POST['saw_lms_passing_grade'] ) );
		}

		if ( isset( $_POST['saw_lms_time_limit'] ) ) {
			update_post_meta( $post_id, '_saw_lms_time_limit', absint( $_POST['saw_lms_time_limit'] ) );
		}

		if ( isset( $_POST['saw_lms_max_attempts'] ) ) {
			update_post_meta( $post_id, '_saw_lms_max_attempts', absint( $_POST['saw_lms_max_attempts'] ) );
		}

		$randomize = isset( $_POST['saw_lms_randomize_questions'] ) ? '1' : '0';
		update_post_meta( $post_id, '_saw_lms_randomize_questions', $randomize );

		$quiz_model = SAW_LMS_Model_Loader::get_quiz_model();
		$quiz_model->get_quiz( $post_id, true );
	}
}