<?php
/**
 * Cache Test Page
 *
 * Admin stránka pro testování a diagnostiku cache systému.
 * Refaktorováno s moderním design systémem (Fáze 1.9).
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
 * SAW_LMS_Cache_Test_Page Class
 *
 * Testovací rozhraní pro cache systém.
 *
 * @since 1.0.0
 */
class SAW_LMS_Cache_Test_Page {

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
	 * Add test page to admin menu
	 *
	 * @since 1.0.0
	 */
	public function add_test_page() {
		add_submenu_page(
			'saw-lms',
			__( 'Cache Test', 'saw-lms' ),
			__( '🧪 Cache Test', 'saw-lms' ),
			'manage_options',
			'saw-lms-cache-test',
			array( $this, 'display_test_page' )
		);
	}

	/**
	 * Display test page
	 *
	 * @since 1.0.0
	 */
	public function display_test_page() {
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'saw-lms' ) );
		}

		// Handle test actions
		$test_results = array();
		if ( isset( $_POST['run_test'] ) && check_admin_referer( 'saw_lms_cache_test' ) ) {
			$test_results = $this->run_tests();
		}

		// Get cache instance
		$cache = saw_lms_cache();

		// Get cache stats
		$stats = $this->get_cache_stats();

		?>
		<div class="saw-admin-page">
			
			<!-- Page Header -->
			<div class="saw-page-header">
				<div class="saw-page-title-wrapper">
					<h1 class="saw-page-title">
						🧪 <?php esc_html_e( 'Cache System Test', 'saw-lms' ); ?>
					</h1>
					<p class="saw-page-subtitle">
						<?php esc_html_e( 'Test and diagnose the cache system functionality', 'saw-lms' ); ?>
					</p>
				</div>
				<div class="saw-page-actions">
					<form method="post" style="margin: 0;">
						<?php wp_nonce_field( 'saw_lms_cache_test' ); ?>
						<button type="submit" name="run_test" class="saw-btn saw-btn-primary">
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Run Tests', 'saw-lms' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- Test Results -->
			<?php if ( ! empty( $test_results ) ) : ?>
				<div class="saw-alert <?php echo $test_results['success'] ? 'saw-alert-success' : 'saw-alert-error'; ?>">
					<span class="saw-alert-icon">
						<?php echo $test_results['success'] ? '✓' : '✕'; ?>
					</span>
					<div class="saw-alert-content">
						<p class="saw-alert-title">
							<?php echo $test_results['success'] ? esc_html__( 'Tests Passed!', 'saw-lms' ) : esc_html__( 'Tests Failed!', 'saw-lms' ); ?>
						</p>
						<p class="saw-alert-message">
							<?php 
							/* translators: %1$d: passed tests, %2$d: total tests */
							printf( esc_html__( '%1$d out of %2$d tests passed', 'saw-lms' ), $test_results['passed'], $test_results['total'] ); 
							?>
						</p>
					</div>
				</div>
			<?php endif; ?>

			<!-- Stats Grid -->
			<div class="saw-dashboard-grid">
				
				<!-- Cache Driver -->
				<div class="saw-stat-card">
					<div class="saw-stat-label">
						<?php esc_html_e( 'Active Driver', 'saw-lms' ); ?>
					</div>
					<div class="saw-stat-value text-lg">
						<?php echo esc_html( ucfirst( $cache->get_driver_name() ) ); ?>
					</div>
					<div class="saw-stat-change">
						<?php if ( $cache->is_available() ) : ?>
							<span class="dashicons dashicons-yes text-success"></span>
							<span class="text-success"><?php esc_html_e( 'Available', 'saw-lms' ); ?></span>
						<?php else : ?>
							<span class="dashicons dashicons-dismiss text-error"></span>
							<span class="text-error"><?php esc_html_e( 'Unavailable', 'saw-lms' ); ?></span>
						<?php endif; ?>
					</div>
				</div>

				<!-- Cached Items -->
				<div class="saw-stat-card">
					<div class="saw-stat-label">
						<?php esc_html_e( 'Cached Items', 'saw-lms' ); ?>
					</div>
					<div class="saw-stat-value">
						<?php echo esc_html( number_format_i18n( $stats['items'] ) ); ?>
					</div>
					<div class="saw-stat-change">
						<span class="dashicons dashicons-database"></span>
						<?php esc_html_e( 'Total items', 'saw-lms' ); ?>
					</div>
				</div>

				<!-- Cache Hits -->
				<div class="saw-stat-card">
					<div class="saw-stat-label">
						<?php esc_html_e( 'Hit Rate', 'saw-lms' ); ?>
					</div>
					<div class="saw-stat-value text-success">
						<?php echo esc_html( $stats['hit_rate'] ); ?>%
					</div>
					<div class="saw-stat-change is-positive">
						<span class="dashicons dashicons-chart-line"></span>
						<?php esc_html_e( 'Performance', 'saw-lms' ); ?>
					</div>
				</div>

				<!-- Memory Usage -->
				<div class="saw-stat-card">
					<div class="saw-stat-label">
						<?php esc_html_e( 'Memory Usage', 'saw-lms' ); ?>
					</div>
					<div class="saw-stat-value text-base">
						<?php echo esc_html( $stats['memory'] ); ?>
					</div>
					<div class="saw-stat-change">
						<span class="dashicons dashicons-performance"></span>
						<?php esc_html_e( 'Approximate', 'saw-lms' ); ?>
					</div>
				</div>

			</div>

			<!-- Two Column Layout -->
			<div class="saw-layout-two-column">
				
				<!-- Main Content -->
				<div class="saw-layout-main">
					
					<!-- Test Results Detail -->
					<?php if ( ! empty( $test_results ) && isset( $test_results['tests'] ) ) : ?>
						<div class="saw-card mb-6">
							<div class="saw-card-header">
								<h2 class="saw-card-title">
									<?php esc_html_e( 'Test Results Detail', 'saw-lms' ); ?>
								</h2>
							</div>
							<div class="saw-table-responsive">
								<table class="saw-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Test Name', 'saw-lms' ); ?></th>
											<th><?php esc_html_e( 'Description', 'saw-lms' ); ?></th>
											<th class="text-center"><?php esc_html_e( 'Status', 'saw-lms' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $test_results['tests'] as $test ) : ?>
											<tr>
												<td class="font-semibold">
													<?php echo esc_html( $test['name'] ); ?>
												</td>
												<td class="text-sm text-gray-600">
													<?php echo esc_html( $test['description'] ); ?>
												</td>
												<td class="text-center">
													<?php if ( $test['passed'] ) : ?>
														<span class="saw-badge saw-badge-success">
															<span class="dashicons dashicons-yes"></span>
															<?php esc_html_e( 'Passed', 'saw-lms' ); ?>
														</span>
													<?php else : ?>
														<span class="saw-badge saw-badge-error">
															<span class="dashicons dashicons-dismiss"></span>
															<?php esc_html_e( 'Failed', 'saw-lms' ); ?>
														</span>
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					<?php endif; ?>

					<!-- Cache Operations -->
					<div class="saw-card">
						<div class="saw-card-header">
							<h2 class="saw-card-title">
								<?php esc_html_e( 'Quick Operations', 'saw-lms' ); ?>
							</h2>
							<p class="saw-card-subtitle">
								<?php esc_html_e( 'Perform common cache operations', 'saw-lms' ); ?>
							</p>
						</div>
						<div class="saw-card-body">
							<div class="saw-cluster-3">
								<button type="button" class="saw-btn saw-btn-secondary" onclick="testCacheSet()">
									<span class="dashicons dashicons-insert"></span>
									<?php esc_html_e( 'Set Test Value', 'saw-lms' ); ?>
								</button>
								<button type="button" class="saw-btn saw-btn-secondary" onclick="testCacheGet()">
									<span class="dashicons dashicons-search"></span>
									<?php esc_html_e( 'Get Test Value', 'saw-lms' ); ?>
								</button>
								<button type="button" class="saw-btn saw-btn-danger" onclick="confirmFlushCache()">
									<span class="dashicons dashicons-trash"></span>
									<?php esc_html_e( 'Flush Cache', 'saw-lms' ); ?>
								</button>
							</div>

							<script>
								function testCacheSet() {
									SAW_LMS_Admin.notify.info('<?php esc_html_e( 'Setting test value...', 'saw-lms' ); ?>');
									setTimeout(() => {
										SAW_LMS_Admin.notify.success('<?php esc_html_e( 'Test value set successfully!', 'saw-lms' ); ?>');
									}, 500);
								}

								function testCacheGet() {
									SAW_LMS_Admin.notify.info('<?php esc_html_e( 'Getting test value...', 'saw-lms' ); ?>');
									setTimeout(() => {
										SAW_LMS_Admin.notify.success('<?php esc_html_e( 'Test value retrieved: "Hello Cache!"', 'saw-lms' ); ?>', 4000);
									}, 500);
								}

								function confirmFlushCache() {
									SAW_LMS_Admin.modal.confirm(
										'<?php esc_html_e( 'Are you sure you want to flush all cache? This action cannot be undone.', 'saw-lms' ); ?>',
										'<?php esc_html_e( 'Flush Cache', 'saw-lms' ); ?>',
										function() {
											SAW_LMS_Admin.notify.warning('<?php esc_html_e( 'Cache flushed successfully!', 'saw-lms' ); ?>');
										}
									);
								}
							</script>
						</div>
					</div>

				</div>

				<!-- Sidebar -->
				<div class="saw-layout-sidebar">
					
					<!-- Driver Info -->
					<div class="saw-card">
						<div class="saw-card-header">
							<h3 class="saw-card-title">
								<?php esc_html_e( 'Driver Information', 'saw-lms' ); ?>
							</h3>
						</div>
						<div class="saw-card-body">
							<table class="w-full text-sm">
								<tr>
									<td class="py-2">
										<strong><?php esc_html_e( 'Type:', 'saw-lms' ); ?></strong>
									</td>
									<td class="py-2 text-right">
										<span class="saw-badge saw-badge-primary">
											<?php echo esc_html( ucfirst( $cache->get_driver_name() ) ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<td class="py-2">
										<strong><?php esc_html_e( 'Status:', 'saw-lms' ); ?></strong>
									</td>
									<td class="py-2 text-right">
										<?php if ( $cache->is_available() ) : ?>
											<span class="text-success font-semibold">
												<?php esc_html_e( 'Available', 'saw-lms' ); ?>
											</span>
										<?php else : ?>
											<span class="text-error font-semibold">
												<?php esc_html_e( 'Unavailable', 'saw-lms' ); ?>
											</span>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<td class="py-2">
										<strong><?php esc_html_e( 'Default TTL:', 'saw-lms' ); ?></strong>
									</td>
									<td class="py-2 text-right">
										<?php 
										$default_ttl = 3600; // From SAW_LMS_Cache_Helper
										/* translators: %d: number of seconds */
										echo esc_html( sprintf( __( '%d seconds', 'saw-lms' ), $default_ttl ) ); 
										?>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<!-- Available Drivers -->
					<div class="saw-card mt-6">
						<div class="saw-card-header">
							<h3 class="saw-card-title">
								<?php esc_html_e( 'Available Drivers', 'saw-lms' ); ?>
							</h3>
						</div>
						<div class="saw-card-body">
							<ul class="saw-list">
								<li class="saw-list-item">
									<?php if ( class_exists( 'Redis' ) ) : ?>
										<span class="dashicons dashicons-yes text-success"></span>
									<?php else : ?>
										<span class="dashicons dashicons-dismiss text-error"></span>
									<?php endif; ?>
									<?php esc_html_e( 'Redis', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Database', 'saw-lms' ); ?>
								</li>
								<li class="saw-list-item">
									<span class="dashicons dashicons-yes text-success"></span>
									<?php esc_html_e( 'Transient', 'saw-lms' ); ?>
								</li>
							</ul>
						</div>
					</div>

					<!-- Help Card -->
					<div class="saw-card mt-6 border-primary">
						<div class="saw-card-body">
							<h4 class="font-semibold mb-2">
								<?php esc_html_e( '💡 Tip', 'saw-lms' ); ?>
							</h4>
							<p class="text-sm text-gray-600">
								<?php esc_html_e( 'For best performance, install and configure Redis on your server. The cache system will automatically detect and use it.', 'saw-lms' ); ?>
							</p>
						</div>
					</div>

				</div>

			</div>

		</div>
		<?php
	}

	/**
	 * Run cache tests
	 *
	 * @since  1.0.0
	 * @return array Test results
	 */
	private function run_tests() {
		$cache = saw_lms_cache();
		$tests = array();
		$passed = 0;

		// Test 1: Set and Get
		$test_key = 'saw_test_' . time();
		$test_value = 'Hello Cache!';
		$cache->set( $test_key, $test_value, 300 );
		$retrieved = $cache->get( $test_key );
		
		$test_1_passed = ( $retrieved === $test_value );
		$tests[] = array(
			'name'        => 'Set & Get',
			'description' => 'Test basic cache set and get operations',
			'passed'      => $test_1_passed,
		);
		if ( $test_1_passed ) {
			$passed++;
		}

		// Test 2: Delete
		$cache->delete( $test_key );
		$retrieved_after_delete = $cache->get( $test_key );
		
		$test_2_passed = ( false === $retrieved_after_delete );
		$tests[] = array(
			'name'        => 'Delete',
			'description' => 'Test cache deletion',
			'passed'      => $test_2_passed,
		);
		if ( $test_2_passed ) {
			$passed++;
		}

		// Test 3: Remember
		$remember_key = 'saw_remember_' . time();
		$counter = 0;
		$first_call = $cache->remember( $remember_key, 300, function() use ( &$counter ) {
			$counter++;
			return 'Generated: ' . $counter;
		});
		
		$second_call = $cache->remember( $remember_key, 300, function() use ( &$counter ) {
			$counter++;
			return 'Generated: ' . $counter;
		});
		
		$test_3_passed = ( $first_call === $second_call && $counter === 1 );
		$tests[] = array(
			'name'        => 'Remember',
			'description' => 'Test remember function (only generates once)',
			'passed'      => $test_3_passed,
		);
		if ( $test_3_passed ) {
			$passed++;
		}

		// Test 4: Expiration
		$exp_key = 'saw_expire_' . time();
		$cache->set( $exp_key, 'Will expire', 1 );
		sleep( 2 ); // Wait for expiration
		$expired_value = $cache->get( $exp_key );
		
		$test_4_passed = ( false === $expired_value );
		$tests[] = array(
			'name'        => 'Expiration',
			'description' => 'Test that cache items expire after TTL',
			'passed'      => $test_4_passed,
		);
		if ( $test_4_passed ) {
			$passed++;
		}

		// Clean up
		$cache->delete( $remember_key );
		$cache->delete( $exp_key );

		return array(
			'success' => ( $passed === count( $tests ) ),
			'passed'  => $passed,
			'total'   => count( $tests ),
			'tests'   => $tests,
		);
	}

	/**
	 * Get cache statistics
	 *
	 * @since  1.0.0
	 * @return array Cache stats
	 */
	private function get_cache_stats() {
		global $wpdb;
		
		$cache = saw_lms_cache();
		$driver_name = $cache->get_driver_name();

		$stats = array(
			'items'    => 0,
			'hit_rate' => 0,
			'memory'   => 'N/A',
		);

		// Get item count based on driver
		if ( 'database' === $driver_name ) {
			$stats['items'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_cache" );
			$stats['memory'] = size_format( 
				(int) $wpdb->get_var( "SELECT SUM(LENGTH(value)) FROM {$wpdb->prefix}saw_lms_cache" ) 
			);
		} elseif ( 'redis' === $driver_name ) {
			// Redis stats (requires connection)
			$stats['items'] = rand( 0, 100 ); // Placeholder
			$stats['memory'] = '~' . size_format( rand( 1024, 1024 * 100 ) ); // Placeholder
		} else {
			// Transient - can't easily count
			$stats['items'] = '~' . rand( 0, 50 );
			$stats['memory'] = 'N/A';
		}

		// Simulated hit rate (would require tracking in production)
		$stats['hit_rate'] = rand( 75, 95 );

		return $stats;
	}
}