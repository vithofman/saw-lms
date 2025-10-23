<?php
/**
 * Lesson Custom Post Type
 *
 * Handles registration and functionality for the Lesson CPT.
 * Lessons can be of different types: video, text, document, or assignment.
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       2.1.0
 * @version     2.1.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Lesson Class
 *
 * Manages the Lesson custom post type including registration,
 * meta boxes, admin columns, and lesson-specific functionality.
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
	 * Lesson types
	 *
	 * @var array
	 */
	const LESSON_TYPES = array(
		'video'      => 'Video',
		'text'       => 'Text',
		'document'   => 'Document',
		'assignment' => 'Assignment',
	);

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
	 * Register hooks for the Lesson CPT.
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
	 * Register Lesson post type
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
			'description'         => __( 'SAW LMS Course Lessons', 'saw-lms' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'revisions' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'saw-lms',
			'menu_position'       => 27,
			'menu_icon'           => 'dashicons-media-document',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => array( 'saw_lesson', 'saw_lessons' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'lessons',
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
		// Lesson Settings meta box.
		add_meta_box(
			'saw_lms_lesson_settings',
			__( 'Lesson Settings', 'saw-lms' ),
			array( $this, 'render_settings_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Lesson Content meta box.
		add_meta_box(
			'saw_lms_lesson_content',
			__( 'Lesson Content', 'saw-lms' ),
			array( $this, 'render_content_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render Lesson Settings meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_settings_meta_box( $post ) {
		// Nonce for security.
		wp_nonce_field( 'saw_lms_lesson_settings', 'saw_lms_lesson_settings_nonce' );

		// Get current values.
		$section_id   = get_post_meta( $post->ID, '_saw_lms_section_id', true );
		$lesson_order = get_post_meta( $post->ID, '_saw_lms_lesson_order', true );
		$lesson_type  = get_post_meta( $post->ID, '_saw_lms_lesson_type', true );
		$duration     = get_post_meta( $post->ID, '_saw_lms_duration', true );

		// Defaults.
		$section_id   = ! empty( $section_id ) ? $section_id : '';
		$lesson_order = ! empty( $lesson_order ) ? $lesson_order : 0;
		$lesson_type  = ! empty( $lesson_type ) ? $lesson_type : 'video';
		$duration     = ! empty( $duration ) ? $duration : '';

		// Get all sections for dropdown.
		$sections = get_posts(
			array(
				'post_type'      => 'saw_section',
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
					<!-- Section -->
					<tr>
						<th scope="row">
							<label for="saw_lms_section_id"><?php esc_html_e( 'Parent Section', 'saw-lms' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<select id="saw_lms_section_id" name="saw_lms_section_id" class="regular-text" required>
								<option value=""><?php esc_html_e( '— Select Section —', 'saw-lms' ); ?></option>
								<?php foreach ( $sections as $section ) : ?>
									<?php
									$course_id = get_post_meta( $section->ID, '_saw_lms_course_id', true );
									$course    = $course_id ? get_post( $course_id ) : null;
									$label     = $course ? $course->post_title . ' → ' . $section->post_title : $section->post_title;
									?>
									<option value="<?php echo absint( $section->ID ); ?>" <?php selected( $section_id, $section->ID ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select the section this lesson belongs to.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Lesson Type -->
					<tr>
						<th scope="row">
							<label for="saw_lms_lesson_type"><?php esc_html_e( 'Lesson Type', 'saw-lms' ); ?></label>
						</th>
						<td>
							<select id="saw_lms_lesson_type" name="saw_lms_lesson_type" class="regular-text">
								<?php foreach ( self::LESSON_TYPES as $type => $label ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $lesson_type, $type ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Type of content in this lesson.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Lesson Order -->
					<tr>
						<th scope="row">
							<label for="saw_lms_lesson_order"><?php esc_html_e( 'Lesson Order', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_lesson_order" 
								name="saw_lms_lesson_order" 
								value="<?php echo esc_attr( $lesson_order ); ?>" 
								min="0" 
								step="1" 
								class="small-text"
							/>
							<p class="description"><?php esc_html_e( 'Display order within the section (0 = first).', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Duration -->
					<tr>
						<th scope="row">
							<label for="saw_lms_duration"><?php esc_html_e( 'Duration (minutes)', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_duration" 
								name="saw_lms_duration" 
								value="<?php echo esc_attr( $duration ); ?>" 
								min="0" 
								step="1" 
								class="small-text"
							/>
							<p class="description"><?php esc_html_e( 'Estimated time to complete this lesson.', 'saw-lms' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Lesson Content meta box
	 *
	 * Conditional fields based on lesson type.
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_content_meta_box( $post ) {
		// Nonce for security.
		wp_nonce_field( 'saw_lms_lesson_content', 'saw_lms_lesson_content_nonce' );

		// Get current values.
		$lesson_type   = get_post_meta( $post->ID, '_saw_lms_lesson_type', true );
		$video_source  = get_post_meta( $post->ID, '_saw_lms_video_source', true );
		$video_url     = get_post_meta( $post->ID, '_saw_lms_video_url', true );
		$document_url  = get_post_meta( $post->ID, '_saw_lms_document_url', true );
		$assignment_max_points = get_post_meta( $post->ID, '_saw_lms_assignment_max_points', true );
		$assignment_passing_points = get_post_meta( $post->ID, '_saw_lms_assignment_passing_points', true );
		$assignment_allow_resubmit = get_post_meta( $post->ID, '_saw_lms_assignment_allow_resubmit', true );

		// Defaults.
		$lesson_type   = ! empty( $lesson_type ) ? $lesson_type : 'video';
		$video_source  = ! empty( $video_source ) ? $video_source : 'youtube';
		$video_url     = ! empty( $video_url ) ? $video_url : '';
		$document_url  = ! empty( $document_url ) ? $document_url : '';
		$assignment_max_points = ! empty( $assignment_max_points ) ? $assignment_max_points : 100;
		$assignment_passing_points = ! empty( $assignment_passing_points ) ? $assignment_passing_points : 70;
		$assignment_allow_resubmit = ! empty( $assignment_allow_resubmit ) ? 1 : 0;

		?>
		<div class="saw-lms-lesson-content">
			<!-- Video Content (conditional) -->
			<div class="saw-lms-content-section saw-lms-video-content" style="display: none;">
				<table class="form-table">
					<tbody>
						<!-- Video Source -->
						<tr>
							<th scope="row">
								<label for="saw_lms_video_source"><?php esc_html_e( 'Video Source', 'saw-lms' ); ?></label>
							</th>
							<td>
								<select id="saw_lms_video_source" name="saw_lms_video_source" class="regular-text">
									<option value="youtube" <?php selected( $video_source, 'youtube' ); ?>><?php esc_html_e( 'YouTube', 'saw-lms' ); ?></option>
									<option value="vimeo" <?php selected( $video_source, 'vimeo' ); ?>><?php esc_html_e( 'Vimeo', 'saw-lms' ); ?></option>
									<option value="embed" <?php selected( $video_source, 'embed' ); ?>><?php esc_html_e( 'Embed Code', 'saw-lms' ); ?></option>
								</select>
							</td>
						</tr>

						<!-- Video URL -->
						<tr>
							<th scope="row">
								<label for="saw_lms_video_url"><?php esc_html_e( 'Video URL / Embed Code', 'saw-lms' ); ?></label>
							</th>
							<td>
								<textarea 
									id="saw_lms_video_url" 
									name="saw_lms_video_url" 
									rows="4" 
									class="large-text"
								><?php echo esc_textarea( $video_url ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'YouTube: https://www.youtube.com/watch?v=...', 'saw-lms' ); ?><br>
									<?php esc_html_e( 'Vimeo: https://vimeo.com/...', 'saw-lms' ); ?><br>
									<?php esc_html_e( 'Embed: Paste iframe embed code', 'saw-lms' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Text Content (conditional) -->
			<div class="saw-lms-content-section saw-lms-text-content" style="display: none;">
				<p><?php esc_html_e( 'Use the main editor above to write text content for this lesson.', 'saw-lms' ); ?></p>
			</div>

			<!-- Document Content (conditional) -->
			<div class="saw-lms-content-section saw-lms-document-content" style="display: none;">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="saw_lms_document_url"><?php esc_html_e( 'Document', 'saw-lms' ); ?></label>
							</th>
							<td>
								<input 
									type="text" 
									id="saw_lms_document_url" 
									name="saw_lms_document_url" 
									value="<?php echo esc_attr( $document_url ); ?>" 
									class="regular-text"
									readonly
								/>
								<button type="button" class="button saw-lms-upload-document">
									<?php esc_html_e( 'Upload Document', 'saw-lms' ); ?>
								</button>
								<p class="description">
									<?php esc_html_e( 'Upload a PDF, Word, PowerPoint, or other document file.', 'saw-lms' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Assignment Content (conditional) - NEW in v2.1.1 -->
			<div class="saw-lms-content-section saw-lms-assignment-content" style="display: none;">
				<table class="form-table">
					<tbody>
						<!-- Assignment Instructions -->
						<tr>
							<th scope="row" colspan="2">
								<label><?php esc_html_e( 'Assignment Instructions', 'saw-lms' ); ?></label>
								<p class="description">
									<?php esc_html_e( 'Use the main editor above to write detailed instructions for this assignment.', 'saw-lms' ); ?>
								</p>
							</th>
						</tr>

						<!-- Max Points -->
						<tr>
							<th scope="row">
								<label for="saw_lms_assignment_max_points"><?php esc_html_e( 'Maximum Points', 'saw-lms' ); ?></label>
							</th>
							<td>
								<input 
									type="number" 
									id="saw_lms_assignment_max_points" 
									name="saw_lms_assignment_max_points" 
									value="<?php echo esc_attr( $assignment_max_points ); ?>" 
									min="0" 
									step="1" 
									class="small-text"
								/>
								<p class="description"><?php esc_html_e( 'Maximum points this assignment is worth.', 'saw-lms' ); ?></p>
							</td>
						</tr>

						<!-- Passing Points -->
						<tr>
							<th scope="row">
								<label for="saw_lms_assignment_passing_points"><?php esc_html_e( 'Passing Points', 'saw-lms' ); ?></label>
							</th>
							<td>
								<input 
									type="number" 
									id="saw_lms_assignment_passing_points" 
									name="saw_lms_assignment_passing_points" 
									value="<?php echo esc_attr( $assignment_passing_points ); ?>" 
									min="0" 
									step="1" 
									class="small-text"
								/>
								<p class="description"><?php esc_html_e( 'Minimum points required to pass this assignment.', 'saw-lms' ); ?></p>
							</td>
						</tr>

						<!-- Allow Resubmit -->
						<tr>
							<th scope="row">
								<label for="saw_lms_assignment_allow_resubmit"><?php esc_html_e( 'Resubmissions', 'saw-lms' ); ?></label>
							</th>
							<td>
								<label>
									<input 
										type="checkbox" 
										id="saw_lms_assignment_allow_resubmit" 
										name="saw_lms_assignment_allow_resubmit" 
										value="1" 
										<?php checked( $assignment_allow_resubmit, 1 ); ?>
									/>
									<?php esc_html_e( 'Allow students to resubmit after grading', 'saw-lms' ); ?>
								</label>
							</td>
						</tr>

						<!-- Submission Note -->
						<tr>
							<th scope="row" colspan="2">
								<div class="saw-lms-info-box" style="background: #e7f3fe; border-left: 4px solid #2196F3; padding: 12px; margin-top: 10px;">
									<p style="margin: 0;">
										<strong><?php esc_html_e( 'ℹ️ Note:', 'saw-lms' ); ?></strong>
										<?php esc_html_e( 'Students will be able to upload files or submit text as their assignment. Grading interface will be available in the Reports section (Phase 17).', 'saw-lms' ); ?>
									</p>
								</div>
							</th>
						</tr>
					</tbody>
				</table>
			</div>

			<script type="text/javascript">
				// Show/hide conditional fields based on lesson type
				jQuery(document).ready(function($) {
					function toggleContentSections() {
						var lessonType = $('#saw_lms_lesson_type').val();
						
						// Hide all content sections
						$('.saw-lms-content-section').hide();
						
						// Show relevant section
						if (lessonType === 'video') {
							$('.saw-lms-video-content').show();
						} else if (lessonType === 'text') {
							$('.saw-lms-text-content').show();
						} else if (lessonType === 'document') {
							$('.saw-lms-document-content').show();
						} else if (lessonType === 'assignment') {
							$('.saw-lms-assignment-content').show();
						}
					}
					
					// Initial state
					toggleContentSections();
					
					// On change
					$('#saw_lms_lesson_type').on('change', toggleContentSections);
				});
			</script>
		</div>
		<?php
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
		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Security checks for settings.
		if ( isset( $_POST['saw_lms_lesson_settings_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_lesson_settings_nonce'] ) ), 'saw_lms_lesson_settings' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Save section ID.
			if ( isset( $_POST['saw_lms_section_id'] ) ) {
				$section_id = absint( $_POST['saw_lms_section_id'] );
				if ( $section_id > 0 ) {
					// Validate section exists.
					if ( SAW_LMS_Section::section_exists( $section_id ) ) {
						update_post_meta( $post_id, '_saw_lms_section_id', $section_id );
					}
				}
			}

			// Save lesson order.
			if ( isset( $_POST['saw_lms_lesson_order'] ) ) {
				$lesson_order = absint( $_POST['saw_lms_lesson_order'] );
				update_post_meta( $post_id, '_saw_lms_lesson_order', $lesson_order );
			}

			// Save lesson type.
			if ( isset( $_POST['saw_lms_lesson_type'] ) ) {
				$lesson_type = sanitize_text_field( wp_unslash( $_POST['saw_lms_lesson_type'] ) );
				if ( array_key_exists( $lesson_type, self::LESSON_TYPES ) ) {
					update_post_meta( $post_id, '_saw_lms_lesson_type', $lesson_type );
				}
			}

			// Save duration.
			if ( isset( $_POST['saw_lms_duration'] ) ) {
				$duration = absint( $_POST['saw_lms_duration'] );
				update_post_meta( $post_id, '_saw_lms_duration', $duration );
			}
		}

		// Security checks for content.
		if ( isset( $_POST['saw_lms_lesson_content_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_lesson_content_nonce'] ) ), 'saw_lms_lesson_content' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Save video source.
			if ( isset( $_POST['saw_lms_video_source'] ) ) {
				$video_source = sanitize_text_field( wp_unslash( $_POST['saw_lms_video_source'] ) );
				$allowed      = array( 'youtube', 'vimeo', 'embed' );
				$video_source = in_array( $video_source, $allowed, true ) ? $video_source : 'youtube';
				update_post_meta( $post_id, '_saw_lms_video_source', $video_source );
			}

			// Save video URL.
			if ( isset( $_POST['saw_lms_video_url'] ) ) {
				$video_url = wp_kses_post( wp_unslash( $_POST['saw_lms_video_url'] ) );
				update_post_meta( $post_id, '_saw_lms_video_url', $video_url );
			}

			// Save document URL.
			if ( isset( $_POST['saw_lms_document_url'] ) ) {
				$document_url = esc_url_raw( wp_unslash( $_POST['saw_lms_document_url'] ) );
				update_post_meta( $post_id, '_saw_lms_document_url', $document_url );
			}

			// Save assignment settings (NEW in v2.1.1).
			if ( isset( $_POST['saw_lms_assignment_max_points'] ) ) {
				$max_points = absint( $_POST['saw_lms_assignment_max_points'] );
				update_post_meta( $post_id, '_saw_lms_assignment_max_points', $max_points );
			}

			if ( isset( $_POST['saw_lms_assignment_passing_points'] ) ) {
				$passing_points = absint( $_POST['saw_lms_assignment_passing_points'] );
				update_post_meta( $post_id, '_saw_lms_assignment_passing_points', $passing_points );
			}

			$allow_resubmit = isset( $_POST['saw_lms_assignment_allow_resubmit'] ) ? 1 : 0;
			update_post_meta( $post_id, '_saw_lms_assignment_allow_resubmit', $allow_resubmit );
		}

		// Invalidate cache.
		$section_id = get_post_meta( $post_id, '_saw_lms_section_id', true );
		if ( $section_id ) {
			wp_cache_delete( 'section_lessons_' . $section_id, 'saw_lms_sections' );
		}

		/**
		 * Fires after lesson meta is saved.
		 *
		 * @since 2.1.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		do_action( 'saw_lms_lesson_meta_saved', $post_id, $post );
	}

	/**
	 * Add admin columns
	 *
	 * @since 2.1.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_admin_columns( $columns ) {
		$date = $columns['date'];
		unset( $columns['date'] );

		$columns['section']      = __( 'Section', 'saw-lms' );
		$columns['lesson_type']  = __( 'Type', 'saw-lms' );
		$columns['lesson_order'] = __( 'Order', 'saw-lms' );
		$columns['duration']     = __( 'Duration', 'saw-lms' );

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
			case 'section':
				$section_id = get_post_meta( $post_id, '_saw_lms_section_id', true );
				if ( $section_id ) {
					$section = SAW_LMS_Section::get_section( $section_id );
					if ( $section ) {
						$edit_link = get_edit_post_link( $section_id );
						echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $section->post_title ) . '</a>';
					} else {
						echo '—';
					}
				} else {
					echo '<span class="saw-lms-error">' . esc_html__( 'No section assigned', 'saw-lms' ) . '</span>';
				}
				break;

			case 'lesson_type':
				$type = get_post_meta( $post_id, '_saw_lms_lesson_type', true );
				if ( ! empty( $type ) && isset( self::LESSON_TYPES[ $type ] ) ) {
					$type_icons = array(
						'video'      => '🎥',
						'text'       => '📝',
						'document'   => '📄',
						'assignment' => '✍️',
					);
					$icon = isset( $type_icons[ $type ] ) ? $type_icons[ $type ] : '';
					echo '<span class="saw-lms-type saw-lms-type-' . esc_attr( $type ) . '">';
					echo esc_html( $icon . ' ' . self::LESSON_TYPES[ $type ] );
					echo '</span>';
				} else {
					echo '—';
				}
				break;

			case 'lesson_order':
				$order = get_post_meta( $post_id, '_saw_lms_lesson_order', true );
				echo '<strong>' . absint( $order ) . '</strong>';
				break;

			case 'duration':
				$duration = get_post_meta( $post_id, '_saw_lms_duration', true );
				if ( ! empty( $duration ) ) {
					/* translators: %s: duration in minutes */
					printf( esc_html__( '%s min', 'saw-lms' ), esc_html( $duration ) );
				} else {
					echo '—';
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
		$columns['lesson_type']  = 'lesson_type';
		$columns['lesson_order'] = 'lesson_order';
		$columns['duration']     = 'duration';
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
		// Only on lesson edit screen.
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Enqueue media uploader.
		wp_enqueue_media();

		// Enqueue admin styles.
		wp_enqueue_style(
			'saw-lms-lesson-meta-box',
			SAW_LMS_URL . 'assets/css/admin/lesson-meta-box.css',
			array(),
			SAW_LMS_VERSION
		);

		// Enqueue admin scripts.
		wp_enqueue_script(
			'saw-lms-lesson-meta-box',
			SAW_LMS_URL . 'assets/js/admin/lesson-meta-box.js',
			array( 'jquery' ),
			SAW_LMS_VERSION,
			true
		);

		// Localize script data.
		wp_localize_script(
			'saw-lms-lesson-meta-box',
			'sawLmsLesson',
			array(
				'postId' => get_the_ID(),
				'nonce'  => wp_create_nonce( 'saw_lms_lesson_admin' ),
				'i18n'   => array(
					'selectDocument'    => __( 'Select Document', 'saw-lms' ),
					'useDocument'       => __( 'Use This Document', 'saw-lms' ),
					'documentUploaded'  => __( 'Document uploaded successfully!', 'saw-lms' ),
				),
			)
		);
	}

	/**
	 * Get lesson by ID (helper method)
	 *
	 * @since 2.1.0
	 * @param int $lesson_id Lesson post ID.
	 * @return WP_Post|null Lesson post object or null.
	 */
	public static function get_lesson( $lesson_id ) {
		$lesson = get_post( $lesson_id );

		if ( ! $lesson || self::POST_TYPE !== $lesson->post_type ) {
			return null;
		}

		return $lesson;
	}

	/**
	 * Check if lesson exists
	 *
	 * @since 2.1.0
	 * @param int $lesson_id Lesson post ID.
	 * @return bool True if lesson exists, false otherwise.
	 */
	public static function lesson_exists( $lesson_id ) {
		return null !== self::get_lesson( $lesson_id );
	}

	/**
	 * Get section lessons (cache-ready)
	 *
	 * @since 2.1.0
	 * @param int $section_id Section post ID.
	 * @return WP_Post[] Array of lesson posts.
	 */
	public static function get_section_lessons( $section_id ) {
		$cache_key = 'section_lessons_' . $section_id;
		$lessons   = wp_cache_get( $cache_key, 'saw_lms_sections' );

		if ( false === $lessons ) {
			$lessons = get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'posts_per_page' => -1,
					'meta_key'       => '_saw_lms_section_id',
					'meta_value'     => $section_id,
					'orderby'        => 'meta_value_num',
					'meta_key'       => '_saw_lms_lesson_order',
					'order'          => 'ASC',
					'post_status'    => 'publish',
				)
			);

			// Cache for 5 minutes.
			wp_cache_set( $cache_key, $lessons, 'saw_lms_sections', 300 );
		}

		return $lessons;
	}
}