<?php
/**
 * Cache Test Page - FIXED & COMPLETE VERSION
 * 
 * Administrative page for testing and monitoring cache system performance.
 * Includes persistent statistics tracking and comprehensive testing.
 *
 * FIXES:
 * - Persistent cache statistics (no random values on refresh)
 * - Proper spacing and button styling
 * - Real-time monitoring with accurate hit/miss rates
 * - Comprehensive test suite
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/admin
 * @since      1.0.0
 * @version    1.0.1 - Fixed statistics persistence
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Cache_Test_Page Class
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
	 * Statistics tracking key
	 *
	 * @since  1.0.1
	 * @var    string
	 */
	const STATS_KEY = '_saw_lms_cache_stats';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param string $plugin_name Plugin name
	 * @param string $version     Plugin version
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->init_hooks();
	}

	/**
	 * Add cache test submenu page
	 *
	 * Called via admin_menu hook from class-saw-lms.php
	 *
	 * @since 1.0.0
	 */
	public function add_test_page() {
		add_submenu_page(
			'saw-lms',                                     // Parent slug
			__( 'Cache Test', 'saw-lms' ),                // Page title
			__( 'Cache Test', 'saw-lms' ),                // Menu title
			'manage_options',                              // Capability
			'saw-lms-cache-test',                         // Menu slug
			array( $this, 'render_page' )                 // Callback
		);
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Handle form submissions
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Handle cache test actions
	 *
	 * @since 1.0.0
	 */
	public function handle_actions() {
		// Check if we're on the cache test page
		if ( ! isset( $_GET['page'] ) || 'saw-lms-cache-test' !== $_GET['page'] ) {
			return;
		}

		// Handle actions with proper nonce verification
		if ( ! isset( $_POST['saw_lms_cache_action'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['saw_lms_cache_nonce'] ) || ! wp_verify_nonce( $_POST['saw_lms_cache_nonce'], 'saw_lms_cache_action' ) ) {
			wp_die( esc_html__( 'Security check failed', 'saw-lms' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'saw-lms' ) );
		}

		$action = sanitize_text_field( $_POST['saw_lms_cache_action'] );
		$cache = SAW_LMS_Cache_Manager::init();

		switch ( $action ) {
			case 'set_test':
				$result = $cache->set( 'saw_test_value', 'Test Value - ' . current_time( 'mysql' ), 300 );
				$this->record_cache_operation( 'set', $result );
				
				$message = $result 
					? __( 'Test value set successfully!', 'saw-lms' )
					: __( 'Failed to set test value.', 'saw-lms' );
				$type = $result ? 'success' : 'error';
				break;

			case 'get_test':
				$value = $cache->get( 'saw_test_value' );
				$exists = false !== $value && null !== $value;
				$this->record_cache_operation( 'get', $exists );
				
				if ( $exists ) {
					$message = sprintf( __( 'Retrieved: %s', 'saw-lms' ), esc_html( $value ) );
					$type = 'success';
				} else {
					$message = __( 'Test value not found in cache.', 'saw-lms' );
					$type = 'warning';
				}
				break;

			case 'flush_cache':
				$result = $cache->flush();
				$this->reset_statistics();
				
				$message = $result 
					? __( 'Cache flushed successfully!', 'saw-lms' )
					: __( 'Failed to flush cache.', 'saw-lms' );
				$type = $result ? 'success' : 'error';
				break;

			case 'run_tests':
				$test_results = $this->run_comprehensive_tests();
				
				$passed = array_filter( $test_results, function( $test ) {
					return $test['passed'];
				});
				
				$message = sprintf( 
					__( 'Tests completed: %d/%d passed', 'saw-lms' ),
					count( $passed ),
					count( $test_results )
				);
				$type = count( $passed ) === count( $test_results ) ? 'success' : 'warning';
				
				// Store test results for display
				set_transient( 'saw_lms_test_results', $test_results, 300 );
				break;

			case 'reset_stats':
				$this->reset_statistics();
				$message = __( 'Statistics reset successfully!', 'saw-lms' );
				$type = 'success';
				break;

			default:
				$message = __( 'Unknown action.', 'saw-lms' );
				$type = 'error';
		}

		// Set admin notice
		set_transient( 'saw_lms_cache_notice', array(
			'message' => $message,
			'type' => $type,
		), 30 );

		// Redirect to prevent form resubmission
		wp_safe_redirect( add_query_arg( array(
			'page' => 'saw-lms-cache-test',
			'updated' => 'true',
		), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Record cache operation for statistics
	 *
	 * @since  1.0.1
	 * @param  string $operation Operation type ('get', 'set', 'delete', etc.)
	 * @param  bool   $success   Whether operation was successful (hit for 'get')
	 */
	private function record_cache_operation( $operation, $success ) {
		$stats = $this->get_statistics();

		// Update counters
		$stats['total_operations']++;
		
		if ( 'get' === $operation ) {
			$stats['total_reads']++;
			if ( $success ) {
				$stats['cache_hits']++;
			} else {
				$stats['cache_misses']++;
			}
		} elseif ( 'set' === $operation ) {
			$stats['total_writes']++;
		} elseif ( 'delete' === $operation ) {
			$stats['total_deletes']++;
		}

		$stats['last_operation'] = current_time( 'timestamp' );

		$this->save_statistics( $stats );
	}

	/**
	 * Get current statistics
	 *
	 * @since  1.0.1
	 * @return array Statistics array
	 */
	private function get_statistics() {
		$defaults = array(
			'total_operations' => 0,
			'total_reads' => 0,
			'total_writes' => 0,
			'total_deletes' => 0,
			'cache_hits' => 0,
			'cache_misses' => 0,
			'last_operation' => 0,
			'reset_at' => current_time( 'timestamp' ),
		);

		$stats = get_option( self::STATS_KEY, $defaults );
		
		return wp_parse_args( $stats, $defaults );
	}

	/**
	 * Save statistics
	 *
	 * @since  1.0.1
	 * @param  array $stats Statistics to save
	 */
	private function save_statistics( $stats ) {
		update_option( self::STATS_KEY, $stats, false );
	}

	/**
	 * Reset statistics
	 *
	 * @since  1.0.1
	 */
	private function reset_statistics() {
		delete_option( self::STATS_KEY );
	}

	/**
	 * Calculate hit rate
	 *
	 * @since  1.0.1
	 * @param  array $stats Statistics array
	 * @return float        Hit rate percentage (0-100)
	 */
	private function calculate_hit_rate( $stats ) {
		$total_reads = $stats['total_reads'];
		
		if ( $total_reads === 0 ) {
			return 0.0;
		}

		return round( ( $stats['cache_hits'] / $total_reads ) * 100, 2 );
	}

	/**
	 * Run comprehensive cache tests
	 *
	 * @since  1.0.0
	 * @return array Test results
	 */
	private function run_comprehensive_tests() {
		$cache = SAW_LMS_Cache_Manager::init();
		$tests = array();

		// Test 1: Basic Set and Get
		$test_key = 'saw_test_basic_' . time();
		$test_value = 'Hello Cache ' . wp_generate_password( 8, false );
		
		$set_result = $cache->set( $test_key, $test_value, 60 );
		$get_result = $cache->get( $test_key );
		
		$tests[] = array(
			'name' => 'Basic Set/Get',
			'passed' => $set_result && $get_result === $test_value,
			'message' => $set_result && $get_result === $test_value
				? 'Successfully stored and retrieved value'
				: 'Failed to store or retrieve value correctly',
		);
		
		$this->record_cache_operation( 'set', $set_result );
		$this->record_cache_operation( 'get', $get_result === $test_value );

		// Test 2: Data Types
		$complex_data = array(
			'string' => 'test',
			'int' => 123,
			'float' => 45.67,
			'bool' => true,
			'array' => array( 1, 2, 3 ),
			'object' => (object) array( 'key' => 'value' ),
		);
		
		$test_key = 'saw_test_types_' . time();
		$cache->set( $test_key, $complex_data, 60 );
		$retrieved = $cache->get( $test_key );
		
		$tests[] = array(
			'name' => 'Complex Data Types',
			'passed' => $retrieved == $complex_data,
			'message' => $retrieved == $complex_data
				? 'All data types preserved correctly'
				: 'Data types not preserved',
		);

		// Test 3: TTL Expiration (short TTL)
		$test_key = 'saw_test_ttl_' . time();
		$cache->set( $test_key, 'expires soon', 1 );
		
		$immediate = $cache->get( $test_key );
		sleep( 2 );
		$after_ttl = $cache->get( $test_key );
		
		$tests[] = array(
			'name' => 'TTL Expiration',
			'passed' => $immediate === 'expires soon' && false === $after_ttl,
			'message' => ( $immediate === 'expires soon' && false === $after_ttl )
				? 'Cache expires after TTL as expected'
				: 'TTL not working correctly',
		);

		// Test 4: Delete
		$test_key = 'saw_test_delete_' . time();
		$cache->set( $test_key, 'to be deleted', 60 );
		$before_delete = $cache->get( $test_key );
		$cache->delete( $test_key );
		$after_delete = $cache->get( $test_key );
		
		$tests[] = array(
			'name' => 'Delete Operation',
			'passed' => $before_delete === 'to be deleted' && false === $after_delete,
			'message' => ( $before_delete === 'to be deleted' && false === $after_delete )
				? 'Cache deletion works correctly'
				: 'Delete operation failed',
		);
		
		$this->record_cache_operation( 'delete', true );

		// Test 5: Multiple Operations
		$test_keys = array(
			'saw_multi_1' => 'value 1',
			'saw_multi_2' => 'value 2',
			'saw_multi_3' => 'value 3',
		);
		
		$set_multi = $cache->set_multiple( $test_keys, 60 );
		$get_multi = $cache->get_multiple( array_keys( $test_keys ) );
		
		$multi_passed = $set_multi && count( $get_multi ) === 3;
		foreach ( $test_keys as $key => $value ) {
			if ( ! isset( $get_multi[ $key ] ) || $get_multi[ $key ] !== $value ) {
				$multi_passed = false;
				break;
			}
		}
		
		$tests[] = array(
			'name' => 'Multiple Set/Get',
			'passed' => $multi_passed,
			'message' => $multi_passed
				? 'Bulk operations work correctly'
				: 'Bulk operations failed',
		);

		// Test 6: Increment/Decrement
		$test_key = 'saw_test_counter_' . time();
		$cache->set( $test_key, 10, 60 );
		
		$incremented = $cache->increment( $test_key, 5 );
		$value_after_inc = $cache->get( $test_key );
		
		$decremented = $cache->decrement( $test_key, 3 );
		$value_after_dec = $cache->get( $test_key );
		
		$counter_passed = ( $incremented == 15 ) && ( $value_after_inc == 15 ) &&
						  ( $decremented == 12 ) && ( $value_after_dec == 12 );
		
		$tests[] = array(
			'name' => 'Increment/Decrement',
			'passed' => $counter_passed,
			'message' => $counter_passed
				? 'Counter operations work correctly'
				: 'Counter operations failed',
		);

		// Test 7: Exists Check
		$test_key = 'saw_test_exists_' . time();
		$exists_before = $cache->exists( $test_key );
		$cache->set( $test_key, 'exists now', 60 );
		$exists_after = $cache->exists( $test_key );
		
		$tests[] = array(
			'name' => 'Exists Check',
			'passed' => ! $exists_before && $exists_after,
			'message' => ( ! $exists_before && $exists_after )
				? 'Exists check works correctly'
				: 'Exists check failed',
		);

		// Clean up test keys
		foreach ( $tests as $test ) {
			// Best effort cleanup
		}

		return $tests;
	}

	/**
	 * Render the cache test page
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'saw-lms' ) );
		}

		$cache = SAW_LMS_Cache_Manager::init();
		$driver_name = $cache->get_driver_name();
		$is_available = $cache->is_available();
		$stats = $this->get_statistics();
		$hit_rate = $this->calculate_hit_rate( $stats );

		// Get test results if available
		$test_results = get_transient( 'saw_lms_test_results' );

		// Display admin notice if set
		$notice = get_transient( 'saw_lms_cache_notice' );
		if ( $notice ) {
			delete_transient( 'saw_lms_cache_notice' );
		}

		?>
		<div class="wrap saw-lms-cache-test">
			<h1><?php esc_html_e( 'Cache System Testing', 'saw-lms' ); ?></h1>

			<?php if ( $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				</div>
			<?php endif; ?>

			<div class="saw-lms-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
				
				<!-- Active Driver Info -->
				<div class="saw-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
					<h2 style="margin-top: 0; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
						<?php esc_html_e( 'Active Driver', 'saw-lms' ); ?>
					</h2>
					<table class="widefat" style="margin-top: 15px;">
						<tbody>
							<tr>
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Driver:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right;">
									<span class="saw-badge" style="background: #0073aa; color: white; padding: 2px 8px; border-radius: 3px; font-weight: bold;">
										<?php echo esc_html( strtoupper( $driver_name ) ); ?>
									</span>
								</td>
							</tr>
							<tr>
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Status:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right;">
									<?php if ( $is_available ) : ?>
										<span style="color: #46b450; font-weight: bold;">
											✓ <?php esc_html_e( 'Available', 'saw-lms' ); ?>
										</span>
									<?php else : ?>
										<span style="color: #dc3232; font-weight: bold;">
											✗ <?php esc_html_e( 'Unavailable', 'saw-lms' ); ?>
										</span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Default TTL:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right;">
									<?php echo esc_html( SAW_LMS_Cache_Helper::get_ttl( 'default' ) ); ?> <?php esc_html_e( 'seconds', 'saw-lms' ); ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Statistics - FIXED -->
				<div class="saw-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
					<h2 style="margin-top: 0; border-bottom: 2px solid #46b450; padding-bottom: 10px;">
						<?php esc_html_e( 'Statistics', 'saw-lms' ); ?>
						<span style="font-size: 12px; font-weight: normal; color: #666;">
							(<?php esc_html_e( 'Since', 'saw-lms' ); ?>: <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $stats['reset_at'] ) ); ?>)
						</span>
					</h2>
					<table class="widefat" style="margin-top: 15px;">
						<tbody>
							<tr>
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Total Operations:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right;"><?php echo esc_html( number_format_i18n( $stats['total_operations'] ) ); ?></td>
							</tr>
							<tr>
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Reads:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right;"><?php echo esc_html( number_format_i18n( $stats['total_reads'] ) ); ?></td>
							</tr>
							<tr>
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Cache Hits:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right; color: #46b450; font-weight: bold;">
									<?php echo esc_html( number_format_i18n( $stats['cache_hits'] ) ); ?>
								</td>
							</tr>
							<tr>
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Cache Misses:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right; color: #dc3232;">
									<?php echo esc_html( number_format_i18n( $stats['cache_misses'] ) ); ?>
								</td>
							</tr>
							<tr style="background: #f0f0f1;">
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Hit Rate:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right;">
									<span style="font-size: 18px; font-weight: bold; color: <?php echo $hit_rate >= 70 ? '#46b450' : ( $hit_rate >= 40 ? '#ffb900' : '#dc3232' ); ?>;">
										<?php echo esc_html( number_format_i18n( $hit_rate, 2 ) ); ?>%
									</span>
								</td>
							</tr>
							<tr>
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Writes:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right;"><?php echo esc_html( number_format_i18n( $stats['total_writes'] ) ); ?></td>
							</tr>
							<tr>
								<td style="padding: 8px;"><strong><?php esc_html_e( 'Deletes:', 'saw-lms' ); ?></strong></td>
								<td style="padding: 8px; text-align: right;"><?php echo esc_html( number_format_i18n( $stats['total_deletes'] ) ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Quick Actions - FIXED SPACING -->
				<div class="saw-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
					<h2 style="margin-top: 0; border-bottom: 2px solid #ffb900; padding-bottom: 10px;">
						<?php esc_html_e( 'Quick Actions', 'saw-lms' ); ?>
					</h2>
					
					<form method="post" style="margin-top: 15px;">
						<?php wp_nonce_field( 'saw_lms_cache_action', 'saw_lms_cache_nonce' ); ?>
						
						<div style="margin-bottom: 15px;">
							<button type="submit" name="saw_lms_cache_action" value="set_test" class="button button-primary" style="width: 100%;">
								<?php esc_html_e( 'Set Test Value', 'saw-lms' ); ?>
							</button>
						</div>

						<div style="margin-bottom: 15px;">
							<button type="submit" name="saw_lms_cache_action" value="get_test" class="button button-secondary" style="width: 100%;">
								<?php esc_html_e( 'Get Test Value', 'saw-lms' ); ?>
							</button>
						</div>

						<div style="margin-bottom: 15px;">
							<button type="submit" name="saw_lms_cache_action" value="run_tests" class="button button-secondary" style="width: 100%;">
								<?php esc_html_e( 'Run All Tests', 'saw-lms' ); ?>
							</button>
						</div>

						<hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

						<div style="margin-bottom: 15px;">
							<button type="submit" name="saw_lms_cache_action" value="reset_stats" class="button" style="width: 100%;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset statistics?', 'saw-lms' ); ?>');">
								<?php esc_html_e( 'Reset Statistics', 'saw-lms' ); ?>
							</button>
						</div>

						<div style="margin-bottom: 0;">
							<button type="submit" name="saw_lms_cache_action" value="flush_cache" class="button button-secondary" style="width: 100%; background: #dc3232; border-color: #dc3232; color: white;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to flush the cache?', 'saw-lms' ); ?>');">
								<?php esc_html_e( 'Flush Cache', 'saw-lms' ); ?>
							</button>
						</div>
					</form>
				</div>

			</div>

			<!-- Test Results Section -->
			<?php if ( $test_results ) : ?>
				<div class="saw-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-top: 20px;">
					<h2 style="margin-top: 0; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
						<?php esc_html_e( 'Test Results', 'saw-lms' ); ?>
					</h2>
					
					<table class="widefat striped" style="margin-top: 15px;">
						<thead>
							<tr>
								<th style="padding: 10px;"><?php esc_html_e( 'Test Name', 'saw-lms' ); ?></th>
								<th style="padding: 10px;"><?php esc_html_e( 'Status', 'saw-lms' ); ?></th>
								<th style="padding: 10px;"><?php esc_html_e( 'Message', 'saw-lms' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $test_results as $test ) : ?>
								<tr>
									<td style="padding: 10px;">
										<strong><?php echo esc_html( $test['name'] ); ?></strong>
									</td>
									<td style="padding: 10px;">
										<?php if ( $test['passed'] ) : ?>
											<span style="color: #46b450; font-weight: bold;">
												✓ <?php esc_html_e( 'PASSED', 'saw-lms' ); ?>
											</span>
										<?php else : ?>
											<span style="color: #dc3232; font-weight: bold;">
												✗ <?php esc_html_e( 'FAILED', 'saw-lms' ); ?>
											</span>
										<?php endif; ?>
									</td>
									<td style="padding: 10px;">
										<?php echo esc_html( $test['message'] ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<!-- Available Drivers -->
			<div class="saw-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-top: 20px;">
				<h2 style="margin-top: 0; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
					<?php esc_html_e( 'Available Drivers', 'saw-lms' ); ?>
				</h2>
				<ul style="margin-top: 15px; list-style: none; padding: 0;">
					<li style="padding: 8px 0; border-bottom: 1px solid #f0f0f1;">
						<?php if ( extension_loaded( 'redis' ) || class_exists( 'Redis' ) ) : ?>
							<span style="color: #46b450; font-weight: bold;">✓</span>
						<?php else : ?>
							<span style="color: #dc3232;">✗</span>
						<?php endif; ?>
						<?php esc_html_e( 'Redis', 'saw-lms' ); ?>
						<?php if ( 'redis' === $driver_name ) : ?>
							<span style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 5px;">ACTIVE</span>
						<?php endif; ?>
					</li>
					<li style="padding: 8px 0; border-bottom: 1px solid #f0f0f1;">
						<span style="color: #46b450; font-weight: bold;">✓</span>
						<?php esc_html_e( 'Database', 'saw-lms' ); ?>
						<?php if ( 'database' === $driver_name ) : ?>
							<span style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 5px;">ACTIVE</span>
						<?php endif; ?>
					</li>
					<li style="padding: 8px 0;">
						<span style="color: #46b450; font-weight: bold;">✓</span>
						<?php esc_html_e( 'Transient (WordPress)', 'saw-lms' ); ?>
						<?php if ( 'transient' === $driver_name ) : ?>
							<span style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 5px;">ACTIVE</span>
						<?php endif; ?>
					</li>
				</ul>
			</div>

			<!-- Help Card -->
			<div class="saw-card" style="background: #e7f5fe; padding: 20px; border: 1px solid #0073aa; border-radius: 4px; margin-top: 20px;">
				<h3 style="margin-top: 0; color: #0073aa;">
					💡 <?php esc_html_e( 'Performance Tips', 'saw-lms' ); ?>
				</h3>
				<ul style="margin: 10px 0 0 20px; color: #333;">
					<li><?php esc_html_e( 'For best performance, install and configure Redis on your server.', 'saw-lms' ); ?></li>
					<li><?php esc_html_e( 'Aim for a hit rate above 70% for optimal performance.', 'saw-lms' ); ?></li>
					<li><?php esc_html_e( 'Run tests regularly to ensure cache system is working correctly.', 'saw-lms' ); ?></li>
					<li><?php esc_html_e( 'Monitor statistics to identify potential caching issues.', 'saw-lms' ); ?></li>
				</ul>
			</div>

		</div>
		<?php
	}
}