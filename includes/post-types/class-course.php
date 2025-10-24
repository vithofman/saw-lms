<?php
/**
 * Course Custom Post Type
 *
 * Handles registration and functionality for the Course CPT.
 * REFACTORED in v3.1.0: Tabbed meta boxes using config files.
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       2.1.0
 * @version     3.1.0
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
 * UPDATED in v3.1.0: Refactored to use tabbed meta boxes.
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
	 * Tabs configuration
	 *
	 * Loaded from config files in includes/post-types/configs/
	 *
	 * @since 3.1.0
	 * @var array
	 */
	private $tabs_config = array();

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
	 * UPDATED in v3.1.0: Load tabs config.
	 *
	 * @since 2.1.0
	 */
	private function __construct() {
		// Load tabs configuration.
		$this->load_tabs_config();

		// Register post type.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Register taxonomies.
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_default_difficulty_terms' ), 20 );

		// Meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

		// Admin columns.
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );

		// Admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Load tabs configuration
	 *
	 * Loads field configurations from config files.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	private function load_tabs_config() {
		$config_dir = SAW_LMS_PLUGIN_DIR . 'includes/post-types/configs/';

		$this->tabs_config = array(
			'content' => array(
				'label'  => __( 'Content', 'saw-lms' ),
				'fields' => file_exists( $config_dir . 'course-content-fields.php' )
					? include $config_dir . 'course-content-fields.php'
					: array(),
			),
			'settings' => array(
				'label'  => __( 'Settings', 'saw-lms' ),
				'fields' => file_exists( $config_dir . 'course-settings-fields.php' )
					? include $config_dir . 'course-settings-fields.php'
					: array(),
			),
			'stats' => array(
				'label'  => __( 'Stats', 'saw-lms' ),
				'fields' => file_exists( $config_dir . 'course-stats-fields.php' )
					? include $config_dir . 'course-stats-fields.php'
					: array(),
			),
		);
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
		);

		$args = array(
			'label'               => __( 'Course', 'saw-lms' ),
			'description'         => __( 'SAW LMS Courses', 'saw-lms' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'hierarchical'        => true,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'saw-lms',
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-book',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => array( 'saw_course', 'saw_courses' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'courses',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register Course taxonomies
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function register_taxonomies() {
		// Course Category.
		$category_labels = array(
			'name'              => _x( 'Course Categories', 'taxonomy general name', 'saw-lms' ),
			'singular_name'     => _x( 'Course Category', 'taxonomy singular name', 'saw-lms' ),
			'search_items'      => __( 'Search Categories', 'saw-lms' ),
			'all_items'         => __( 'All Categories', 'saw-lms' ),
			'parent_item'       => __( 'Parent Category', 'saw-lms' ),
			'parent_item_colon' => __( 'Parent Category:', 'saw-lms' ),
			'edit_item'         => __( 'Edit Category', 'saw-lms' ),
			'update_item'       => __( 'Update Category', 'saw-lms' ),
			'add_new_item'      => __( 'Add New Category', 'saw-lms' ),
			'new_item_name'     => __( 'New Category Name', 'saw-lms' ),
			'menu_name'         => __( 'Categories', 'saw-lms' ),
		);

		register_taxonomy(
			'saw_course_category',
			self::POST_TYPE,
			array(
				'hierarchical'      => true,
				'labels'            => $category_labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'course-category' ),
				'show_in_rest'      => true,
			)
		);

		// Course Difficulty.
		$difficulty_labels = array(
			'name'              => _x( 'Course Difficulties', 'taxonomy general name', 'saw-lms' ),
			'singular_name'     => _x( 'Difficulty', 'taxonomy singular name', 'saw-lms' ),
			'search_items'      => __( 'Search Difficulties', 'saw-lms' ),
			'all_items'         => __( 'All Difficulties', 'saw-lms' ),
			'edit_item'         => __( 'Edit Difficulty', 'saw-lms' ),
			'update_item'       => __( 'Update Difficulty', 'saw-lms' ),
			'add_new_item'      => __( 'Add New Difficulty', 'saw-lms' ),
			'new_item_name'     => __( 'New Difficulty Name', 'saw-lms' ),
			'menu_name'         => __( 'Difficulty', 'saw-lms' ),
		);

		register_taxonomy(
			'saw_course_difficulty',
			self::POST_TYPE,
			array(
				'hierarchical'      => false,
				'labels'            => $difficulty_labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'difficulty' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register default difficulty terms
	 *
	 * Creates default difficulty levels if they don't exist.
	 *
	 * @since 2.1.1
	 * @return void
	 */
	public function register_default_difficulty_terms() {
		$default_terms = array(
			'beginner'     => __( 'Beginner', 'saw-lms' ),
			'intermediate' => __( 'Intermediate', 'saw-lms' ),
			'advanced'     => __( 'Advanced', 'saw-lms' ),
			'expert'       => __( 'Expert', 'saw-lms' ),
		);

		foreach ( $default_terms as $slug => $name ) {
			if ( ! term_exists( $slug, 'saw_course_difficulty' ) ) {
				wp_insert_term(
					$name,
					'saw_course_difficulty',
					array(
						'slug' => $slug,
					)
				);
			}
		}
	}

	/**
	 * Add meta boxes
	 *
	 * REFACTORED in v3.1.0: Single tabbed meta box.
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function add_meta_boxes() {
		// Main tabbed meta box.
		add_meta_box(
			'saw_lms_course_details',
			__( 'Course Details', 'saw-lms' ),
			array( $this, 'render_tabbed_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Custom Difficulty meta box (replaces default taxonomy meta box).
		add_meta_box(
			'saw_lms_course_difficulty',
			__( 'Course Difficulty', 'saw-lms' ),
			array( $this, 'render_difficulty_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render tabbed meta box
	 *
	 * UPDATED in v3.1.0: Uses Meta Box Helper for tabs.
	 *
	 * @since 3.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_tabbed_meta_box( $post ) {
		// Nonce for security.
		wp_nonce_field( 'saw_lms_course_meta', 'saw_lms_course_nonce' );

		// Calculate stats for Stats tab.
		$this->calculate_course_stats( $post->ID );

		// Render tabs.
		SAW_LMS_Meta_Box_Helper::render_tabbed_meta_box( $post->ID, $this->tabs_config );
	}

	/**
	 * Calculate course statistics
	 *
	 * Updates readonly stats fields with real-time data.
	 *
	 * @since 3.1.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function calculate_course_stats( $post_id ) {
		global $wpdb;

		// Get enrollment counts.
		$total_enrollments = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE course_id = %d",
				$post_id
			)
		);

		$active_enrollments = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE course_id = %d AND status = 'active'",
				$post_id
			)
		);

		$completions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE course_id = %d AND status = 'completed'",
				$post_id
			)
		);

		// Calculate completion rate.
		$completion_rate = $total_enrollments > 0
			? round( ( $completions / $total_enrollments ) * 100, 2 ) . '%'
			: '0%';

		// Update meta (for display purposes only).
		update_post_meta( $post_id, '_saw_lms_total_enrollments', $total_enrollments );
		update_post_meta( $post_id, '_saw_lms_active_enrollments', $active_enrollments );
		update_post_meta( $post_id, '_saw_lms_completions', $completions );
		update_post_meta( $post_id, '_saw_lms_completion_rate', $completion_rate );
	}

	/**
	 * Render Course Difficulty meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_difficulty_meta_box( $post ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'saw_course_difficulty',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$current = wp_get_object_terms( $post->ID, 'saw_course_difficulty', array( 'fields' => 'ids' ) );
			$current = ! empty( $current ) ? $current[0] : 0;

			echo '<div class="saw-difficulty-select">';
			foreach ( $terms as $term ) {
				printf(
					'<label class="form-radio"><input type="radio" name="saw_course_difficulty" value="%d"%s> <span>%s</span></label><br>',
					esc_attr( $term->term_id ),
					checked( $current, $term->term_id, false ),
					esc_html( $term->name )
				);
			}
			echo '</div>';
		}
	}

	/**
	 * Save meta box data
	 *
	 * REFACTORED in v3.1.0: Universal save method with sanitization.
	 *
	 * @since 2.1.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Security checks.
		if ( ! isset( $_POST['saw_lms_course_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_course_nonce'] ) ), 'saw_lms_course_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Loop through all tabs and save fields.
		foreach ( $this->tabs_config as $tab_id => $tab_config ) {
			// Skip Stats tab (read-only).
			if ( 'stats' === $tab_id ) {
				continue;
			}

			foreach ( $tab_config['fields'] as $key => $field ) {
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

		// Save difficulty taxonomy.
		if ( isset( $_POST['saw_course_difficulty'] ) ) {
			$term_id = absint( $_POST['saw_course_difficulty'] );
			wp_set_object_terms( $post_id, $term_id, 'saw_course_difficulty', false );
		}

		// Invalidate course cache.
		wp_cache_delete( 'course_' . $post_id, 'saw_lms_courses' );

		/**
		 * Fires after course meta is saved.
		 *
		 * @since 2.1.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		do_action( 'saw_lms_course_meta_saved', $post_id, $post );
	}

	/**
	 * Add admin columns
	 *
	 * @since 2.1.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_admin_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' === $key ) {
				$new_columns['saw_difficulty']   = __( 'Difficulty', 'saw-lms' );
				$new_columns['saw_duration']     = __( 'Duration', 'saw-lms' );
				$new_columns['saw_enrollments']  = __( 'Enrollments', 'saw-lms' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render admin columns
	 *
	 * @since 2.1.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'saw_difficulty':
				$terms = get_the_terms( $post_id, 'saw_course_difficulty' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					echo esc_html( $terms[0]->name );
				} else {
					echo '—';
				}
				break;

			case 'saw_duration':
				$duration = get_post_meta( $post_id, '_saw_lms_duration', true );
				echo $duration ? esc_html( $duration . ' hours' ) : '—';
				break;

			case 'saw_enrollments':
				$enrollments = get_post_meta( $post_id, '_saw_lms_total_enrollments', true );
				echo $enrollments ? esc_html( $enrollments ) : '0';
				break;
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @since 2.1.0
	 * @param array $columns Sortable columns.
	 * @return array Modified columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['saw_duration']    = 'saw_duration';
		$columns['saw_enrollments'] = 'saw_enrollments';

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
		global $post_type;

		if ( self::POST_TYPE !== $post_type ) {
			return;
		}

		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			wp_enqueue_style(
				'saw-lms-admin-components',
				SAW_LMS_PLUGIN_URL . 'assets/css/admin/components.css',
				array(),
				SAW_LMS_VERSION
			);

			wp_enqueue_script(
				'saw-lms-admin-utilities',
				SAW_LMS_PLUGIN_URL . 'assets/js/admin/utilities.js',
				array( 'jquery' ),
				SAW_LMS_VERSION,
				true
			);
		}
	}
}