<?php
/**
 * Course Custom Post Type
 *
 * Handles registration and functionality for the Course CPT.
 * REFACTORED in v3.1.0: Tabbed meta boxes using config files.
 * REFACTORED in v3.1.1: Difficulty as meta field instead of taxonomy.
 * FIXED in v3.1.2: Added debugging for tabs configuration.
 * FIXED in v3.1.4: Removed enqueue_admin_assets() - now handled centrally by class-admin-assets.php
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       2.1.0
 * @version     3.1.4
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
 * UPDATED in v3.1.1: Difficulty is now a meta field, not taxonomy.
 * FIXED in v3.1.2: Added debugging and validation for tabs config.
 * FIXED in v3.1.4: Removed duplicate asset enqueueing.
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
	 * UPDATED in v3.1.4: Removed enqueue hook.
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

		// Meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

		// Admin columns.
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );

		// NOTE: Admin assets are now handled centrally by class-admin-assets.php (v3.1.4).
	}

	/**
	 * Load tabs configuration
	 *
	 * Loads field configurations from config files.
	 *
	 * FIXED in v3.1.2: Added validation and error logging.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	private function load_tabs_config() {
		$config_dir = SAW_LMS_PLUGIN_DIR . 'includes/post-types/configs/';

		// Initialize tabs array.
		$this->tabs_config = array();

		// Load Settings tab.
		$settings_file = $config_dir . 'course-settings-fields.php';
		if ( file_exists( $settings_file ) ) {
			$settings_fields = include $settings_file;
			if ( is_array( $settings_fields ) && ! empty( $settings_fields ) ) {
				$this->tabs_config['settings'] = array(
					'label'  => __( 'Settings', 'saw-lms' ),
					'fields' => $settings_fields,
				);
			} else {
				// Log error if WP_DEBUG is enabled.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'SAW LMS: course-settings-fields.php returned invalid or empty array' );
				}
			}
		} else {
			// Log error if WP_DEBUG is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SAW LMS: course-settings-fields.php not found at ' . $settings_file );
			}
		}

		// Load Builder tab.
		$builder_file = $config_dir . 'course-builder-fields.php';
		if ( file_exists( $builder_file ) ) {
			$builder_fields = include $builder_file;
			if ( is_array( $builder_fields ) && ! empty( $builder_fields ) ) {
				$this->tabs_config['builder'] = array(
					'label'  => __( 'Builder', 'saw-lms' ),
					'fields' => $builder_fields,
				);
			} else {
				// Log error if WP_DEBUG is enabled.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'SAW LMS: course-builder-fields.php returned invalid or empty array' );
				}
			}
		} else {
			// Log error if WP_DEBUG is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SAW LMS: course-builder-fields.php not found at ' . $builder_file );
			}
		}

		// Load Stats tab.
		$stats_file = $config_dir . 'course-stats-fields.php';
		if ( file_exists( $stats_file ) ) {
			$stats_fields = include $stats_file;
			if ( is_array( $stats_fields ) && ! empty( $stats_fields ) ) {
				$this->tabs_config['stats'] = array(
					'label'  => __( 'Stats', 'saw-lms' ),
					'fields' => $stats_fields,
				);
			} else {
				// Log error if WP_DEBUG is enabled.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'SAW LMS: course-stats-fields.php returned invalid or empty array' );
				}
			}
		} else {
			// Log error if WP_DEBUG is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SAW LMS: course-stats-fields.php not found at ' . $stats_file );
			}
		}

		// Final validation - if no tabs loaded, show admin notice.
		if ( empty( $this->tabs_config ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'SAW LMS CRITICAL: No tabs configuration loaded for Course CPT!' );
		}
	}

	/**
	 * Register Course post type
	 *
	 * UPDATED in v3.1.4: Changed show_in_menu to false (menu managed by class-admin-menu.php).
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
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false, // Menu handled by class-admin-menu.php.
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => array( 'saw_course', 'saw_courses' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'courses',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register taxonomies
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
	}

	/**
	 * Render tabbed meta box
	 *
	 * UPDATED in v3.1.0: Uses Meta Box Helper for tabs.
	 * FIXED in v3.1.2: Added debugging output when no tabs are loaded.
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

		// Check if enrollments table exists.
		$table_name = $wpdb->prefix . 'saw_lms_enrollments';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		if ( ! $table_exists ) {
			// Table doesn't exist yet - set defaults.
			update_post_meta( $post_id, '_saw_lms_total_enrollments', 0 );
			update_post_meta( $post_id, '_saw_lms_active_enrollments', 0 );
			update_post_meta( $post_id, '_saw_lms_completions', 0 );
			update_post_meta( $post_id, '_saw_lms_completion_rate', '0%' );
			return;
		}

		// Get enrollment counts.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_enrollments = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE course_id = %d",
				$post_id
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active_enrollments = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE course_id = %d AND status = 'active'",
				$post_id
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
	 * Save meta boxes
	 *
	 * REFACTORED in v3.1.0: Universal save method using tabs config.
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

		// Loop through all tabs and fields.
		foreach ( $this->tabs_config as $tab_config ) {
			if ( empty( $tab_config['fields'] ) ) {
				continue;
			}

			foreach ( $tab_config['fields'] as $key => $field ) {
				// Skip readonly fields.
				if ( ! empty( $field['readonly'] ) ) {
					continue;
				}

				// Skip heading fields (they're not actual inputs).
				if ( isset( $field['type'] ) && 'heading' === $field['type'] ) {
					continue;
				}

				// Handle field value.
				if ( isset( $_POST[ $key ] ) ) {
					// Sanitize value based on field type.
					$value = SAW_LMS_Meta_Box_Helper::sanitize_value(
						wp_unslash( $_POST[ $key ] ),
						isset( $field['type'] ) ? $field['type'] : 'text'
					);

					update_post_meta( $post_id, $key, $value );
				} else {
					// Checkbox unchecked = empty string.
					if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
						update_post_meta( $post_id, $key, '' );
					}
				}
			}
		}

		// Invalidate course cache.
		wp_cache_delete( 'course_data_' . $post_id, 'saw_lms_courses' );

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
		// Remove date temporarily.
		$date = $columns['date'];
		unset( $columns['date'] );

		// Add custom columns.
		$columns['thumbnail']  = __( 'Image', 'saw-lms' );
		$columns['difficulty'] = __( 'Difficulty', 'saw-lms' );
		$columns['duration']   = __( 'Duration', 'saw-lms' );
		$columns['students']   = __( 'Students', 'saw-lms' );

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
			case 'thumbnail':
				$thumbnail = get_the_post_thumbnail( $post_id, array( 50, 50 ) );
				echo $thumbnail ? $thumbnail : '—';
				break;

			case 'difficulty':
				$difficulty = get_post_meta( $post_id, '_saw_lms_difficulty', true );
				if ( ! empty( $difficulty ) ) {
					echo '<span class="saw-lms-difficulty saw-lms-difficulty-' . esc_attr( $difficulty ) . '">';
					echo esc_html( ucfirst( $difficulty ) );
					echo '</span>';
				} else {
					echo '—';
				}
				break;

			case 'duration':
				$duration = get_post_meta( $post_id, '_saw_lms_duration_minutes', true );
				if ( ! empty( $duration ) ) {
					$hours   = floor( $duration / 60 );
					$minutes = $duration % 60;
					if ( $hours > 0 ) {
						/* translators: 1: hours, 2: minutes */
						printf( esc_html__( '%1$dh %2$dm', 'saw-lms' ), (int) $hours, (int) $minutes );
					} else {
						/* translators: %s: minutes */
						printf( esc_html__( '%s min', 'saw-lms' ), (int) $minutes );
					}
				} else {
					echo '—';
				}
				break;

			case 'students':
				$students = get_post_meta( $post_id, '_saw_lms_total_enrollments', true );
				echo esc_html( $students ? $students : '0' );
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
		$columns['duration'] = 'duration';
		$columns['students'] = 'students';
		return $columns;
	}

	/**
	 * Get course by ID (helper method)
	 *
	 * @since 2.1.0
	 * @param int $course_id Course post ID.
	 * @return WP_Post|null Course post object or null.
	 */
	public static function get_course( $course_id ) {
		$course = get_post( $course_id );

		if ( ! $course || self::POST_TYPE !== $course->post_type ) {
			return null;
		}

		return $course;
	}

	/**
	 * Check if course exists
	 *
	 * @since 2.1.0
	 * @param int $course_id Course post ID.
	 * @return bool True if course exists, false otherwise.
	 */
	public static function course_exists( $course_id ) {
		return null !== self::get_course( $course_id );
	}
}