<?php
/**
 * Section Custom Post Type
 *
 * Handles registration and functionality for the Section CPT.
 * Sections are hierarchical containers for lessons within a course.
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
 * SAW_LMS_Section Class
 *
 * Manages the Section custom post type including registration,
 * meta boxes, admin columns, and section-specific functionality.
 *
 * @since 2.1.0
 */
class SAW_LMS_Section {

	/**
	 * Post type slug
	 *
	 * @var string
	 */
	const POST_TYPE = 'saw_section';

	/**
	 * Singleton instance
	 *
	 * @var SAW_LMS_Section|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return SAW_LMS_Section
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
	 * Register hooks for the Section CPT.
	 *
	 * @since 2.1.0
	 */
	private function __construct() {
		// Register post type.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

		// Admin columns.
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
	}

	/**
	 * Register Section post type
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Sections', 'Post Type General Name', 'saw-lms' ),
			'singular_name'         => _x( 'Section', 'Post Type Singular Name', 'saw-lms' ),
			'menu_name'             => __( 'Sections', 'saw-lms' ),
			'name_admin_bar'        => __( 'Section', 'saw-lms' ),
			'archives'              => __( 'Section Archives', 'saw-lms' ),
			'attributes'            => __( 'Section Attributes', 'saw-lms' ),
			'parent_item_colon'     => __( 'Parent Section:', 'saw-lms' ),
			'all_items'             => __( 'All Sections', 'saw-lms' ),
			'add_new_item'          => __( 'Add New Section', 'saw-lms' ),
			'add_new'               => __( 'Add New', 'saw-lms' ),
			'new_item'              => __( 'New Section', 'saw-lms' ),
			'edit_item'             => __( 'Edit Section', 'saw-lms' ),
			'update_item'           => __( 'Update Section', 'saw-lms' ),
			'view_item'             => __( 'View Section', 'saw-lms' ),
			'view_items'            => __( 'View Sections', 'saw-lms' ),
			'search_items'          => __( 'Search Section', 'saw-lms' ),
			'not_found'             => __( 'Not found', 'saw-lms' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'saw-lms' ),
		);

		$args = array(
			'label'               => __( 'Section', 'saw-lms' ),
			'description'         => __( 'SAW LMS Course Sections', 'saw-lms' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
			'hierarchical'        => true,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'saw-lms',
			'menu_position'       => 26,
			'menu_icon'           => 'dashicons-list-view',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => array( 'saw_section', 'saw_sections' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'sections',
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
		// Section Settings meta box.
		add_meta_box(
			'saw_lms_section_settings',
			__( 'Section Settings', 'saw-lms' ),
			array( $this, 'render_settings_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Section Lessons meta box.
		add_meta_box(
			'saw_lms_section_lessons',
			__( 'Section Lessons', 'saw-lms' ),
			array( $this, 'render_lessons_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render Section Settings meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_settings_meta_box( $post ) {
		// Nonce for security.
		wp_nonce_field( 'saw_lms_section_settings', 'saw_lms_section_settings_nonce' );

		// Get current values.
		$course_id     = get_post_meta( $post->ID, '_saw_lms_course_id', true );
		$section_order = get_post_meta( $post->ID, '_saw_lms_section_order', true );

		// Defaults.
		$course_id     = ! empty( $course_id ) ? $course_id : '';
		$section_order = ! empty( $section_order ) ? $section_order : 0;

		// Get all courses for dropdown.
		$courses = get_posts(
			array(
				'post_type'      => 'saw_course',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'any',
			)
		);

		?>
		<div class="saw-lms-meta-box">
			<table class="form-table">
				<tbody>
					<!-- Course -->
					<tr>
						<th scope="row">
							<label for="saw_lms_course_id"><?php esc_html_e( 'Parent Course', 'saw-lms' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<select id="saw_lms_course_id" name="saw_lms_course_id" class="regular-text" required>
								<option value=""><?php esc_html_e( '— Select Course —', 'saw-lms' ); ?></option>
								<?php foreach ( $courses as $course ) : ?>
									<option value="<?php echo absint( $course->ID ); ?>" <?php selected( $course_id, $course->ID ); ?>>
										<?php echo esc_html( $course->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select the course this section belongs to.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Section Order -->
					<tr>
						<th scope="row">
							<label for="saw_lms_section_order"><?php esc_html_e( 'Section Order', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_section_order" 
								name="saw_lms_section_order" 
								value="<?php echo esc_attr( $section_order ); ?>" 
								min="0" 
								step="1" 
								class="small-text"
							/>
							<p class="description"><?php esc_html_e( 'Display order within the course (0 = first).', 'saw-lms' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Section Lessons meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_lessons_meta_box( $post ) {
		// Get lessons in this section.
		$lessons = $this->get_section_lessons( $post->ID );

		if ( empty( $lessons ) ) {
			echo '<p>' . esc_html__( 'No lessons in this section yet.', 'saw-lms' ) . '</p>';
			return;
		}

		echo '<ul class="saw-lms-lessons-list">';
		foreach ( $lessons as $lesson ) {
			$edit_link = get_edit_post_link( $lesson->ID );
			echo '<li>';
			echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $lesson->post_title ) . '</a>';
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Get lessons in a section
	 *
	 * @since 2.1.0
	 * @param int $section_id Section post ID.
	 * @return WP_Post[] Array of lesson posts.
	 */
	private function get_section_lessons( $section_id ) {
		$cache_key = 'section_lessons_' . $section_id;
		$lessons   = wp_cache_get( $cache_key, 'saw_lms_sections' );

		if ( false === $lessons ) {
			$lessons = get_posts(
				array(
					'post_type'      => 'saw_lesson',
					'posts_per_page' => -1,
					'meta_key'       => '_saw_lms_section_id',
					'meta_value'     => $section_id,
					'orderby'        => 'meta_value_num',
					'meta_key'       => '_saw_lms_lesson_order',
					'order'          => 'ASC',
					'post_status'    => 'any',
				)
			);

			// Cache for 5 minutes.
			wp_cache_set( $cache_key, $lessons, 'saw_lms_sections', 300 );
		}

		return $lessons;
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
		if ( ! isset( $_POST['saw_lms_section_settings_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_section_settings_nonce'] ) ), 'saw_lms_section_settings' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitize and save course ID.
		if ( isset( $_POST['saw_lms_course_id'] ) ) {
			$course_id = absint( $_POST['saw_lms_course_id'] );

			// Validate course exists.
			if ( SAW_LMS_Course::course_exists( $course_id ) ) {
				update_post_meta( $post_id, '_saw_lms_course_id', $course_id );
			}
		}

		// Sanitize and save section order.
		if ( isset( $_POST['saw_lms_section_order'] ) ) {
			$section_order = absint( $_POST['saw_lms_section_order'] );
			update_post_meta( $post_id, '_saw_lms_section_order', $section_order );
		}

		// Invalidate cache.
		$course_id = get_post_meta( $post_id, '_saw_lms_course_id', true );
		if ( $course_id ) {
			wp_cache_delete( 'course_sections_' . $course_id, 'saw_lms_courses' );
		}
		wp_cache_delete( 'section_lessons_' . $post_id, 'saw_lms_sections' );

		/**
		 * Fires after section meta is saved.
		 *
		 * @since 2.1.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		do_action( 'saw_lms_section_meta_saved', $post_id, $post );
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
		$columns['course']       = __( 'Course', 'saw-lms' );
		$columns['section_order'] = __( 'Order', 'saw-lms' );
		$columns['lessons_count'] = __( 'Lessons', 'saw-lms' );

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
			case 'course':
				$course_id = get_post_meta( $post_id, '_saw_lms_course_id', true );
				if ( $course_id ) {
					$course = SAW_LMS_Course::get_course( $course_id );
					if ( $course ) {
						$edit_link = get_edit_post_link( $course_id );
						echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $course->post_title ) . '</a>';
					} else {
						echo '—';
					}
				} else {
					echo '<span class="saw-lms-error">' . esc_html__( 'No course assigned', 'saw-lms' ) . '</span>';
				}
				break;

			case 'section_order':
				$order = get_post_meta( $post_id, '_saw_lms_section_order', true );
				echo '<strong>' . absint( $order ) . '</strong>';
				break;

			case 'lessons_count':
				$lessons = $this->get_section_lessons( $post_id );
				echo '<span class="saw-lms-count">' . count( $lessons ) . '</span>';
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
		$columns['section_order'] = 'section_order';
		return $columns;
	}

	/**
	 * Get section by ID (helper method)
	 *
	 * @since 2.1.0
	 * @param int $section_id Section post ID.
	 * @return WP_Post|null Section post object or null.
	 */
	public static function get_section( $section_id ) {
		$section = get_post( $section_id );

		if ( ! $section || self::POST_TYPE !== $section->post_type ) {
			return null;
		}

		return $section;
	}

	/**
	 * Check if section exists
	 *
	 * @since 2.1.0
	 * @param int $section_id Section post ID.
	 * @return bool True if section exists, false otherwise.
	 */
	public static function section_exists( $section_id ) {
		return null !== self::get_section( $section_id );
	}

	/**
	 * Get course sections (cache-ready)
	 *
	 * @since 2.1.0
	 * @param int $course_id Course post ID.
	 * @return WP_Post[] Array of section posts.
	 */
	public static function get_course_sections( $course_id ) {
		$cache_key = 'course_sections_' . $course_id;
		$sections  = wp_cache_get( $cache_key, 'saw_lms_courses' );

		if ( false === $sections ) {
			$sections = get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'posts_per_page' => -1,
					'meta_key'       => '_saw_lms_course_id',
					'meta_value'     => $course_id,
					'orderby'        => 'meta_value_num',
					'meta_key'       => '_saw_lms_section_order',
					'order'          => 'ASC',
					'post_status'    => 'publish',
				)
			);

			// Cache for 5 minutes (invalidated on section save).
			wp_cache_set( $cache_key, $sections, 'saw_lms_courses', 300 );
		}

		return $sections;
	}
}