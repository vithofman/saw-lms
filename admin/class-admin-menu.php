<?php
/**
 * Admin Menu
 *
 * Vytvoření menu v administraci s moderním dashboardem.
 * Používá nový design system (Fáze 1.9).
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/admin
 * @since      1.0.0
 * @version    1.9.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Admin_Menu Class
 *
 * Spravuje admin menu a dashboard stránku.
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
	 * @since 1.0.0
	 */
	public function add_menu() {
		// Hlavní menu položka
		add_menu_page(
			__( 'SAW LMS', 'saw-lms' ),                    // Page title
			__( 'SAW LMS', 'saw-lms' ),                    // Menu title
			'manage_options',                               // Capability
			'saw-lms',                                      // Menu slug
			array( $this, 'display_dashboard' ),           // Callback
			'dashicons-book-alt',                           // Icon
			30                                              // Position
		);

		// Submenu - Dashboard (přejmenovaná první položka)
		add_submenu_page(
			'saw-lms',
			__( 'Dashboard', 'saw-lms' ),
			__( 'Dashboard', 'saw-lms' ),
			'manage_options',
			'saw-lms',
			array( $this, 'display_dashboard' )
		);

		// Submenu - Plugin Info
		add_submenu_page(
			'saw-lms',
			__( 'Plugin Info', 'saw-lms' ),
			__( 'Plugin Info', 'saw-lms' ),
			'manage_options',
			'saw-lms-info',
			array( $this, 'display_info_page' )
		);
	}

	/**
	 * Display Dashboard page
	 *
	 * Moderní dashboard s použitím design systému.
	 *
	 * @since 1.0.0
	 */
	public function display_dashboard() {
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'saw-lms' ) );
		}

		global $wpdb;

		// Get statistics
		$stats = $this->get_dashboard_stats();

		?>
		<div class="saw-admin-page">
			
			<!-- Page Header -->
			<div class="saw-page-header">
				<div class="saw-page-title-wrapper">
					<h1 class="saw-page-title">
						<?php esc_html_e( 'Dashboard', 'saw-lms' ); ?>
					</h1>
					<p class="saw-page-subtitle">
						<?php esc_html_e( 'Welcome to SAW LMS - Learning Management System', 'saw-lms' ); ?>
					</p>
				</div>
				<div class="saw-page-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=saw-lms-cache-test' ) ); ?>" 
					   class="saw-btn saw-btn-secondary">
						<span class="dashicons dashicons-admin-tools"></span>
						<?php esc_html_e( 'Cache Test', 'saw-lms' ); ?>
					</a>
				</div>
			</div>

			<!-- Welcome Alert -->
			<div class="saw-alert saw-alert-success">
				<span class="saw-alert-icon">🎉</span>
				<div class="saw-alert-content">
					<p class="saw-alert-title"><?php esc_html_e( 'Plugin is Active!', 'saw-lms' ); ?></p>
					<p class="saw-alert-message">
						<?php esc_html_e( 'All core systems are ready. Database tables created, cache system initialized, and logging active.', 'saw-lms' ); ?>
					</p>
				</div>
			</div>

			<!-- Stats Grid -->
			<div class="saw-dashboard-grid">
				
				<!-- Total Enrollments -->
				<div class="saw-stat-card">
					<div class="saw-stat-label">
						<?php esc_html_e( 'Total Enrollments', 'saw-lms' ); ?>
					</div>
					<div class="saw-stat-value">
						<?php echo esc_html( number_format_i18n( $stats['enrollments'] ) ); ?>
					</div>
					<div class="saw-stat-change is-positive">
						<span class="dashicons dashicons-arrow-up-alt"></span>
						<?php esc_html_e( 'All time', 'saw-lms' ); ?>
					</div>
				</div>

				<!-- Active Enrollments -->
				<div class="saw-stat-card">
					<div class="saw-stat-label">
						<?php esc_html_e( 'Active Enrollments', 'saw-lms' ); ?>
					</div>
					<div class="saw-stat-value">
						<?php echo esc_html( number_format_i18n( $stats['active_enrollments'] ) ); ?>
					</div>
					<div class="saw-stat-change">
						<span class="dashicons dashicons-groups"></span>
						<?php esc_html_e( 'Currently enrolled', 'saw-lms' ); ?>
					</div>
				</div>

				<!-- Completed Courses -->
				<div class="saw-stat-card">
					<div class="saw-stat-label">
						<?php esc_html_e( 'Completed Courses', 'saw-lms' ); ?>
					</div>
					<div class="saw-stat-value">
						<?php echo esc_html( number_format_i18n( $stats['completed'] ) ); ?>
					</div>
					<div class="saw-stat-change is-positive">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Completed', 'saw-lms' ); ?>
					</div>
				</div>

				<!-- Certificates Issued -->
				<div class="saw-stat-card">
					<div class="saw-stat-label">
						<?php esc_html_e( 'Certificates Issued', 'saw-lms' ); ?>
					</div>
					<div class="saw-stat-value">
						<?php echo esc_html( number_format_i18n( $stats['certificates'] ) ); ?>
					</div>
					<div class="saw-stat-change">
						<span class="dashicons dashicons-awards"></span>
						<?php esc_html_e( 'Total', 'saw-lms' ); ?>
					</div>
				</div>

			</div>

			<!-- Two Column Layout -->
			<div class="saw-layout-two-column">
				
				<!-- Main Content -->
				<div class="saw-layout-main">
					
					<!-- Current Phase Card -->
					<div class="saw-card mb-6">
						<div class="saw-card-header">
							<h2 class="saw-card-title">
								<?php esc_html_e( '🚀 Current Development Phase', 'saw-lms' ); ?>
							</h2>
						</div>
						<div class="saw-card-body">
							<div class="mb-4">
								<div class="d-flex justify-between align-center mb-2">
									<span class="font-semibold text-gray-700">
										<?php esc_html_e( 'Phase 1.9: Admin Design System', 'saw-lms' ); ?>
									</span>
									<span class="saw-badge saw-badge-success">
										<?php esc_html_e( 'In Progress', 'saw-lms' ); ?>
									</span>
								</div>
								<div class="saw-progress">
									<div class="saw-progress-bar is-success" style="width: 75%;"></div>
								</div>
							</div>

							<h3 class="text-base font-semibold mb-2">
								<?php esc_html_e( 'Completed in this phase:', 'saw-lms' ); ?>
							</h3>
							<ul class="saw-list">
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( '15 Database tables created', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Cache system with Redis/Database/Transient drivers', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Error handling & logging system', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Modern admin design system', 'saw-lms' ); ?>
								</li>
							</ul>

							<h3 class="text-base font-semibold mb-2 mt-4">
								<?php esc_html_e( 'Next steps:', 'saw-lms' ); ?>
							</h3>
							<ul class="saw-list">
								<li class="saw-list-item">
									<span class="dashicons dashicons-clock text-warning"></span>
									<?php esc_html_e( 'Custom Post Types (Phase 2)', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-clock text-warning"></span>
									<?php esc_html_e( 'Course Builder UI (Phase 3)', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-clock text-warning"></span>
									<?php esc_html_e( 'WooCommerce Integration (Phase 6)', 'saw-lms' ); ?>
								</li>
							</ul>
						</div>
					</div>

					<!-- Quick Actions Card -->
					<div class="saw-card">
						<div class="saw-card-header">
							<h2 class="saw-card-title">
								<?php esc_html_e( '⚡ Quick Actions', 'saw-lms' ); ?>
							</h2>
						</div>
						<div class="saw-card-body">
							<div class="saw-cluster-3">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=saw-lms-cache-test' ) ); ?>" 
								   class="saw-btn saw-btn-primary">
									<span class="dashicons dashicons-admin-tools"></span>
									<?php esc_html_e( 'Test Cache System', 'saw-lms' ); ?>
								</a>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=saw-lms-info' ) ); ?>" 
								   class="saw-btn saw-btn-secondary">
									<span class="dashicons dashicons-info"></span>
									<?php esc_html_e( 'Plugin Info', 'saw-lms' ); ?>
								</a>
								<button type="button" class="saw-btn saw-btn-ghost" 
								        onclick="SAW_LMS_Admin.notify.success('This is a test notification!', 3000);">
									<span class="dashicons dashicons-bell"></span>
									<?php esc_html_e( 'Test Notification', 'saw-lms' ); ?>
								</button>
							</div>
						</div>
					</div>

				</div>

				<!-- Sidebar -->
				<div class="saw-layout-sidebar">
					
					<!-- System Info Card -->
					<div class="saw-card">
						<div class="saw-card-header">
							<h3 class="saw-card-title">
								<?php esc_html_e( 'System Info', 'saw-lms' ); ?>
							</h3>
						</div>
						<div class="saw-card-body">
							<table class="w-full">
								<tr>
									<td class="py-2">
										<strong><?php esc_html_e( 'Plugin Version:', 'saw-lms' ); ?></strong>
									</td>
									<td class="py-2 text-right">
										<span class="saw-badge saw-badge-primary">
											<?php echo esc_html( SAW_LMS_VERSION ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<td class="py-2">
										<strong><?php esc_html_e( 'DB Version:', 'saw-lms' ); ?></strong>
									</td>
									<td class="py-2 text-right">
										<?php echo esc_html( get_option( 'saw_lms_db_version', 'N/A' ) ); ?>
									</td>
								</tr>
								<tr>
									<td class="py-2">
										<strong><?php esc_html_e( 'Cache Driver:', 'saw-lms' ); ?></strong>
									</td>
									<td class="py-2 text-right">
										<?php 
										$cache = saw_lms_cache();
										echo esc_html( ucfirst( $cache->get_driver_name() ) );
										?>
									</td>
								</tr>
								<tr>
									<td class="py-2">
										<strong><?php esc_html_e( 'Installed:', 'saw-lms' ); ?></strong>
									</td>
									<td class="py-2 text-right text-sm text-gray-600">
										<?php 
										$installed = get_option( 'saw_lms_installed_at' );
										echo $installed ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $installed ) ) ) : 'N/A';
										?>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<!-- Groups Card -->
					<div class="saw-card mt-6">
						<div class="saw-card-header">
							<h3 class="saw-card-title">
								<?php esc_html_e( 'Groups', 'saw-lms' ); ?>
							</h3>
						</div>
						<div class="saw-card-body text-center">
							<div class="saw-stat-value text-primary">
								<?php echo esc_html( number_format_i18n( $stats['groups'] ) ); ?>
							</div>
							<p class="text-sm text-gray-600 mt-2">
								<?php esc_html_e( 'Active learning groups', 'saw-lms' ); ?>
							</p>
						</div>
					</div>

				</div>

			</div>

		</div>
		<?php
	}

	/**
	 * Display Plugin Info page
	 *
	 * @since 1.0.0
	 */
	public function display_info_page() {
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'saw-lms' ) );
		}

		global $wpdb;

		// Get all tables
		$tables = $this->get_plugin_tables();

		?>
		<div class="saw-admin-page">
			
			<div class="saw-page-header">
				<div class="saw-page-title-wrapper">
					<h1 class="saw-page-title">
						<?php esc_html_e( 'Plugin Info', 'saw-lms' ); ?>
					</h1>
					<p class="saw-page-subtitle">
						<?php esc_html_e( 'Database tables and system information', 'saw-lms' ); ?>
					</p>
				</div>
			</div>

			<!-- Database Tables -->
			<div class="saw-card">
				<div class="saw-card-header">
					<h2 class="saw-card-title">
						<?php esc_html_e( '📋 Database Tables', 'saw-lms' ); ?>
					</h2>
					<p class="saw-card-subtitle">
						<?php 
						/* translators: %d: number of tables */
						printf( esc_html__( '%d tables created', 'saw-lms' ), count( $tables ) ); 
						?>
					</p>
				</div>
				<div class="saw-table-responsive">
					<table class="saw-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Table Name', 'saw-lms' ); ?></th>
								<th class="text-right"><?php esc_html_e( 'Record Count', 'saw-lms' ); ?></th>
								<th class="text-center"><?php esc_html_e( 'Status', 'saw-lms' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $tables as $table => $count ) : ?>
								<tr>
									<td>
										<code class="text-sm"><?php echo esc_html( $table ); ?></code>
									</td>
									<td class="text-right font-semibold">
										<?php echo esc_html( number_format_i18n( $count ) ); ?>
									</td>
									<td class="text-center">
										<span class="saw-badge saw-badge-success">
											<?php esc_html_e( 'Active', 'saw-lms' ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Get dashboard statistics
	 *
	 * @since  1.0.0
	 * @return array Statistics data
	 */
	private function get_dashboard_stats() {
		global $wpdb;

		return array(
			'enrollments'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments" ),
			'active_enrollments'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE status = 'enrolled'" ),
			'completed'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE status = 'completed'" ),
			'certificates'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_certificates" ),
			'groups'              => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_groups" ),
		);
	}

	/**
	 * Get plugin tables with record counts
	 *
	 * @since  1.0.0
	 * @return array Table names with counts
	 */
	private function get_plugin_tables() {
		global $wpdb;

		$tables = array(
			'saw_lms_enrollments',
			'saw_lms_progress',
			'saw_lms_quiz_attempts',
			'saw_lms_certificates',
			'saw_lms_points_ledger',
			'saw_lms_activity_log',
			'saw_lms_groups',
			'saw_lms_group_members',
			'saw_lms_custom_documents',
			'saw_lms_content_versions',
			'saw_lms_enrollment_content_versions',
			'saw_lms_content_changelog',
			'saw_lms_course_completion_snapshots',
			'saw_lms_course_schedules',
			'saw_lms_document_snapshots',
		);

		$result = array();

		foreach ( $tables as $table ) {
			$full_table_name = $wpdb->prefix . $table;
			$count           = $wpdb->get_var( "SELECT COUNT(*) FROM $full_table_name" );
			
			$result[ $full_table_name ] = (int) $count;
		}

		return $result;
	}
}