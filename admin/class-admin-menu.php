<?php
/**
 * Admin Menu
 *
 * Vytvoření hierarchického menu v administraci podle Master Development Planu v15.0.
 * Používá nový design system (Fáze 1.9).
 *
 * PHASE 2.5: Unified admin menu structure with submenu groups.
 * All CPTs are organized under main menu with proper icons and hierarchy.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/admin
 * @since      1.0.0
 * @version    2.5.2
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Admin_Menu Class
 *
 * Spravuje admin menu s hierarchickou strukturou.
 *
 * @since 1.0.0
 */
class SAW_LMS_Admin_Menu {

	/**
	 * Plugin name
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private $version;

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @param string $plugin_name Plugin name
	 * @param string $version     Plugin version
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Add admin menu pages
	 *
	 * @since 2.5.0
	 */
	public function add_menu() {
		// Hlavní menu
		add_menu_page(
			__( 'SAW LMS', 'saw-lms' ),
			__( 'SAW LMS', 'saw-lms' ),
			'manage_options',
			'saw-lms',
			array( $this, 'display_dashboard' ),
			'dashicons-book-alt',
			30
		);

		// Dashboard
		add_submenu_page(
			'saw-lms',
			__( 'Dashboard', 'saw-lms' ),
			'<span class="dashicons dashicons-dashboard" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px;"></span>' . __( 'Dashboard', 'saw-lms' ),
			'manage_options',
			'saw-lms',
			array( $this, 'display_dashboard' )
		);

		// Courses
		if ( post_type_exists( 'saw_course' ) ) {
			add_submenu_page(
				'saw-lms',
				__( 'Courses', 'saw-lms' ),
				'<span class="dashicons dashicons-book" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px;"></span>' . __( 'Courses', 'saw-lms' ),
				'edit_posts',
				'edit.php?post_type=saw_course'
			);
		}

		// Sections
		if ( post_type_exists( 'saw_section' ) ) {
			add_submenu_page(
				'saw-lms',
				__( 'Sections', 'saw-lms' ),
				'<span class="dashicons dashicons-editor-justify" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px;"></span>' . __( 'Sections', 'saw-lms' ),
				'edit_posts',
				'edit.php?post_type=saw_section'
			);
		}

		// Lessons
		if ( post_type_exists( 'saw_lesson' ) ) {
			add_submenu_page(
				'saw-lms',
				__( 'Lessons', 'saw-lms' ),
				'<span class="dashicons dashicons-media-document" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px;"></span>' . __( 'Lessons', 'saw-lms' ),
				'edit_posts',
				'edit.php?post_type=saw_lesson'
			);
		}

		// Quizzes
		if ( post_type_exists( 'saw_quiz' ) ) {
			add_submenu_page(
				'saw-lms',
				__( 'Quizzes', 'saw-lms' ),
				'<span class="dashicons dashicons-editor-help" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px;"></span>' . __( 'Quizzes', 'saw-lms' ),
				'edit_posts',
				'edit.php?post_type=saw_quiz'
			);
		}

		// Question Bank
		add_submenu_page(
			'saw-lms',
			__( 'Question Bank', 'saw-lms' ),
			'<span class="dashicons dashicons-database" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px; opacity: 0.5;"></span>' . __( 'Question Bank', 'saw-lms' ) . ' <span class="awaiting-mod">🔜</span>',
			'manage_options',
			'saw-lms-question-bank',
			array( $this, 'display_coming_soon_page' )
		);

		// Groups
		add_submenu_page(
			'saw-lms',
			__( 'Groups', 'saw-lms' ),
			'<span class="dashicons dashicons-groups" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px; opacity: 0.5;"></span>' . __( 'Groups', 'saw-lms' ) . ' <span class="awaiting-mod">🔜</span>',
			'manage_options',
			'saw-lms-groups',
			array( $this, 'display_coming_soon_page' )
		);

		// Reports
		add_submenu_page(
			'saw-lms',
			__( 'Reports', 'saw-lms' ),
			'<span class="dashicons dashicons-chart-bar" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px; opacity: 0.5;"></span>' . __( 'Reports', 'saw-lms' ) . ' <span class="awaiting-mod">🔜</span>',
			'manage_options',
			'saw-lms-reports',
			array( $this, 'display_coming_soon_page' )
		);

		// Settings
		add_submenu_page(
			'saw-lms',
			__( 'Settings', 'saw-lms' ),
			'<span class="dashicons dashicons-admin-settings" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px; opacity: 0.5;"></span>' . __( 'Settings', 'saw-lms' ) . ' <span class="awaiting-mod">🔜</span>',
			'manage_options',
			'saw-lms-settings',
			array( $this, 'display_coming_soon_page' )
		);

		// Plugin Info
		add_submenu_page(
			'saw-lms',
			__( 'Plugin Info', 'saw-lms' ),
			'<span class="dashicons dashicons-info-outline" style="font-size: 17px; width: 17px; height: 17px; margin-right: 5px;"></span>' . __( 'Plugin Info', 'saw-lms' ),
			'manage_options',
			'saw-lms-info',
			array( $this, 'display_info_page' )
		);
	}

	/**
	 * Display Dashboard page
	 *
	 * @since 1.0.0
	 */
	public function display_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'saw-lms' ) );
		}

		$stats = $this->get_dashboard_stats();

		$cpt_registered = post_type_exists( 'saw_course' );
		?>
		<div class="wrap saw-admin-page">
			<div class="saw-page-header">
				<div class="saw-page-title-wrapper">
					<h1 class="saw-page-title">
						<?php esc_html_e( 'SAW LMS Dashboard', 'saw-lms' ); ?>
					</h1>
					<p class="saw-page-subtitle">
						<?php esc_html_e( 'Welcome to your Learning Management System', 'saw-lms' ); ?>
					</p>
				</div>
			</div>

			<div class="saw-dashboard-grid">
				<div class="saw-card">
					<div class="saw-card-header">
						<h3><?php esc_html_e( 'Courses', 'saw-lms' ); ?></h3>
					</div>
					<div class="saw-card-body">
						<div style="font-size: 48px; font-weight: bold; color: var(--saw-primary);">
							<?php echo esc_html( $stats['courses'] ); ?>
						</div>
						<p><?php esc_html_e( 'Total Courses', 'saw-lms' ); ?></p>
					</div>
				</div>

				<div class="saw-card">
					<div class="saw-card-header">
						<h3><?php esc_html_e( 'Sections', 'saw-lms' ); ?></h3>
					</div>
					<div class="saw-card-body">
						<div style="font-size: 48px; font-weight: bold; color: var(--saw-success);">
							<?php echo esc_html( $stats['sections'] ); ?>
						</div>
						<p><?php esc_html_e( 'Total Sections', 'saw-lms' ); ?></p>
					</div>
				</div>

				<div class="saw-card">
					<div class="saw-card-header">
						<h3><?php esc_html_e( 'Lessons', 'saw-lms' ); ?></h3>
					</div>
					<div class="saw-card-body">
						<div style="font-size: 48px; font-weight: bold; color: var(--saw-warning);">
							<?php echo esc_html( $stats['lessons'] ); ?>
						</div>
						<p><?php esc_html_e( 'Total Lessons', 'saw-lms' ); ?></p>
					</div>
				</div>

				<div class="saw-card">
					<div class="saw-card-header">
						<h3><?php esc_html_e( 'Quizzes', 'saw-lms' ); ?></h3>
					</div>
					<div class="saw-card-body">
						<div style="font-size: 48px; font-weight: bold; color: var(--saw-info);">
							<?php echo esc_html( $stats['quizzes'] ); ?>
						</div>
						<p><?php esc_html_e( 'Total Quizzes', 'saw-lms' ); ?></p>
					</div>
				</div>
			</div>

			<div class="saw-card" style="margin-top: 20px;">
				<div class="saw-card-header">
					<h3><?php esc_html_e( 'Quick Actions', 'saw-lms' ); ?></h3>
				</div>
				<div class="saw-card-body">
					<?php if ( $cpt_registered ) : ?>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=saw_course' ) ); ?>" class="saw-btn saw-btn-primary">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php esc_html_e( 'Create Course', 'saw-lms' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=saw_course' ) ); ?>" class="saw-btn saw-btn-secondary">
							<span class="dashicons dashicons-list-view"></span>
							<?php esc_html_e( 'View All Courses', 'saw-lms' ); ?>
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=saw-lms-info' ) ); ?>" class="saw-btn saw-btn-secondary">
						<span class="dashicons dashicons-info"></span>
						<?php esc_html_e( 'Plugin Info', 'saw-lms' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display Coming Soon page
	 *
	 * @since 2.5.0
	 */
	public function display_coming_soon_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'saw-lms' ) );
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		
		$titles = array(
			'saw-lms-question-bank' => array(
				'title' => __( 'Question Bank', 'saw-lms' ),
				'icon'  => 'dashicons-database',
				'phase' => '4',
				'desc'  => __( 'Create and manage reusable quiz questions.', 'saw-lms' ),
			),
			'saw-lms-groups'        => array(
				'title' => __( 'Groups', 'saw-lms' ),
				'icon'  => 'dashicons-groups',
				'phase' => '9',
				'desc'  => __( 'Organize students into groups for collaborative learning.', 'saw-lms' ),
			),
			'saw-lms-reports'       => array(
				'title' => __( 'Reports', 'saw-lms' ),
				'icon'  => 'dashicons-chart-bar',
				'phase' => '17',
				'desc'  => __( 'Detailed analytics and reporting.', 'saw-lms' ),
			),
			'saw-lms-settings'      => array(
				'title' => __( 'Settings', 'saw-lms' ),
				'icon'  => 'dashicons-admin-settings',
				'phase' => '17',
				'desc'  => __( 'Configure plugin settings.', 'saw-lms' ),
			),
		);

		$page_info = isset( $titles[ $page ] ) ? $titles[ $page ] : array(
			'title' => __( 'Coming Soon', 'saw-lms' ),
			'icon'  => 'dashicons-clock',
			'phase' => 'TBA',
			'desc'  => __( 'This feature is planned for a future release.', 'saw-lms' ),
		);

		?>
		<div class="wrap saw-admin-page">
			<div class="saw-page-header">
				<div class="saw-page-title-wrapper">
					<h1 class="saw-page-title">
						<span class="dashicons <?php echo esc_attr( $page_info['icon'] ); ?>"></span>
						<?php echo esc_html( $page_info['title'] ); ?>
					</h1>
					<p class="saw-page-subtitle">
						<?php printf( esc_html__( 'Coming in Phase %s', 'saw-lms' ), esc_html( $page_info['phase'] ) ); ?>
					</p>
				</div>
			</div>

			<div class="saw-card">
				<div class="saw-card-body" style="text-align: center; padding: 60px 30px;">
					<div style="font-size: 80px; opacity: 0.2;">
						<span class="dashicons <?php echo esc_attr( $page_info['icon'] ); ?>"></span>
					</div>
					<h2><?php esc_html_e( 'Feature Under Development', 'saw-lms' ); ?></h2>
					<p><?php echo esc_html( $page_info['desc'] ); ?></p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=saw-lms' ) ); ?>" class="saw-btn saw-btn-primary">
							<?php esc_html_e( 'Back to Dashboard', 'saw-lms' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display Info page
	 *
	 * @since 1.0.0
	 */
	public function display_info_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'saw-lms' ) );
		}

		$plugin_data = get_plugin_data( SAW_LMS_PLUGIN_FILE );
		?>
		<div class="wrap saw-admin-page">
			<div class="saw-page-header">
				<div class="saw-page-title-wrapper">
					<h1 class="saw-page-title">
						<span class="dashicons dashicons-info-outline"></span>
						<?php esc_html_e( 'Plugin Information', 'saw-lms' ); ?>
					</h1>
				</div>
			</div>

			<div class="saw-dashboard-grid">
				<div class="saw-card">
					<div class="saw-card-header">
						<h3><?php esc_html_e( 'Plugin Details', 'saw-lms' ); ?></h3>
					</div>
					<div class="saw-card-body">
						<p><strong><?php esc_html_e( 'Version:', 'saw-lms' ); ?></strong> <?php echo esc_html( SAW_LMS_VERSION ); ?></p>
						<p><strong><?php esc_html_e( 'Author:', 'saw-lms' ); ?></strong> <?php echo esc_html( $plugin_data['Author'] ); ?></p>
					</div>
				</div>

				<div class="saw-card">
					<div class="saw-card-header">
						<h3><?php esc_html_e( 'System Information', 'saw-lms' ); ?></h3>
					</div>
					<div class="saw-card-body">
						<p><strong><?php esc_html_e( 'WordPress:', 'saw-lms' ); ?></strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></p>
						<p><strong><?php esc_html_e( 'PHP:', 'saw-lms' ); ?></strong> <?php echo esc_html( PHP_VERSION ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get dashboard statistics
	 *
	 * @since  1.0.0
	 * @return array
	 */
	private function get_dashboard_stats() {
		$stats = array(
			'courses'  => 0,
			'sections' => 0,
			'lessons'  => 0,
			'quizzes'  => 0,
		);

		if ( post_type_exists( 'saw_course' ) ) {
			$stats['courses'] = wp_count_posts( 'saw_course' )->publish;
		}

		if ( post_type_exists( 'saw_section' ) ) {
			$stats['sections'] = wp_count_posts( 'saw_section' )->publish;
		}

		if ( post_type_exists( 'saw_lesson' ) ) {
			$stats['lessons'] = wp_count_posts( 'saw_lesson' )->publish;
		}

		if ( post_type_exists( 'saw_quiz' ) ) {
			$stats['quizzes'] = wp_count_posts( 'saw_quiz' )->publish;
		}

		return $stats;
	}
}