<?php
/**
 * Quiz Custom Post Type
 *
 * Handles registration and functionality for the Quiz CPT.
 * The Quiz Builder UI will be implemented in later phases.
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
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'saw-lms',
			'menu_position'       => 28,
			'menu_icon'           => 'dashicons-clipboard',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
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

		// Quiz Stats meta box.
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
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_settings_meta_box( $post ) {
		// Nonce for security.
		wp_nonce_field( 'saw_lms_quiz_settings', 'saw_lms_quiz_settings_nonce' );

		// Get current values.
		$section_id       = get_post_meta( $post->ID, '_saw_lms_section_id', true );
		$quiz_order       = get_post_meta( $post->ID, '_saw_lms_quiz_order', true );
		$pass_percentage  = get_post_meta( $post->ID, '_saw_lms_pass_percentage', true );
		$time_limit       = get_post_meta( $post->ID, '_saw_lms_time_limit', true );
		$max_attempts     = get_post_meta( $post->ID, '_saw_lms_max_attempts', true );
		$randomize        = get_post_meta( $post->ID, '_saw_lms_randomize_questions', true );
		$show_answers     = get_post_meta( $post->ID, '_saw_lms_show_answers', true );

		// Defaults.
		$section_id       = ! empty( $section_id ) ? $section_id : '';
		$quiz_order       = ! empty( $quiz_order ) ? $quiz_order : 0;
		$pass_percentage  = ! empty( $pass_percentage ) ? $pass_percentage : 70;
		$time_limit       = ! empty( $time_limit ) ? $time_limit : 0;
		$max_attempts     = ! empty( $max_attempts ) ? $max_attempts : 0;
		$randomize        = ! empty( $randomize ) ? 1 : 0;
		$show_answers     = ! empty( $show_answers ) ? 1 : 0;

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
							<p class="description"><?php esc_html_e( 'Select the section this quiz belongs to.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Quiz Order -->
					<tr>
						<th scope="row">
							<label for="saw_lms_quiz_order"><?php esc_html_e( 'Quiz Order', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_quiz_order" 
								name="saw_lms_quiz_order" 
								value="<?php echo esc_attr( $quiz_order ); ?>" 
								min="0" 
								step="1" 
								class="small-text"
							/>
							<p class="description"><?php esc_html_e( 'Display order within the section (0 = first). Usually placed after lessons.', 'saw-lms' ); ?></p>
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
							<p class="description"><?php esc_html_e( 'Minimum percentage required to pass this quiz.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Time Limit -->
					<tr>
						<th scope="row">
							<label for="saw_lms_time_limit"><?php esc_html_e( 'Time Limit (minutes)', 'saw-lms' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="saw_lms_time_limit" 
								name="saw_lms_time_limit" 
								value="<?php echo esc_attr( $time_limit ); ?>" 
								min="0" 
								step="1" 
								class="small-text"
							/>
							<p class="description"><?php esc_html_e( 'Time limit for completing the quiz. 0 = no limit.', 'saw-lms' ); ?></p>
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
							<p class="description"><?php esc_html_e( 'Maximum number of attempts allowed. 0 = unlimited.', 'saw-lms' ); ?></p>
						</td>
					</tr>

					<!-- Randomize Questions -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Randomize Questions', 'saw-lms' ); ?>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									id="saw_lms_randomize_questions" 
									name="saw_lms_randomize_questions" 
									value="1" 
									<?php checked( $randomize, 1 ); ?>
								/>
								<?php esc_html_e( 'Randomize question order for each attempt', 'saw-lms' ); ?>
							</label>
						</td>
					</tr>

					<!-- Show Answers After Submission -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Show Answers', 'saw-lms' ); ?>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									id="saw_lms_show_answers" 
									name="saw_lms_show_answers" 
									value="1" 
									<?php checked( $show_answers, 1 ); ?>
								/>
								<?php esc_html_e( 'Show correct answers after quiz submission', 'saw-lms' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Quiz Stats meta box
	 *
	 * @since 2.1.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_stats_meta_box( $post ) {
		// Get stats from database (cache-ready).
		$stats = $this->get_quiz_stats( $post->ID );

		?>
		<div class="saw-lms-stats">
			<p>
				<strong><?php esc_html_e( 'Total Attempts:', 'saw-lms' ); ?></strong> 
				<span><?php echo absint( $stats['attempts'] ); ?></span>
			</p>
			<p>
				<strong><?php esc_html_e( 'Passed:', 'saw-lms' ); ?></strong> 
				<span><?php echo absint( $stats['passed'] ); ?></span>
			</p>
			<p>
				<strong><?php esc_html_e( 'Failed:', 'saw-lms' ); ?></strong> 
				<span><?php echo absint( $stats['failed'] ); ?></span>
			</p>
			<p>
				<strong><?php esc_html_e( 'Pass Rate:', 'saw-lms' ); ?></strong> 
				<span><?php echo esc_html( $stats['pass_rate'] ); ?>%</span>
			</p>
			<p>
				<strong><?php esc_html_e( 'Average Score:', 'saw-lms' ); ?></strong> 
				<span><?php echo esc_html( $stats['average_score'] ); ?>%</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Get quiz statistics (cache-ready)
	 *
	 * @since 2.1.0
	 * @param int $quiz_id Quiz post ID.
	 * @return array Quiz statistics.
	 */
	private function get_quiz_stats( $quiz_id ) {
		// Cache key.
		$cache_key = 'quiz_stats_' . $quiz_id;

		// Try cache first.
		$stats = wp_cache_get( $cache_key, 'saw_lms_quizzes' );

		if ( false === $stats ) {
			global $wpdb;

			// Get total attempts.
			$attempts = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_quiz_attempts WHERE quiz_id = %d",
					$quiz_id
				)
			);

			// Get passed attempts.
			$passed = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_quiz_attempts WHERE quiz_id = %d AND passed = 1",
					$quiz_id
				)
			);

			// Calculate failed.
			$failed = $attempts - $passed;

			// Calculate pass rate.
			$pass_rate = ( $attempts > 0 ) ? round( ( $passed / $attempts ) * 100, 1 ) : 0;

			// Get average score.
			$average_score = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(score) FROM {$wpdb->prefix}saw_lms_quiz_attempts WHERE quiz_id = %d",
					$quiz_id
				)
			);
			$average_score = $average_score ? round( $average_score, 1 ) : 0;

			$stats = array(
				'attempts'      => absint( $attempts ),
				'passed'        => absint( $passed ),
				'failed'        => absint( $failed ),
				'pass_rate'     => $pass_rate,
				'average_score' => $average_score,
			);

			// Cache for 5 minutes.
			wp_cache_set( $cache_key, $stats, 'saw_lms_quizzes', 300 );
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
		if ( ! isset( $_POST['saw_lms_quiz_settings_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_quiz_settings_nonce'] ) ), 'saw_lms_quiz_settings' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save section ID.
		if ( isset( $_POST['saw_lms_section_id'] ) ) {
			$section_id = absint( $_POST['saw_lms_section_id'] );
			if ( SAW_LMS_Section::section_exists( $section_id ) ) {
				update_post_meta( $post_id, '_saw_lms_section_id', $section_id );
			}
		}

		// Save quiz order.
		if ( isset( $_POST['saw_lms_quiz_order'] ) ) {
			$quiz_order = absint( $_POST['saw_lms_quiz_order'] );
			update_post_meta( $post_id, '_saw_lms_quiz_order', $quiz_order );
		}

		// Save pass percentage.
		if ( isset( $_POST['saw_lms_pass_percentage'] ) ) {
			$pass_percentage = absint( $_POST['saw_lms_pass_percentage'] );
			$pass_percentage = max( 0, min( 100, $pass_percentage ) );
			update_post_meta( $post_id, '_saw_lms_pass_percentage', $pass_percentage );
		}

		// Save time limit.
		if ( isset( $_POST['saw_lms_time_limit'] ) ) {
			$time_limit = absint( $_POST['saw_lms_time_limit'] );
			update_post_meta( $post_id, '_saw_lms_time_limit', $time_limit );
		}

		// Save max attempts.
		if ( isset( $_POST['saw_lms_max_attempts'] ) ) {
			$max_attempts = absint( $_POST['saw_lms_max_attempts'] );
			update_post_meta( $post_id, '_saw_lms_max_attempts', $max_attempts );
		}

		// Save randomize setting.
		$randomize = isset( $_POST['saw_lms_randomize_questions'] ) ? 1 : 0;
		update_post_meta( $post_id, '_saw_lms_randomize_questions', $randomize );

		// Save show answers setting.
		$show_answers = isset( $_POST['saw_lms_show_answers'] ) ? 1 : 0;
		update_post_meta( $post_id, '_saw_lms_show_answers', $show_answers );

		// Invalidate cache.
		wp_cache_delete( 'quiz_stats_' . $post_id, 'saw_lms_quizzes' );

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
		$date = $columns['date'];
		unset( $columns['date'] );

		$columns['section']         = __( 'Section', 'saw-lms' );
		$columns['pass_percentage'] = __( 'Pass %', 'saw-lms' );
		$columns['attempts']        = __( 'Attempts', 'saw-lms' );
		$columns['pass_rate']       = __( 'Pass Rate', 'saw-lms' );

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

			case 'pass_percentage':
				$pass_percentage = get_post_meta( $post_id, '_saw_lms_pass_percentage', true );
				echo '<strong>' . absint( $pass_percentage ) . '%</strong>';
				break;

			case 'attempts':
				$stats = $this->get_quiz_stats( $post_id );
				echo '<span class="saw-lms-count">' . absint( $stats['attempts'] ) . '</span>';
				break;

			case 'pass_rate':
				$stats = $this->get_quiz_stats( $post_id );
				$class = $stats['pass_rate'] >= 70 ? 'saw-lms-success' : 'saw-lms-warning';
				echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $stats['pass_rate'] ) . '%</span>';
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
		$columns['pass_percentage'] = 'pass_percentage';
		return $columns;
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

	/**
	 * Get section quizzes (cache-ready)
	 *
	 * @since 2.1.0
	 * @param int $section_id Section post ID.
	 * @return WP_Post[] Array of quiz posts.
	 */
	public static function get_section_quizzes( $section_id ) {
		$cache_key = 'section_quizzes_' . $section_id;
		$quizzes   = wp_cache_get( $cache_key, 'saw_lms_sections' );

		if ( false === $quizzes ) {
			$quizzes = get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'posts_per_page' => -1,
					'meta_key'       => '_saw_lms_section_id',
					'meta_value'     => $section_id,
					'orderby'        => 'meta_value_num',
					'meta_key'       => '_saw_lms_quiz_order',
					'order'          => 'ASC',
					'post_status'    => 'publish',
				)
			);

			// Cache for 5 minutes.
			wp_cache_set( $cache_key, $quizzes, 'saw_lms_sections', 300 );
		}

		return $quizzes;
	}
}