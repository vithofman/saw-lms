<?php
/**
 * Course Custom Post Type
 *
 * Handles registration and functionality for the Course CPT.
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       2.1.0
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

		// Admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
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
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
			'taxonomies'          => array( 'saw_course_category', 'saw_course_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'saw-lms',
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-book-alt',
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
			'rewrite'             => array(
				'slug'       => 'courses',
				'with_front' => false,
			),
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
		// Course Categories.
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
			array( self::POST_TYPE ),
			array(
				'hierarchical'      => true,
				'labels'            => $category_labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'course-category' ),
			)
		);

		// Course Tags.
		$tag_labels = array(
			'name'                       => _x( 'Course Tags', 'taxonomy general name', 'saw-lms' ),
			'singular_name'              => _x( 'Course Tag', 'taxonomy singular name', 'saw-lms' ),
			'search_items'               => __( 'Search Tags', 'saw-lms' ),
			'popular_items'              => __( 'Popular Tags', 'saw-lms' ),
			'all_items'                  => __( 'All Tags', 'saw-lms' ),
			'edit_item'                  => __( 'Edit Tag', 'saw-lms' ),
			'update_item'                => __( 'Update Tag', 'saw-lms' ),
			'add_new_item'               => __( 'Add New Tag', 'saw-lms' ),
			'new_item_name'              => __( 'New Tag Name', 'saw-lms' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'saw-lms' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'saw-lms' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'saw-lms' ),
			'not_found'                  => __( 'No tags found.', 'saw-lms' ),
			'menu_name'                  => __( 'Tags', 'saw-lms' ),
		);

		register_taxonomy(
			'saw_course_tag',
			array( self::POST_TYPE ),
			array(
				'hierarchical'      => false,
				'labels'            => $tag_labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'course-tag' ),
			)
		);
	}

	/**
	 * Add meta boxes
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function add_meta_boxes() {
		// Course Settings meta box.
		add_meta_box(
			'saw_lms_course_settings',
			__( 'Course Settings', 'saw-lms' ),
			array( $this, 'render_settings_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Course Stats meta box (read-only).
		add_meta_box(
			'saw_lms_course_stats',
			__( 'Course Statistics', 'saw-lms' ),
			array( $this, 'render_stats_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
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
		// Nonce for security.
		wp_nonce_field( 'saw_lms_course_settings', 'saw_lms_course_settings_nonce' );

		// Get current values.
		$duration           = get_post_meta( $post->ID, '_saw_lms_duration', true );
		$difficulty         = get_post_meta( $post->ID, '_saw_lms_difficulty', true );
		$pass_percentage    = get_post_meta( $post->ID, '_saw_lms_pass_percentage', true );
		$certificate_enable = get_post_meta( $post->ID, '_saw_lms_certificate_enable', true );
		$points             = get_post_meta( $post->ID, '_saw_lms_points', true );
		$repeatable         = get_post_meta( $post->ID, '_saw_lms_repeatable', true );

		// Defaults.
		$duration           = ! empty( $duration ) ? $duration : '';
		$difficulty         = ! empty( $difficulty ) ? $difficulty : 'beginner';
		$pass_percentage    = ! empty( $pass_percentage ) ? $pass_percentage : 70;
		$certificate_enable = ! empty( $certificate_enable ) ? 1 : 0;
		$points             = ! empty( $points ) ? $points : 100;
		$repeatable         = ! empty( $repeatable ) ? 1 : 0;

		?>
		<div class="saw-lms-meta-box">
			<table class="form-table">
				<tbody>
					<!-- Duration -->
					<tr>
						<th scope="row">
							<label for="saw_lms_duration"><?php esc_html_e( 'Duration (hours)', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_duration" 
								name="saw_lms_duration" 
								value="<?php echo esc_attr( $duration ); ?>" 
								min="0" 
								step="0.5" 
								class="regular-text"
							/>
							<p class="description"><?php esc_html_e( 'Estimated time to complete this course.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Difficulty -->
					<tr>
						<th scope="row">
							<label for="saw_lms_difficulty"><?php esc_html_e( 'Difficulty Level', 'saw-lms' ); ?></label>
						</th>
						<td>
							<select id="saw_lms_difficulty" name="saw_lms_difficulty" class="regular-text">
								<option value="beginner" <?php selected( $difficulty, 'beginner' ); ?>><?php esc_html_e( 'Beginner', 'saw-lms' ); ?></option>
								<option value="intermediate" <?php selected( $difficulty, 'intermediate' ); ?>><?php esc_html_e( 'Intermediate', 'saw-lms' ); ?></option>
								<option value="advanced" <?php selected( $difficulty, 'advanced' ); ?>><?php esc_html_e( 'Advanced', 'saw-lms' ); ?></option>
								<option value="expert" <?php selected( $difficulty, 'expert' ); ?>><?php esc_html_e( 'Expert', 'saw-lms' ); ?></option>
							</select>
						</td>
					</tr>

					<!-- Pass Percentage -->
					<tr>
						<th scope="row">
							<label for="saw_lms_pass_percentage"><?php esc_html_e( 'Pass Percentage (%)', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_pass_percentage" 
								name="saw_lms_pass_percentage" 
								value="<?php echo esc_attr( $pass_percentage ); ?>" 
								min="0" 
								max="100" 
								step="1" 
								class="small-text"
							/>
							<p class="description"><?php esc_html_e( 'Minimum percentage required to pass this course.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Certificate Enable -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Certificate', 'saw-lms' ); ?>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									id="saw_lms_certificate_enable" 
									name="saw_lms_certificate_enable" 
									value="1" 
									<?php checked( $certificate_enable, 1 ); ?>
								/>
								<?php esc_html_e( 'Issue certificate upon course completion', 'saw-lms' ); ?>
							</label>
						</td>
					</tr>

					<!-- Gamification Points -->
					<tr>
						<th scope="row">
							<label for="saw_lms_points"><?php esc_html_e( 'Points on Completion', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_points" 
								name="saw_lms_points" 
								value="<?php echo esc_attr( $points ); ?>" 
								min="0" 
								step="1" 
								class="small-text"
							/>
							<p class="description"><?php esc_html_e( 'Gamification points awarded upon course completion.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Repeatable -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Repeatable', 'saw-lms' ); ?>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									id="saw_lms_repeatable" 
									name="saw_lms_repeatable" 
									value="1" 
									<?php checked( $repeatable, 1 ); ?>
								/>
								<?php esc_html_e( 'Allow users to retake this course after completion', 'saw-lms' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Course Stats meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_stats_meta_box( $post ) {
		// Get stats from database (cache-ready).
		$stats = $this->get_course_stats( $post->ID );

		?>
		<div class="saw-lms-stats">
			<p>
				<strong><?php esc_html_e( 'Total Enrollments:', 'saw-lms' ); ?></strong> 
				<span><?php echo absint( $stats['enrollments'] ); ?></span>
			</p>
			<p>
				<strong><?php esc_html_e( 'Completed:', 'saw-lms' ); ?></strong> 
				<span><?php echo absint( $stats['completed'] ); ?></span>
			</p>
			<p>
				<strong><?php esc_html_e( 'In Progress:', 'saw-lms' ); ?></strong> 
				<span><?php echo absint( $stats['in_progress'] ); ?></span>
			</p>
			<p>
				<strong><?php esc_html_e( 'Completion Rate:', 'saw-lms' ); ?></strong> 
				<span><?php echo esc_html( $stats['completion_rate'] ); ?>%</span>
			</p>
			<p>
				<strong><?php esc_html_e( 'Average Score:', 'saw-lms' ); ?></strong> 
				<span><?php echo esc_html( $stats['average_score'] ); ?>%</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Get course statistics (cache-ready)
	 *
	 * @since 2.1.0
	 * @param int $course_id Course post ID.
	 * @return array Course statistics.
	 */
	private function get_course_stats( $course_id ) {
		// Cache key.
		$cache_key = 'course_stats_' . $course_id;

		// Try cache first (will use Cache Manager when implemented).
		$stats = wp_cache_get( $cache_key, 'saw_lms_courses' );

		if ( false === $stats ) {
			global $wpdb;

			// Get enrollments.
			$enrollments = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE course_id = %d",
					$course_id
				)
			);

			// Get completed enrollments.
			$completed = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE course_id = %d AND status = 'completed'",
					$course_id
				)
			);

			// Get in progress enrollments.
			$in_progress = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE course_id = %d AND status = 'in-progress'",
					$course_id
				)
			);

			// Calculate completion rate.
			$completion_rate = ( $enrollments > 0 ) ? round( ( $completed / $enrollments ) * 100, 1 ) : 0;

			// Get average final score.
			$average_score = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(final_score) FROM {$wpdb->prefix}saw_lms_enrollments WHERE course_id = %d AND status = 'completed'",
					$course_id
				)
			);
			$average_score = $average_score ? round( $average_score, 1 ) : 0;

			$stats = array(
				'enrollments'     => absint( $enrollments ),
				'completed'       => absint( $completed ),
				'in_progress'     => absint( $in_progress ),
				'completion_rate' => $completion_rate,
				'average_score'   => $average_score,
			);

			// Cache for 5 minutes (will be invalidated on enrollment changes).
			wp_cache_set( $cache_key, $stats, 'saw_lms_courses', 300 );
		}

		return $stats;
	}

	/**
	 * Save meta box data
	 *
	 * @since 2.1.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Security checks.
		if ( ! isset( $_POST['saw_lms_course_settings_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_course_settings_nonce'] ) ), 'saw_lms_course_settings' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitize and save duration.
		if ( isset( $_POST['saw_lms_duration'] ) ) {
			$duration = floatval( $_POST['saw_lms_duration'] );
			update_post_meta( $post_id, '_saw_lms_duration', $duration );
		}

		// Sanitize and save difficulty.
		if ( isset( $_POST['saw_lms_difficulty'] ) ) {
			$allowed_difficulties = array( 'beginner', 'intermediate', 'advanced', 'expert' );
			$difficulty           = sanitize_text_field( wp_unslash( $_POST['saw_lms_difficulty'] ) );
			$difficulty           = in_array( $difficulty, $allowed_difficulties, true ) ? $difficulty : 'beginner';
			update_post_meta( $post_id, '_saw_lms_difficulty', $difficulty );
		}

		// Sanitize and save pass percentage.
		if ( isset( $_POST['saw_lms_pass_percentage'] ) ) {
			$pass_percentage = absint( $_POST['saw_lms_pass_percentage'] );
			$pass_percentage = max( 0, min( 100, $pass_percentage ) ); // Clamp 0-100.
			update_post_meta( $post_id, '_saw_lms_pass_percentage', $pass_percentage );
		}

		// Save certificate enable.
		$certificate_enable = isset( $_POST['saw_lms_certificate_enable'] ) ? 1 : 0;
		update_post_meta( $post_id, '_saw_lms_certificate_enable', $certificate_enable );

		// Sanitize and save points.
		if ( isset( $_POST['saw_lms_points'] ) ) {
			$points = absint( $_POST['saw_lms_points'] );
			update_post_meta( $post_id, '_saw_lms_points', $points );
		}

		// Save repeatable.
		$repeatable = isset( $_POST['saw_lms_repeatable'] ) ? 1 : 0;
		update_post_meta( $post_id, '_saw_lms_repeatable', $repeatable );

		// Invalidate course stats cache.
		wp_cache_delete( 'course_stats_' . $post_id, 'saw_lms_courses' );

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
		// Remove date column temporarily.
		$date = $columns['date'];
		unset( $columns['date'] );

		// Add custom columns.
		$columns['difficulty']   = __( 'Difficulty', 'saw-lms' );
		$columns['duration']     = __( 'Duration', 'saw-lms' );
		$columns['enrollments']  = __( 'Enrollments', 'saw-lms' );
		$columns['completion']   = __( 'Completion', 'saw-lms' );

		// Re-add date column at the end.
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
			case 'difficulty':
				$difficulty = get_post_meta( $post_id, '_saw_lms_difficulty', true );
				if ( ! empty( $difficulty ) ) {
					$difficulty_labels = array(
						'beginner'     => __( 'Beginner', 'saw-lms' ),
						'intermediate' => __( 'Intermediate', 'saw-lms' ),
						'advanced'     => __( 'Advanced', 'saw-lms' ),
						'expert'       => __( 'Expert', 'saw-lms' ),
					);
					echo '<span class="saw-lms-difficulty saw-lms-difficulty-' . esc_attr( $difficulty ) . '">';
					echo esc_html( $difficulty_labels[ $difficulty ] ?? ucfirst( $difficulty ) );
					echo '</span>';
				} else {
					echo '—';
				}
				break;

			case 'duration':
				$duration = get_post_meta( $post_id, '_saw_lms_duration', true );
				if ( ! empty( $duration ) ) {
					/* translators: %s: duration in hours */
					printf( esc_html__( '%s hours', 'saw-lms' ), esc_html( $duration ) );
				} else {
					echo '—';
				}
				break;

			case 'enrollments':
				$stats = $this->get_course_stats( $post_id );
				echo '<strong>' . absint( $stats['enrollments'] ) . '</strong>';
				break;

			case 'completion':
				$stats = $this->get_course_stats( $post_id );
				echo '<span title="' . esc_attr( $stats['completed'] . ' / ' . $stats['enrollments'] ) . '">';
				echo esc_html( $stats['completion_rate'] ) . '%';
				echo '</span>';
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
		$columns['difficulty'] = 'difficulty';
		$columns['duration']   = 'duration';
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
		// Only on course edit screen.
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Enqueue admin styles (will be created in next steps).
		wp_enqueue_style(
			'saw-lms-course-admin',
			SAW_LMS_URL . 'assets/css/admin/course.css',
			array(),
			SAW_LMS_VERSION
		);

		// Enqueue admin scripts (will be created in next steps).
		wp_enqueue_script(
			'saw-lms-course-admin',
			SAW_LMS_URL . 'assets/js/admin/course.js',
			array( 'jquery' ),
			SAW_LMS_VERSION,
			true
		);

		// Localize script data.
		wp_localize_script(
			'saw-lms-course-admin',
			'sawLmsCourse',
			array(
				'postId' => get_the_ID(),
				'nonce'  => wp_create_nonce( 'saw_lms_course_admin' ),
				'i18n'   => array(
					'error'   => __( 'An error occurred. Please try again.', 'saw-lms' ),
					'success' => __( 'Settings saved successfully.', 'saw-lms' ),
				),
			)
		);
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