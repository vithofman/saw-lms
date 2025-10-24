<?php
/**
 * Course Settings Page - Separate Admin Screen
 *
 * Creates a dedicated full-width admin page for Course Details,
 * separate from the Gutenberg editor.
 *
 * @package    SAW_LMS
 * @subpackage Admin
 * @since      3.3.0
 * @version    3.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Course_Settings_Page Class
 *
 * Provides a clean, full-width admin page for editing Course Details
 * without Gutenberg layout conflicts.
 *
 * @since 3.3.0
 */
class SAW_LMS_Course_Settings_Page {

	/**
	 * Singleton instance
	 *
	 * @var SAW_LMS_Course_Settings_Page|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return SAW_LMS_Course_Settings_Page
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
	 * @since 3.3.0
	 */
	private function __construct() {
		// Add admin bar menu item
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_link' ), 100 );

		// Hide Course Details meta box on edit screen
		add_action( 'add_meta_boxes', array( $this, 'hide_course_details_metabox' ), 999 );

		// Register admin page
		add_action( 'admin_menu', array( $this, 'register_admin_page' ), 999 );

		// Save settings
		add_action( 'admin_post_saw_lms_save_course_settings', array( $this, 'save_course_settings' ) );
	}

	/**
	 * Add link to admin bar for quick switching
	 *
	 * @since 3.3.0
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function add_admin_bar_link( $wp_admin_bar ) {
		// Only on Course edit screen
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== 'saw_course' ) {
			return;
		}

		$post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;

		// If on edit screen, show link to Settings
		if ( $screen->base === 'post' && $post_id > 0 ) {
			$wp_admin_bar->add_node(
				array(
					'id'    => 'saw_course_settings',
					'title' => '⚙️ Course Settings',
					'href'  => admin_url( 'admin.php?page=saw-course-settings&post=' . $post_id ),
					'meta'  => array(
						'class' => 'saw-course-settings-link',
						'title' => __( 'Edit Course Settings', 'saw-lms' ),
					),
				)
			);
		}

		// If on Settings page, show link back to Editor
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'saw-course-settings' && $post_id > 0 ) {
			$wp_admin_bar->add_node(
				array(
					'id'    => 'saw_course_editor',
					'title' => '✏️ Content Editor',
					'href'  => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
					'meta'  => array(
						'class' => 'saw-course-editor-link',
						'title' => __( 'Edit Course Content', 'saw-lms' ),
					),
				)
			);
		}
	}

	/**
	 * Hide Course Details meta box on edit screen
	 *
	 * Since we have a separate page, we don't need it cluttering Gutenberg.
	 *
	 * @since 3.3.0
	 */
	public function hide_course_details_metabox() {
		remove_meta_box( 'saw_lms_course_details', 'saw_course', 'normal' );
	}

	/**
	 * Register admin page (hidden from menu)
	 *
	 * @since 3.3.0
	 */
	public function register_admin_page() {
		// Hidden page (no menu item)
		add_submenu_page(
			null, // No parent = hidden
			__( 'Course Settings', 'saw-lms' ),
			__( 'Course Settings', 'saw-lms' ),
			'edit_saw_courses',
			'saw-course-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page
	 *
	 * @since 3.3.0
	 */
	public function render_settings_page() {
		// Get post ID
		$post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;

		if ( ! $post_id ) {
			wp_die( __( 'Invalid course ID.', 'saw-lms' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( __( 'You do not have permission to edit this course.', 'saw-lms' ) );
		}

		// Get post
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'saw_course' ) {
			wp_die( __( 'Invalid course.', 'saw-lms' ) );
		}

		// Load tabs config (same as meta box)
		$config_dir = SAW_LMS_PLUGIN_DIR . 'includes/post-types/configs/';

		$tabs_config = array();

		// Load Settings tab
		$settings_file = $config_dir . 'course-settings-fields.php';
		if ( file_exists( $settings_file ) ) {
			$settings_fields = include $settings_file;
			if ( is_array( $settings_fields ) && ! empty( $settings_fields ) ) {
				$tabs_config['settings'] = array(
					'label'  => __( 'Settings', 'saw-lms' ),
					'fields' => $settings_fields,
				);
			}
		}

		// Load Builder tab
		$builder_file = $config_dir . 'course-builder-fields.php';
		if ( file_exists( $builder_file ) ) {
			$builder_fields = include $builder_file;
			if ( is_array( $builder_fields ) && ! empty( $builder_fields ) ) {
				$tabs_config['builder'] = array(
					'label'  => __( 'Builder', 'saw-lms' ),
					'fields' => $builder_fields,
				);
			}
		}

		// Load Stats tab
		$stats_file = $config_dir . 'course-stats-fields.php';
		if ( file_exists( $stats_file ) ) {
			$stats_fields = include $stats_file;
			if ( is_array( $stats_fields ) && ! empty( $stats_fields ) ) {
				$tabs_config['stats'] = array(
					'label'  => __( 'Stats', 'saw-lms' ),
					'fields' => $stats_fields,
				);
			}
		}

		// Calculate stats
		$this->calculate_course_stats( $post_id );

		?>
		<div class="wrap saw-course-settings-page">
			<h1 class="wp-heading-inline">
				<?php echo esc_html( get_the_title( $post_id ) ); ?>
				<span class="subtitle"><?php esc_html_e( '— Course Settings', 'saw-lms' ); ?></span>
			</h1>

			<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ); ?>" class="page-title-action">
				<?php esc_html_e( '← Back to Content Editor', 'saw-lms' ); ?>
			</a>

			<hr class="wp-header-end">

			<?php if ( isset( $_GET['saved'] ) && $_GET['saved'] === '1' ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Course settings saved successfully!', 'saw-lms' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="saw-course-settings-form">
				<?php wp_nonce_field( 'saw_lms_course_settings', 'saw_lms_course_settings_nonce' ); ?>
				<input type="hidden" name="action" value="saw_lms_save_course_settings">
				<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">

				<div class="saw-course-settings-content">
					<?php
					// Render tabs using Meta Box Helper
					SAW_LMS_Meta_Box_Helper::render_tabbed_meta_box( $post_id, $tabs_config );
					?>
				</div>

				<div class="saw-course-settings-footer">
					<?php submit_button( __( 'Save Course Settings', 'saw-lms' ), 'primary', 'submit', false ); ?>
					<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Cancel', 'saw-lms' ); ?>
					</a>
				</div>
			</form>
		</div>

		<style>
		.saw-course-settings-page {
			max-width: 1200px;
			margin: 0 auto;
		}
		.saw-course-settings-page .subtitle {
			color: #646970;
			font-size: 0.9em;
			font-weight: normal;
		}
		.saw-course-settings-content {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			padding: 0;
			margin-top: 20px;
		}
		.saw-course-settings-footer {
			margin-top: 20px;
			padding: 15px 0;
			border-top: 1px solid #c3c4c7;
		}
		.saw-course-settings-footer .button {
			margin-right: 10px;
		}
		</style>
		<?php
	}

	/**
	 * Save course settings
	 *
	 * @since 3.3.0
	 */
	public function save_course_settings() {
		// Verify nonce
		if ( ! isset( $_POST['saw_lms_course_settings_nonce'] ) ||
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_course_settings_nonce'] ) ), 'saw_lms_course_settings' ) ) {
			wp_die( __( 'Security check failed.', 'saw-lms' ) );
		}

		// Get post ID
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_die( __( 'Invalid course ID.', 'saw-lms' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( __( 'You do not have permission to edit this course.', 'saw-lms' ) );
		}

		// Load tabs config
		$config_dir = SAW_LMS_PLUGIN_DIR . 'includes/post-types/configs/';

		$tabs_config = array();

		$settings_file = $config_dir . 'course-settings-fields.php';
		if ( file_exists( $settings_file ) ) {
			$settings_fields = include $settings_file;
			if ( is_array( $settings_fields ) ) {
				$tabs_config['settings'] = array( 'fields' => $settings_fields );
			}
		}

		$builder_file = $config_dir . 'course-builder-fields.php';
		if ( file_exists( $builder_file ) ) {
			$builder_fields = include $builder_file;
			if ( is_array( $builder_fields ) ) {
				$tabs_config['builder'] = array( 'fields' => $builder_fields );
			}
		}

		// Save fields (same logic as class-course.php)
		foreach ( $tabs_config as $tab_config ) {
			if ( empty( $tab_config['fields'] ) ) {
				continue;
			}

			foreach ( $tab_config['fields'] as $key => $field ) {
				// Skip readonly fields
				if ( ! empty( $field['readonly'] ) ) {
					continue;
				}

				// Skip heading fields
				if ( isset( $field['type'] ) && 'heading' === $field['type'] ) {
					continue;
				}

				// Handle field value
				if ( isset( $_POST[ $key ] ) ) {
					$value = SAW_LMS_Meta_Box_Helper::sanitize_value(
						wp_unslash( $_POST[ $key ] ),
						isset( $field['type'] ) ? $field['type'] : 'text'
					);

					update_post_meta( $post_id, $key, $value );
				} else {
					// Checkbox unchecked = empty string
					if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
						update_post_meta( $post_id, $key, '' );
					}
				}
			}
		}

		// Invalidate cache
		wp_cache_delete( 'course_data_' . $post_id, 'saw_lms_courses' );

		// Redirect back with success message
		$redirect_url = add_query_arg(
			array(
				'page'  => 'saw-course-settings',
				'post'  => $post_id,
				'saved' => '1',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Calculate course statistics
	 *
	 * Same as in class-course.php
	 *
	 * @since 3.3.0
	 * @param int $post_id Post ID.
	 */
	private function calculate_course_stats( $post_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'saw_lms_enrollments';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		if ( ! $table_exists ) {
			update_post_meta( $post_id, '_saw_lms_total_enrollments', 0 );
			update_post_meta( $post_id, '_saw_lms_active_enrollments', 0 );
			update_post_meta( $post_id, '_saw_lms_completions', 0 );
			update_post_meta( $post_id, '_saw_lms_completion_rate', '0%' );
			return;
		}

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

		$completion_rate = $total_enrollments > 0
			? round( ( $completions / $total_enrollments ) * 100, 2 ) . '%'
			: '0%';

		update_post_meta( $post_id, '_saw_lms_total_enrollments', $total_enrollments );
		update_post_meta( $post_id, '_saw_lms_active_enrollments', $active_enrollments );
		update_post_meta( $post_id, '_saw_lms_completions', $completions );
		update_post_meta( $post_id, '_saw_lms_completion_rate', $completion_rate );
	}
}