<?php
/**
 * Quiz Custom Post Type
 *
 * Handles registration and functionality for the Quiz CPT.
 * The Quiz Builder UI will be implemented in later phases (Phase 4).
 *
 * UPDATED in v2.4.0: Complete implementation with all meta boxes, save logic, and admin columns.
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       2.1.0
 * @version     2.4.0
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
	 * Register hooks for the Quiz CPT.
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
			'description'         => __( 'SAW LMS Course Quizzes', 'saw-lms' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'revisions' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'saw-lms',
			'menu_position'       => 28,
			'menu_icon'           => 'dashicons-clipboard',
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
	 * UPDATED in v2.4.0: Added all required meta boxes.
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function add_meta_boxes() {
		// Quiz Settings meta box.
		add_meta_box(
			'saw_lms_quiz_settings',
			__( 'Quiz Settings', 'saw-lms' ),
			array( $this, 'render_settings_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Quiz Behavior meta box.
		add_meta_box(
			'saw_lms_quiz_behavior',
			__( 'Quiz Behavior', 'saw-lms' ),
			array( $this, 'render_behavior_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Quiz Assignment meta box (course/section).
		add_meta_box(
			'saw_lms_quiz_assignment',
			__( 'Quiz Assignment', 'saw-lms' ),
			array( $this, 'render_assignment_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);

		// Quiz Questions placeholder meta box.
		add_meta_box(
			'saw_lms_quiz_questions',
			__( 'Quiz Questions', 'saw-lms' ),
			array( $this, 'render_questions_meta_box' ),
			self::POST_TYPE,
			'normal',
			'default'
		);

		// Quiz Statistics meta box (read-only).
		add_meta_box(
			'saw_lms_quiz_stats',
			__( 'Quiz Statistics', 'saw-lms' ),
			array( $this, 'render_stats_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render Quiz Settings meta box
	 *
	 * NEW in v2.4.0: Passing score, time limit, max attempts.
	 *
	 * @since 2.4.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_settings_meta_box( $post ) {
		// Nonce for security.
		wp_nonce_field( 'saw_lms_quiz_settings', 'saw_lms_quiz_settings_nonce' );

		// Get current values.
		$passing_score = get_post_meta( $post->ID, '_saw_lms_passing_score_percent', true );
		$time_limit    = get_post_meta( $post->ID, '_saw_lms_time_limit_minutes', true );
		$max_attempts  = get_post_meta( $post->ID, '_saw_lms_max_attempts', true );

		// Defaults.
		$passing_score = ! empty( $passing_score ) ? $passing_score : 70;
		$time_limit    = ! empty( $time_limit ) ? $time_limit : 0;
		$max_attempts  = ! empty( $max_attempts ) ? $max_attempts : 0;

		?>
		<div class="saw-lms-meta-box">
			<table class="form-table">
				<tbody>
					<!-- Passing Score -->
					<tr>
						<th scope="row">
							<label for="saw_lms_passing_score_percent"><?php esc_html_e( 'Passing Score (%)', 'saw-lms' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_passing_score_percent" 
								name="saw_lms_passing_score_percent" 
								value="<?php echo esc_attr( $passing_score ); ?>" 
								min="0" 
								max="100" 
								step="1" 
								class="small-text"
								required
							/>
							<span class="saw-lms-unit">%</span>
							<p class="description"><?php esc_html_e( 'Minimum percentage required to pass this quiz (0-100).', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Time Limit -->
					<tr>
						<th scope="row">
							<label for="saw_lms_time_limit_minutes"><?php esc_html_e( 'Time Limit (minutes)', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_time_limit_minutes" 
								name="saw_lms_time_limit_minutes" 
								value="<?php echo esc_attr( $time_limit ); ?>" 
								min="0" 
								step="1" 
								class="small-text"
							/>
							<span class="saw-lms-unit"><?php esc_html_e( 'minutes', 'saw-lms' ); ?></span>
							<p class="description"><?php esc_html_e( 'Maximum time allowed to complete the quiz (0 = no limit).', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Max Attempts -->
					<tr>
						<th scope="row">
							<label for="saw_lms_max_attempts"><?php esc_html_e( 'Maximum Attempts', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_max_attempts" 
								name="saw_lms_max_attempts" 
								value="<?php echo esc_attr( $max_attempts ); ?>" 
								min="0" 
								step="1" 
								class="small-text"
							/>
							<span class="saw-lms-unit"><?php esc_html_e( 'attempts', 'saw-lms' ); ?></span>
							<p class="description"><?php esc_html_e( 'Number of times a student can take this quiz (0 = unlimited).', 'saw-lms' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Quiz Behavior meta box
	 *
	 * NEW in v2.4.0: Randomization and answer display settings.
	 *
	 * @since 2.4.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_behavior_meta_box( $post ) {
		// Nonce for security.
		wp_nonce_field( 'saw_lms_quiz_behavior', 'saw_lms_quiz_behavior_nonce' );

		// Get current values.
		$randomize_questions = get_post_meta( $post->ID, '_saw_lms_randomize_questions', true );
		$randomize_answers   = get_post_meta( $post->ID, '_saw_lms_randomize_answers', true );
		$show_correct        = get_post_meta( $post->ID, '_saw_lms_show_correct_answers', true );

		// Defaults.
		$randomize_questions = ! empty( $randomize_questions ) ? 1 : 0;
		$randomize_answers   = ! empty( $randomize_answers ) ? 1 : 0;
		$show_correct        = ! empty( $show_correct ) ? $show_correct : 'after_last_attempt';

		?>
		<div class="saw-lms-meta-box">
			<table class="form-table">
				<tbody>
					<!-- Randomize Questions -->
					<tr>
						<th scope="row">
							<label for="saw_lms_randomize_questions"><?php esc_html_e( 'Randomize Questions', 'saw-lms' ); ?></label>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									id="saw_lms_randomize_questions" 
									name="saw_lms_randomize_questions" 
									value="1" 
									<?php checked( $randomize_questions, 1 ); ?>
								/>
								<?php esc_html_e( 'Display questions in random order for each student', 'saw-lms' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Helps prevent cheating by randomizing question order.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Randomize Answers -->
					<tr>
						<th scope="row">
							<label for="saw_lms_randomize_answers"><?php esc_html_e( 'Randomize Answers', 'saw-lms' ); ?></label>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									id="saw_lms_randomize_answers" 
									name="saw_lms_randomize_answers" 
									value="1" 
									<?php checked( $randomize_answers, 1 ); ?>
								/>
								<?php esc_html_e( 'Display answer choices in random order', 'saw-lms' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Randomizes the order of answer options within each question.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Show Correct Answers -->
					<tr>
						<th scope="row">
							<label for="saw_lms_show_correct_answers"><?php esc_html_e( 'Show Correct Answers', 'saw-lms' ); ?></label>
						</th>
						<td>
							<select id="saw_lms_show_correct_answers" name="saw_lms_show_correct_answers" class="regular-text">
								<option value="immediately" <?php selected( $show_correct, 'immediately' ); ?>>
									<?php esc_html_e( 'Immediately after submission', 'saw-lms' ); ?>
								</option>
								<option value="after_last_attempt" <?php selected( $show_correct, 'after_last_attempt' ); ?>>
									<?php esc_html_e( 'After last attempt only', 'saw-lms' ); ?>
								</option>
								<option value="never" <?php selected( $show_correct, 'never' ); ?>>
									<?php esc_html_e( 'Never show correct answers', 'saw-lms' ); ?>
								</option>
							</select>
							<p class="description"><?php esc_html_e( 'When to display the correct answers to students.', 'saw-lms' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Quiz Assignment meta box
	 *
	 * NEW in v2.4.0: Assign quiz to course and section.
	 *
	 * @since 2.4.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_assignment_meta_box( $post ) {
		// Nonce for security.
		wp_nonce_field( 'saw_lms_quiz_assignment', 'saw_lms_quiz_assignment_nonce' );

		// Get current values.
		$course_id  = get_post_meta( $post->ID, '_saw_lms_course_id', true );
		$section_id = get_post_meta( $post->ID, '_saw_lms_section_id', true );

		// Defaults.
		$course_id  = ! empty( $course_id ) ? $course_id : '';
		$section_id = ! empty( $section_id ) ? $section_id : '';

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

		// Get sections for selected course.
		$sections = array();
		if ( $course_id ) {
			$sections = get_posts(
				array(
					'post_type'      => 'saw_section',
					'posts_per_page' => -1,
					'meta_key'       => '_saw_lms_course_id',
					'meta_value'     => $course_id,
					'orderby'        => 'meta_value_num',
					'meta_key'       => '_saw_lms_section_order',
					'order'          => 'ASC',
					'post_status'    => 'any',
				)
			);
		}

		?>
		<div class="saw-lms-meta-box">
			<!-- Course -->
			<p class="post-attributes-label-wrapper">
				<label class="post-attributes-label" for="saw_lms_quiz_course_id">
					<?php esc_html_e( 'Parent Course', 'saw-lms' ); ?>
				</label>
			</p>
			<select id="saw_lms_quiz_course_id" name="saw_lms_course_id" class="widefat">
				<option value=""><?php esc_html_e( '— Select Course —', 'saw-lms' ); ?></option>
				<?php foreach ( $courses as $course ) : ?>
					<option value="<?php echo absint( $course->ID ); ?>" <?php selected( $course_id, $course->ID ); ?>>
						<?php echo esc_html( $course->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description" style="margin-top: 8px;">
				<?php esc_html_e( 'Optional: Assign this quiz to a course.', 'saw-lms' ); ?>
			</p>

			<!-- Section -->
			<p class="post-attributes-label-wrapper" style="margin-top: 20px;">
				<label class="post-attributes-label" for="saw_lms_quiz_section_id">
					<?php esc_html_e( 'Parent Section', 'saw-lms' ); ?>
				</label>
			</p>
			<select id="saw_lms_quiz_section_id" name="saw_lms_section_id" class="widefat" <?php echo empty( $course_id ) ? 'disabled' : ''; ?>>
				<option value=""><?php esc_html_e( '— Select Section —', 'saw-lms' ); ?></option>
				<?php foreach ( $sections as $section ) : ?>
					<option value="<?php echo absint( $section->ID ); ?>" <?php selected( $section_id, $section->ID ); ?>>
						<?php echo esc_html( $section->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description" style="margin-top: 8px;">
				<?php esc_html_e( 'Optional: Assign to a specific section.', 'saw-lms' ); ?>
			</p>

			<?php if ( empty( $course_id ) ) : ?>
				<p class="saw-lms-info-box" style="background: #fff3cd; border-left: 3px solid #ffc107; padding: 10px; margin-top: 15px; font-size: 12px;">
					<strong><?php esc_html_e( 'Info:', 'saw-lms' ); ?></strong>
					<?php esc_html_e( 'Select a course first to enable section selection.', 'saw-lms' ); ?>
				</p>
			<?php endif; ?>
		</div>

		<script type="text/javascript">
		// Dynamic section loading when course changes
		jQuery(document).ready(function($) {
			$('#saw_lms_quiz_course_id').on('change', function() {
				var courseId = $(this).val();
				var $sectionSelect = $('#saw_lms_quiz_section_id');
				
				if (!courseId) {
					$sectionSelect.prop('disabled', true).html('<option value=""><?php esc_html_e( '— Select Section —', 'saw-lms' ); ?></option>');
					return;
				}
				
				// Enable and show loading
				$sectionSelect.prop('disabled', false).html('<option value=""><?php esc_html_e( 'Loading sections...', 'saw-lms' ); ?></option>');
				
				// AJAX load sections (will be implemented in Phase 3)
				// For now, reload page to show sections
				// TODO: Replace with AJAX in Phase 3
			});
		});
		</script>
		<?php
	}

	/**
	 * Render Quiz Questions placeholder meta box
	 *
	 * Placeholder for Quiz Builder UI (Phase 4).
	 *
	 * @since 2.4.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_questions_meta_box( $post ) {
		?>
		<div class="saw-lms-placeholder-box" style="text-align: center; padding: 40px 20px; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px;">
			<span class="dashicons dashicons-editor-help" style="font-size: 64px; color: #ccc; display: block; margin-bottom: 20px;"></span>
			<h3 style="margin: 0 0 10px 0; color: #666;"><?php esc_html_e( 'Quiz Builder Coming Soon', 'saw-lms' ); ?></h3>
			<p style="margin: 0; color: #999; font-size: 14px;">
				<?php esc_html_e( 'The Quiz Builder interface will be available in Phase 4.', 'saw-lms' ); ?>
			</p>
			<p style="margin: 10px 0 0 0; color: #999; font-size: 14px;">
				<?php esc_html_e( 'Questions will be added and managed here using drag & drop interface.', 'saw-lms' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render Quiz Statistics meta box
	 *
	 * Read-only statistics (will be populated in later phases).
	 *
	 * @since 2.4.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_stats_meta_box( $post ) {
		// Placeholder stats - will be populated from database in Phase 17.
		$total_attempts  = 0;
		$success_rate    = 0;
		$average_score   = 0;
		$question_count  = 0;

		?>
		<div class="saw-lms-stats-box">
			<div class="saw-lms-stat-item" style="padding: 10px 0; border-bottom: 1px solid #eee;">
				<strong><?php esc_html_e( 'Total Attempts:', 'saw-lms' ); ?></strong>
				<span style="float: right; color: #2196F3; font-weight: bold;"><?php echo absint( $total_attempts ); ?></span>
			</div>
			<div class="saw-lms-stat-item" style="padding: 10px 0; border-bottom: 1px solid #eee;">
				<strong><?php esc_html_e( 'Success Rate:', 'saw-lms' ); ?></strong>
				<span style="float: right; color: #4CAF50; font-weight: bold;"><?php echo absint( $success_rate ); ?>%</span>
			</div>
			<div class="saw-lms-stat-item" style="padding: 10px 0; border-bottom: 1px solid #eee;">
				<strong><?php esc_html_e( 'Average Score:', 'saw-lms' ); ?></strong>
				<span style="float: right; color: #FF9800; font-weight: bold;"><?php echo absint( $average_score ); ?>%</span>
			</div>
			<div class="saw-lms-stat-item" style="padding: 10px 0;">
				<strong><?php esc_html_e( 'Questions:', 'saw-lms' ); ?></strong>
				<span style="float: right; color: #666; font-weight: bold;"><?php echo absint( $question_count ); ?></span>
			</div>
			<p class="description" style="margin-top: 15px; font-size: 12px; color: #999;">
				<?php esc_html_e( 'Statistics will be populated once students start taking this quiz.', 'saw-lms' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * UPDATED in v2.4.0: Complete save logic for all meta boxes.
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
		if ( isset( $_POST['saw_lms_quiz_settings_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_quiz_settings_nonce'] ) ), 'saw_lms_quiz_settings' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Save passing score (0-100).
			if ( isset( $_POST['saw_lms_passing_score_percent'] ) ) {
				$passing_score = absint( $_POST['saw_lms_passing_score_percent'] );
				$passing_score = max( 0, min( 100, $passing_score ) ); // Clamp to 0-100
				update_post_meta( $post_id, '_saw_lms_passing_score_percent', $passing_score );
			}

			// Save time limit (0 = unlimited).
			if ( isset( $_POST['saw_lms_time_limit_minutes'] ) ) {
				$time_limit = absint( $_POST['saw_lms_time_limit_minutes'] );
				update_post_meta( $post_id, '_saw_lms_time_limit_minutes', $time_limit );
			}

			// Save max attempts (0 = unlimited).
			if ( isset( $_POST['saw_lms_max_attempts'] ) ) {
				$max_attempts = absint( $_POST['saw_lms_max_attempts'] );
				update_post_meta( $post_id, '_saw_lms_max_attempts', $max_attempts );
			}
		}

		// --- SAVE BEHAVIOR META BOX ---
		if ( isset( $_POST['saw_lms_quiz_behavior_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_quiz_behavior_nonce'] ) ), 'saw_lms_quiz_behavior' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Save randomize questions.
			$randomize_questions = isset( $_POST['saw_lms_randomize_questions'] ) ? 1 : 0;
			update_post_meta( $post_id, '_saw_lms_randomize_questions', $randomize_questions );

			// Save randomize answers.
			$randomize_answers = isset( $_POST['saw_lms_randomize_answers'] ) ? 1 : 0;
			update_post_meta( $post_id, '_saw_lms_randomize_answers', $randomize_answers );

			// Save show correct answers.
			if ( isset( $_POST['saw_lms_show_correct_answers'] ) ) {
				$show_correct = sanitize_text_field( wp_unslash( $_POST['saw_lms_show_correct_answers'] ) );
				$allowed      = array( 'immediately', 'after_last_attempt', 'never' );
				$show_correct = in_array( $show_correct, $allowed, true ) ? $show_correct : 'after_last_attempt';
				update_post_meta( $post_id, '_saw_lms_show_correct_answers', $show_correct );
			}
		}

		// --- SAVE ASSIGNMENT META BOX ---
		if ( isset( $_POST['saw_lms_quiz_assignment_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_quiz_assignment_nonce'] ) ), 'saw_lms_quiz_assignment' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Save course ID.
			if ( isset( $_POST['saw_lms_course_id'] ) ) {
				$course_id = absint( $_POST['saw_lms_course_id'] );
				
				// Validate course exists.
				if ( $course_id > 0 && SAW_LMS_Course::course_exists( $course_id ) ) {
					update_post_meta( $post_id, '_saw_lms_course_id', $course_id );
				} else {
					delete_post_meta( $post_id, '_saw_lms_course_id' );
				}
			}

			// Save section ID.
			if ( isset( $_POST['saw_lms_section_id'] ) ) {
				$section_id = absint( $_POST['saw_lms_section_id'] );
				
				// Validate section exists.
				if ( $section_id > 0 && SAW_LMS_Section::section_exists( $section_id ) ) {
					update_post_meta( $post_id, '_saw_lms_section_id', $section_id );
				} else {
					delete_post_meta( $post_id, '_saw_lms_section_id' );
				}
			}
		}

		/**
		 * Fires after quiz meta is saved.
		 *
		 * @since 2.4.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		do_action( 'saw_lms_quiz_meta_saved', $post_id, $post );
	}

	/**
	 * Add admin columns
	 *
	 * UPDATED in v2.4.0: Added course, section, question count, success rate columns.
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
		$columns['section']       = __( 'Section', 'saw-lms' );
		$columns['question_count'] = __( 'Questions', 'saw-lms' );
		$columns['passing_score'] = __( 'Pass %', 'saw-lms' );
		$columns['success_rate']  = __( 'Success Rate', 'saw-lms' );

		// Re-add date column at the end.
		$columns['date'] = $date;

		return $columns;
	}

	/**
	 * Render admin column content
	 *
	 * UPDATED in v2.4.0: Render all custom columns.
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
					echo '<span style="color: #999;">' . esc_html__( 'Not assigned', 'saw-lms' ) . '</span>';
				}
				break;

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
					echo '<span style="color: #999;">' . esc_html__( 'Not assigned', 'saw-lms' ) . '</span>';
				}
				break;

			case 'question_count':
				// Placeholder - will be populated from Question Bank in Phase 4
				echo '<span style="color: #666;">0</span>';
				break;

			case 'passing_score':
				$passing_score = get_post_meta( $post_id, '_saw_lms_passing_score_percent', true );
				if ( $passing_score ) {
					echo '<strong style="color: #2196F3;">' . absint( $passing_score ) . '%</strong>';
				} else {
					echo '—';
				}
				break;

			case 'success_rate':
				// Placeholder - will be calculated from attempts in Phase 17
				echo '<span style="color: #999;">—</span>';
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
		$columns['passing_score'] = 'passing_score';
		return $columns;
	}

	/**
	 * Get quiz by ID (helper method)
	 *
	 * NEW in v2.4.0.
	 *
	 * @since 2.4.0
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
	 * NEW in v2.4.0.
	 *
	 * @since 2.4.0
	 * @param int $quiz_id Quiz post ID.
	 * @return bool True if quiz exists, false otherwise.
	 */
	public static function quiz_exists( $quiz_id ) {
		return null !== self::get_quiz( $quiz_id );
	}
}