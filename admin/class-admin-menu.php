<?php
/**
 * Admin Menu
 *
 * Vytvoření menu v administraci s moderním dashboardem.
 * Používá nový design system (Fáze 1.9).
 *
 * UPDATED in Phase 2.1: Added Courses submenu item with correct capabilities.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/admin
 * @since      1.0.0
 * @version    2.1.0
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
	 * UPDATED in Phase 2.1: Added Courses submenu with correct capabilities.
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

		// Submenu - Courses (NEW in Phase 2.1)
		// FIXED: Používáme správnou capability 'edit_saw_courses'
		if ( post_type_exists( 'saw_course' ) ) {
			add_submenu_page(
				'saw-lms',
				__( 'Courses', 'saw-lms' ),
				__( 'Courses', 'saw-lms' ),
				'edit_saw_courses',  // ✅ OPRAVENO: Správná custom capability
				'edit.php?post_type=saw_course',
				''
			);
		} else {
			// Fallback pokud CPT ještě není zaregistrován
			add_submenu_page(
				'saw-lms',
				__( 'Courses', 'saw-lms' ),
				__( 'Courses', 'saw-lms' ) . ' <span class="awaiting-mod">!</span>',
				'manage_options',
				'saw-lms-courses-placeholder',
				array( $this, 'display_courses_placeholder' )
			);
		}

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

		// Get quick stats
		global $wpdb;
		$stats = array(
			'enrollments' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments" ),
			'groups'      => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_groups" ),
			'cache_hits'  => 0, // Bude implementováno později
		);

		// Check if Course CPT is registered
		$cpt_registered = post_type_exists( 'saw_course' );

		?>
		<div class="saw-admin-page">
			
			<!-- Page Header -->
			<div class="saw-page-header">
				<div class="saw-page-title-wrapper">
					<h1 class="saw-page-title">
						<?php esc_html_e( 'SAW LMS Dashboard', 'saw-lms' ); ?>
					</h1>
					<p class="saw-page-subtitle">
						<?php esc_html_e( 'Welcome to SAW Learning Management System', 'saw-lms' ); ?>
					</p>
				</div>
			</div>

			<!-- Main Content -->
			<div class="saw-dashboard-grid">

				<!-- Left Column -->
				<div class="saw-dashboard-col-main">

					<!-- Quick Stats -->
					<div class="saw-card">
						<div class="saw-card-header">
							<h2 class="saw-card-title">
								<?php esc_html_e( 'Quick Stats', 'saw-lms' ); ?>
							</h2>
						</div>
						<div class="saw-card-body">
							<div class="saw-stats-grid">
								<div class="saw-stat-item">
									<div class="saw-stat-label">
										<?php esc_html_e( 'Enrollments', 'saw-lms' ); ?>
									</div>
									<div class="saw-stat-value">
										<?php echo esc_html( number_format_i18n( $stats['enrollments'] ) ); ?>
									</div>
								</div>
								<div class="saw-stat-item">
									<div class="saw-stat-label">
										<?php esc_html_e( 'Groups', 'saw-lms' ); ?>
									</div>
									<div class="saw-stat-value">
										<?php echo esc_html( number_format_i18n( $stats['groups'] ) ); ?>
									</div>
								</div>
								<?php if ( $cpt_registered ) : ?>
									<div class="saw-stat-item">
										<div class="saw-stat-label">
											<?php esc_html_e( 'Courses', 'saw-lms' ); ?>
										</div>
										<div class="saw-stat-value">
											<?php 
											$course_count = wp_count_posts( 'saw_course' );
											echo esc_html( number_format_i18n( $course_count->publish ) ); 
											?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Getting Started (Phase 2.1 Progress) -->
					<div class="saw-card mt-6">
						<div class="saw-card-header">
							<h2 class="saw-card-title">
								<?php esc_html_e( '🚀 Phase 2.1 Progress', 'saw-lms' ); ?>
							</h2>
						</div>
						<div class="saw-card-body">
							<p class="text-gray-700 mb-4">
								<?php esc_html_e( 'You have successfully implemented Phase 2.1 - Custom Post Types!', 'saw-lms' ); ?>
							</p>
							<ul class="saw-list">
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Course post type registered', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Section post type registered', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Lesson post type registered', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Quiz post type registered', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Admin menu integration complete', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Custom capabilities added to Administrator role', 'saw-lms' ); ?>
								</li>
							</ul>

							<div class="mt-4 pt-4 border-top">
								<h3 class="font-bold text-lg mb-2">
									<?php esc_html_e( '📝 Next Steps (Phase 2.2):', 'saw-lms' ); ?>
								</h3>
								<ul class="saw-list">
									<li class="saw-list-item text-gray-600">
										<span class="dashicons dashicons-arrow-right"></span>
										<?php esc_html_e( 'Course Builder Interface', 'saw-lms' ); ?>
									</li>
									<li class="saw-list-item text-gray-600">
										<span class="dashicons dashicons-arrow-right"></span>
										<?php esc_html_e( 'Drag & Drop Section/Lesson Management', 'saw-lms' ); ?>
									</li>
									<li class="saw-list-item text-gray-600">
										<span class="dashicons dashicons-arrow-right"></span>
										<?php esc_html_e( 'Quiz Question Builder', 'saw-lms' ); ?>
									</li>
								</ul>
							</div>
						</div>
					</div>

				</div>

				<!-- Right Sidebar -->
				<div class="saw-dashboard-col-sidebar">

					<!-- System Status -->
					<div class="saw-card">
						<div class="saw-card-header">
							<h3 class="saw-card-title">
								<?php esc_html_e( 'System Status', 'saw-lms' ); ?>
							</h3>
						</div>
						<div class="saw-card-body">
							<table class="saw-table-simple">
								<tr>
									<td class="py-2">
										<strong><?php esc_html_e( 'Plugin Version:', 'saw-lms' ); ?></strong>
									</td>
									<td class="py-2 text-right text-sm text-gray-600">
										<?php echo esc_html( SAW_LMS_VERSION ); ?>
									</td>
								</tr>
								<tr>
									<td class="py-2">
										<strong><?php esc_html_e( 'CPT Status:', 'saw-lms' ); ?></strong>
									</td>
									<td class="py-2 text-right">
										<span class="saw-badge <?php echo $cpt_registered ? 'saw-badge-success' : 'saw-badge-warning'; ?>">
											<?php echo $cpt_registered ? esc_html__( 'Active', 'saw-lms' ) : esc_html__( 'Missing', 'saw-lms' ); ?>
										</span>
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

					<!-- Courses Card (if CPT registered) -->
					<?php if ( $cpt_registered ) : ?>
						<div class="saw-card mt-6">
							<div class="saw-card-header">
								<h3 class="saw-card-title">
									<?php esc_html_e( 'Courses', 'saw-lms' ); ?>
								</h3>
							</div>
							<div class="saw-card-body text-center">
								<div class="saw-stat-value text-primary">
									<?php 
									$course_count = wp_count_posts( 'saw_course' );
									echo esc_html( number_format_i18n( $course_count->publish ) ); 
									?>
								</div>
								<p class="text-sm text-gray-600 mt-2">
									<?php esc_html_e( 'Published courses', 'saw-lms' ); ?>
								</p>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=saw_course' ) ); ?>" 
								   class="saw-btn saw-btn-sm saw-btn-secondary mt-3">
									<?php esc_html_e( 'Manage Courses', 'saw-lms' ); ?>
								</a>
							</div>
						</div>
					<?php endif; ?>

				</div>

			</div>

		</div>
		<?php
	}

	/**
	 * Display Courses Placeholder page
	 *
	 * Shown when CPT files are not uploaded yet.
	 *
	 * @since 2.1.0
	 */
	public function display_courses_placeholder() {
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'saw-lms' ) );
		}

		?>
		<div class="saw-admin-page">
			
			<div class="saw-page-header">
				<div class="saw-page-title-wrapper">
					<h1 class="saw-page-title">
						<?php esc_html_e( 'Courses', 'saw-lms' ); ?>
					</h1>
					<p class="saw-page-subtitle">
						<?php esc_html_e( 'Course management', 'saw-lms' ); ?>
					</p>
				</div>
			</div>

			<div class="saw-alert saw-alert-warning">
				<span class="saw-alert-icon">⚠️</span>
				<div class="saw-alert-content">
					<p class="saw-alert-title"><?php esc_html_e( 'Course Post Type Not Available', 'saw-lms' ); ?></p>
					<p class="saw-alert-message">
						<?php 
						esc_html_e( 'To enable course management, please upload the following files from Phase 2.1:', 'saw-lms' );
						?>
					</p>
					<ul class="mt-3">
						<li><code>/includes/post-types/class-course.php</code></li>
						<li><code>/includes/post-types/class-section.php</code></li>
						<li><code>/includes/post-types/class-lesson.php</code></li>
						<li><code>/includes/post-types/class-quiz.php</code></li>
					</ul>
					<p class="mt-3">
						<?php esc_html_e( 'After uploading these files, reload this page and the course management will be available.', 'saw-lms' ); ?>
					</p>
				</div>
			</div>

			<div class="saw-card">
				<div class="saw-card-header">
					<h2 class="saw-card-title">
						<?php esc_html_e( '📚 What You Will Be Able To Do', 'saw-lms' ); ?>
					</h2>
				</div>
				<div class="saw-card-body">
					<ul class="saw-list">
						<li class="saw-list-item">
							<span class="dashicons dashicons-book-alt text-primary"></span>
							<?php esc_html_e( 'Create and manage courses', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-portfolio text-primary"></span>
							<?php esc_html_e( 'Organize content into sections', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-media-document text-primary"></span>
							<?php esc_html_e( 'Add lessons with video, documents, and text', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-welcome-learn-more text-primary"></span>
							<?php esc_html_e( 'Create quizzes and assessments', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-yes-alt text-primary"></span>
							<?php esc_html_e( 'Track student progress and completion', 'saw-lms' ); ?>
						</li>
					</ul>
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

		?>
		<div class="saw-admin-page">
			
			<div class="saw-page-header">
				<div class="saw-page-title-wrapper">
					<h1 class="saw-page-title">
						<?php esc_html_e( 'Plugin Info', 'saw-lms' ); ?>
					</h1>
					<p class="saw-page-subtitle">
						<?php esc_html_e( 'About SAW LMS', 'saw-lms' ); ?>
					</p>
				</div>
			</div>

			<div class="saw-card">
				<div class="saw-card-body">
					<table class="saw-table-simple">
						<tr>
							<td class="py-3">
								<strong><?php esc_html_e( 'Plugin Name:', 'saw-lms' ); ?></strong>
							</td>
							<td class="py-3 text-gray-600">
								<?php esc_html_e( 'SAW LMS', 'saw-lms' ); ?>
							</td>
						</tr>
						<tr>
							<td class="py-3">
								<strong><?php esc_html_e( 'Version:', 'saw-lms' ); ?></strong>
							</td>
							<td class="py-3 text-gray-600">
								<?php echo esc_html( SAW_LMS_VERSION ); ?>
							</td>
						</tr>
						<tr>
							<td class="py-3">
								<strong><?php esc_html_e( 'Author:', 'saw-lms' ); ?></strong>
							</td>
							<td class="py-3 text-gray-600">
								<?php esc_html_e( 'SAW Team', 'saw-lms' ); ?>
							</td>
						</tr>
						<tr>
							<td class="py-3">
								<strong><?php esc_html_e( 'Description:', 'saw-lms' ); ?></strong>
							</td>
							<td class="py-3 text-gray-600">
								<?php esc_html_e( 'Kompletní Learning Management System pro WordPress s WooCommerce integrací, skupinovými licencemi a content versioning', 'saw-lms' ); ?>
							</td>
						</tr>
						<tr>
							<td class="py-3">
								<strong><?php esc_html_e( 'Minimum Requirements:', 'saw-lms' ); ?></strong>
							</td>
							<td class="py-3 text-gray-600">
								<ul class="saw-list-simple">
									<li>WordPress 5.8+</li>
									<li>PHP 7.4+</li>
									<li>WooCommerce 6.0+ (optional)</li>
								</ul>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="saw-card mt-6">
				<div class="saw-card-header">
					<h2 class="saw-card-title">
						<?php esc_html_e( 'Key Features', 'saw-lms' ); ?>
					</h2>
				</div>
				<div class="saw-card-body">
					<ul class="saw-list">
						<li class="saw-list-item">
							<span class="dashicons dashicons-yes text-success"></span>
							<?php esc_html_e( 'Course Management with Custom Post Types', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-yes text-success"></span>
							<?php esc_html_e( 'Group-Based Learning', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-yes text-success"></span>
							<?php esc_html_e( 'Content Versioning & Archiving', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-yes text-success"></span>
							<?php esc_html_e( 'Drip Content & Scheduling', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-yes text-success"></span>
							<?php esc_html_e( 'Points & Achievements System', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-yes text-success"></span>
							<?php esc_html_e( 'Certificate Generation', 'saw-lms' ); ?>
						</li>
						<li class="saw-list-item">
							<span class="dashicons dashicons-yes text-success"></span>
							<?php esc_html_e( 'WooCommerce Integration (optional)', 'saw-lms' ); ?>
						</li>
					</ul>
				</div>
			</div>

		</div>
		<?php
	}
}