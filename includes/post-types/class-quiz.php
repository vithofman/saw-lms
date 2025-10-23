<?php
/**
 * Quiz Custom Post Type
 *
 * Handles registration and functionality for the Quiz CPT.
 * REFACTORED in v3.0.0: Config-based meta boxes using quiz-fields.php
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       2.1.0
 * @version     3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Quiz Class
 *
 * Manages the Quiz custom post type including registration,
 * meta boxes, admin columns, and quiz-specific functionality.
 *
 * UPDATED in v3.0.0: Refactored to use config-based meta boxes.
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
	 * Fields configuration
	 *
	 * Loaded from includes/config/quiz-fields.php
	 *
	 * @since 3.0.0
	 * @var array
	 */
	private $fields_config = array();

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
	 * Register hooks for the Quiz CPT.
	 *
	 * UPDATED in v3.0.0: Load fields config.
	 *
	 * @since 2.1.0
	 */
	private function __construct() {
		// Load fields configuration.
		$config_file = SAW_LMS_PLUGIN_DIR . 'includes/config/quiz-fields.php';
		if ( file_exists( $config_file ) ) {
			$this->fields_config = include $config_file;
		}

		// Register post type.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

		// Admin columns.
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );

		// Admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register Quiz post type
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
			'description'         => __( 'SAW LMS Quizzes', 'saw-lms' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'saw-lms',
			'menu_position'       => 28,
			'menu_icon'           => 'dashicons-forms',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => array( 'saw_quiz', 'saw_quizzes' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'quizzes',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Add meta boxes
	 *
	 * REFACTORED in v3.0.0: Automatically creates meta boxes from config.
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function add_meta_boxes() {
		// Loop through config and register each meta box.
		foreach ( $this->fields_config as $box_id => $box_config ) {
			add_meta_box(
				'saw_lms_' . $box_id,
				$box_config['title'],
				array( $this, 'render_meta_box' ),
				self::POST_TYPE,
				isset( $box_config['context'] ) ? $box_config['context'] : 'normal',
				isset( $box_config['priority'] ) ? $box_config['priority'] : 'default',
				array(
					'box_id' => $box_id,
					'fields' => $box_config['fields'],
				)
			);
		}
	}

	/**
	 * Render meta box
	 *
	 * Universal rendering method using Meta Box Helper.
	 *
	 * @since 3.0.0
	 * @param WP_Post $post    Current post object.
	 * @param array   $metabox Meta box arguments.
	 * @return void
	 */
	public function render_meta_box( $post, $metabox ) {
		$fields = $metabox['args']['fields'];

		// Nonce for security.
		wp_nonce_field( 'saw_lms_quiz_meta', 'saw_lms_quiz_nonce' );

		echo '<div class="saw-lms-meta-box">';

		// Render each field using helper.
		foreach ( $fields as $key => $field ) {
			$value = SAW_LMS_Meta_Box_Helper::get_field_value( $post->ID, $key, $field );
			SAW_LMS_Meta_Box_Helper::render_field( $key, $field, $value );
		}

		echo '</div>';
	}

	/**
	 * Save meta box data
	 *
	 * REFACTORED in v3.0.0: Universal save method with sanitization.
	 *
	 * @since 2.1.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Security checks.
		if ( ! isset( $_POST['saw_lms_quiz_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_quiz_nonce'] ) ), 'saw_lms_quiz_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Loop through all fields from config.
		foreach ( $this->fields_config as $box_config ) {
			foreach ( $box_config['fields'] as $key => $field ) {

				// Skip readonly fields.
				if ( ! empty( $field['readonly'] ) ) {
					continue;
				}

				// Handle field value.
				if ( isset( $_POST[ $key ] ) ) {
					// Sanitize value based on field type.
					$value = SAW_LMS_Meta_Box_Helper::sanitize_value(
						wp_unslash( $_POST[ $key ] ),
						$field['type']
					);

					update_post_meta( $post_id, $key, $value );
				} else {
					// Checkbox unchecked = empty string.
					if ( 'checkbox' === $field['type'] ) {
						update_post_meta( $post_id, $key, '' );
					}
				}
			}
		}

		/**
		 * Fires after quiz meta is saved.
		 *
		 * @since 2.1.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		do_action( 'saw_lms_quiz_meta_saved', $post_id, $post );
	}

	/**
	 * Add admin columns
	 *
	 * @since 2.1.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_admin_columns( $columns ) {
		// Remove date temporarily.
		$date = $columns['date'];
		unset( $columns['date'] );

		// Add custom columns.
		$columns['course']   = __( 'Course', 'saw-lms' );
		$columns['passing']  = __( 'Passing %', 'saw-lms' );
		$columns['attempts'] = __( 'Max Attempts', 'saw-lms' );

		// Re-add date.
		$columns['date'] = $date;

		return $columns;
	}

	/**
	 * Render admin column content
	 *
	 * @since 2.1.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'course':
				$course_id = get_post_meta( $post_id, '_saw_lms_course_id', true );
				if ( $course_id ) {
					$course = get_post( $course_id );
					if ( $course ) {
						$edit_link = get_edit_post_link( $course_id );
						echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $course->post_title ) . '</a>';
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;

			case 'passing':
				$passing = get_post_meta( $post_id, '_saw_lms_passing_score_percent', true );
				echo esc_html( $passing ) . '%';
				break;

			case 'attempts':
				$attempts = get_post_meta( $post_id, '_saw_lms_max_attempts', true );
				if ( ! empty( $attempts ) ) {
					echo esc_html( $attempts );
				} else {
					echo '∞'; // Unlimited.
				}
				break;
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @since 2.1.0
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['passing'] = 'passing';
		return $columns;
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 2.1.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only on quiz edit screen.
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Enqueue admin styles.
		wp_enqueue_style(
			'saw-lms-quiz-admin',
			SAW_LMS_PLUGIN_URL . 'assets/css/admin/quiz.css',
			array(),
			SAW_LMS_VERSION
		);

		// Enqueue admin scripts.
		wp_enqueue_script(
			'saw-lms-quiz-admin',
			SAW_LMS_PLUGIN_URL . 'assets/js/admin/quiz.js',
			array( 'jquery' ),
			SAW_LMS_VERSION,
			true
		);
	}

	/**
	 * Get quiz by ID (helper method)
	 *
	 * @since 2.1.0
	 * @param int $quiz_id Quiz post ID.
	 * @return WP_Post|null Quiz post object or null.
	 */
	public static function get_quiz( $quiz_id ) {
		$quiz = get_post( $quiz_id );

		if ( ! $quiz || self::POST_TYPE !== $quiz->post_type ) {
			return null;
		}

		return $quiz;
	}

	/**
	 * Check if quiz exists
	 *
	 * @since 2.1.0
	 * @param int $quiz_id Quiz post ID.
	 * @return bool True if quiz exists, false otherwise.
	 */
	public static function quiz_exists( $quiz_id ) {
		return null !== self::get_quiz( $quiz_id );
	}
}