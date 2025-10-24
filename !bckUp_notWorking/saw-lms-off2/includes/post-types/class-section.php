<?php
/**
 * Section Custom Post Type
 *
 * Handles registration and functionality for the Section CPT.
 * Sections are hierarchical containers for lessons within a course.
 *
 * UPDATED in v2.2.0: Added Optional Content meta box (video URL + documents).
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       2.1.0
 * @version     2.2.0
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

		// Admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
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

		// Section Optional Content meta box (NEW in v2.2.0).
		add_meta_box(
			'saw_lms_section_content',
			__( 'Section Optional Content', 'saw-lms' ),
			array( $this, 'render_content_meta_box' ),
			self::POST_TYPE,
			'normal',
			'default'
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
	 * Render Section Optional Content meta box
	 *
	 * NEW in v2.2.0: Allows adding intro video and downloadable materials to section.
	 *
	 * @since 2.2.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_content_meta_box( $post ) {
		// Nonce for security.
		wp_nonce_field( 'saw_lms_section_content', 'saw_lms_section_content_nonce' );

		// Get current values.
		$video_url = get_post_meta( $post->ID, '_saw_section_video_url', true );
		$documents = get_post_meta( $post->ID, '_saw_section_documents', true );

		// Defaults.
		$video_url = ! empty( $video_url ) ? $video_url : '';
		$documents = ! empty( $documents ) && is_array( $documents ) ? $documents : array();

		?>
		<div class="saw-lms-meta-box saw-lms-section-content">
			<div class="saw-lms-info-box" style="background: #e7f3fe; border-left: 4px solid #2196F3; padding: 12px; margin-bottom: 20px;">
				<p style="margin: 0;">
					<strong><?php esc_html_e( 'ℹ️ Optional:', 'saw-lms' ); ?></strong>
					<?php esc_html_e( 'You can add an introductory video and downloadable materials for this section. These will be displayed to students before the lessons.', 'saw-lms' ); ?>
				</p>
			</div>

			<table class="form-table">
				<tbody>
					<!-- Intro Video URL -->
					<tr>
						<th scope="row">
							<label for="saw_section_video_url"><?php esc_html_e( 'Intro Video URL', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="url" 
								id="saw_section_video_url" 
								name="saw_section_video_url" 
								value="<?php echo esc_url( $video_url ); ?>" 
								class="regular-text"
								placeholder="https://www.youtube.com/watch?v=..."
							/>
							<p class="description">
								<?php esc_html_e( 'Optional: URL to an introductory video for this section (YouTube, Vimeo, or direct link).', 'saw-lms' ); ?>
							</p>
							<?php if ( ! empty( $video_url ) ) : ?>
								<div class="saw-lms-video-preview" style="margin-top: 10px;">
									<p><strong><?php esc_html_e( 'Current video:', 'saw-lms' ); ?></strong> 
									<a href="<?php echo esc_url( $video_url ); ?>" target="_blank" rel="noopener">
										<?php echo esc_html( $video_url ); ?>
									</a>
									</p>
								</div>
							<?php endif; ?>
						</td>
					</tr>

					<!-- Section Documents -->
					<tr>
						<th scope="row">
							<label for="saw_section_documents"><?php esc_html_e( 'Section Materials', 'saw-lms' ); ?></label>
						</th>
						<td>
							<div id="saw_section_documents_container">
								<?php if ( ! empty( $documents ) ) : ?>
									<ul class="saw-lms-documents-list" style="margin-bottom: 15px;">
										<?php foreach ( $documents as $attachment_id ) : ?>
											<?php
											$attachment = get_post( $attachment_id );
											if ( $attachment ) :
												$file_url  = wp_get_attachment_url( $attachment_id );
												$file_name = basename( get_attached_file( $attachment_id ) );
												$file_size = size_format( filesize( get_attached_file( $attachment_id ) ) );
												?>
												<li class="saw-lms-document-item" data-id="<?php echo absint( $attachment_id ); ?>" style="padding: 10px; background: #f9f9f9; border: 1px solid #ddd; margin-bottom: 5px; border-radius: 4px;">
													<span class="dashicons dashicons-media-document" style="color: #2196F3;"></span>
													<strong><?php echo esc_html( $file_name ); ?></strong>
													<span class="saw-lms-file-size" style="color: #666; font-size: 12px;">(<?php echo esc_html( $file_size ); ?>)</span>
													<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" class="button button-small" style="margin-left: 10px;">
														<?php esc_html_e( 'View', 'saw-lms' ); ?>
													</a>
													<button type="button" class="button button-small saw-lms-remove-document" data-id="<?php echo absint( $attachment_id ); ?>" style="margin-left: 5px; color: #d63638;">
														<?php esc_html_e( 'Remove', 'saw-lms' ); ?>
													</button>
													<input type="hidden" name="saw_section_documents[]" value="<?php echo absint( $attachment_id ); ?>" />
												</li>
											<?php endif; ?>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<p class="saw-lms-no-documents" style="color: #666; font-style: italic;">
										<?php esc_html_e( 'No materials uploaded yet.', 'saw-lms' ); ?>
									</p>
								<?php endif; ?>
							</div>

							<button type="button" id="saw_section_upload_documents" class="button button-secondary">
								<span class="dashicons dashicons-upload" style="margin-top: 3px;"></span>
								<?php esc_html_e( 'Upload Materials', 'saw-lms' ); ?>
							</button>
							<p class="description">
								<?php esc_html_e( 'Optional: Upload PDF documents, presentations, or other materials for this section.', 'saw-lms' ); ?>
							</p>
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
	 * UPDATED in v2.2.0: Added save logic for video URL and documents.
	 *
	 * @since 2.1.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// --- SAVE SETTINGS META BOX ---
		if ( isset( $_POST['saw_lms_section_settings_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_section_settings_nonce'] ) ), 'saw_lms_section_settings' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Sanitize and save course ID.
			if ( isset( $_POST['saw_lms_course_id'] ) ) {
				$course_id = absint( $_POST['saw_lms_course_id'] );

				// Validate course exists.
				if ( $course_id > 0 && SAW_LMS_Course::course_exists( $course_id ) ) {
					update_post_meta( $post_id, '_saw_lms_course_id', $course_id );
				}
			}

			// Sanitize and save section order.
			if ( isset( $_POST['saw_lms_section_order'] ) ) {
				$section_order = absint( $_POST['saw_lms_section_order'] );
				update_post_meta( $post_id, '_saw_lms_section_order', $section_order );
			}
		}

		// --- SAVE OPTIONAL CONTENT META BOX (NEW in v2.2.0) ---
		if ( isset( $_POST['saw_lms_section_content_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_section_content_nonce'] ) ), 'saw_lms_section_content' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Sanitize and save video URL.
			if ( isset( $_POST['saw_section_video_url'] ) ) {
				$video_url = esc_url_raw( wp_unslash( $_POST['saw_section_video_url'] ) );
				
				// Only save if valid URL or empty.
				if ( empty( $video_url ) || filter_var( $video_url, FILTER_VALIDATE_URL ) ) {
					update_post_meta( $post_id, '_saw_section_video_url', $video_url );
				}
			}

			// Sanitize and save documents.
			if ( isset( $_POST['saw_section_documents'] ) && is_array( $_POST['saw_section_documents'] ) ) {
				$documents = array_map( 'absint', $_POST['saw_section_documents'] );
				
				// Validate all are valid attachment IDs.
				$valid_documents = array();
				foreach ( $documents as $attachment_id ) {
					if ( get_post( $attachment_id ) && 'attachment' === get_post_type( $attachment_id ) ) {
						$valid_documents[] = $attachment_id;
					}
				}
				
				update_post_meta( $post_id, '_saw_section_documents', $valid_documents );
			} else {
				// If no documents selected, clear the field.
				delete_post_meta( $post_id, '_saw_section_documents' );
			}
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
	 * Enqueue admin assets
	 *
	 * NEW in v2.2.0: Enqueues media uploader and custom JS/CSS.
	 *
	 * @since 2.2.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only on section edit screen.
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Enqueue WordPress media uploader.
		wp_enqueue_media();

		// Enqueue admin styles.
		wp_enqueue_style(
			'saw-lms-section-meta-box',
			SAW_LMS_PLUGIN_URL . 'assets/css/admin/section-meta-box.css',
			array(),
			SAW_LMS_VERSION
		);

		// Enqueue admin scripts.
		wp_enqueue_script(
			'saw-lms-section-meta-box',
			SAW_LMS_PLUGIN_URL . 'assets/js/admin/section-meta-box.js',
			array( 'jquery', 'media-upload', 'media-views' ),
			SAW_LMS_VERSION,
			true
		);

		// Localize script data.
		wp_localize_script(
			'saw-lms-section-meta-box',
			'sawLmsSection',
			array(
				'postId'       => get_the_ID(),
				'nonce'        => wp_create_nonce( 'saw_lms_section_admin' ),
				'i18n'         => array(
					'selectDocuments'   => __( 'Select Documents', 'saw-lms' ),
					'useDocuments'      => __( 'Use These Documents', 'saw-lms' ),
					'documentsUploaded' => __( 'Documents uploaded successfully!', 'saw-lms' ),
					'confirmRemove'     => __( 'Are you sure you want to remove this document?', 'saw-lms' ),
					'noDocuments'       => __( 'No materials uploaded yet.', 'saw-lms' ),
				),
			)
		);
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
		$columns['course']        = __( 'Course', 'saw-lms' );
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