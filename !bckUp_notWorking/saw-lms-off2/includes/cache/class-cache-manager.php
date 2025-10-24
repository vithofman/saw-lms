<?php
/**
 * Cache Manager - UPGRADED VERSION
 *
 * Central cache management system with intelligent driver auto-detection
 * and comprehensive statistics tracking.
 *
 * UPGRADES IN v1.1:
 * - Added get_stats() method for real-time statistics
 * - Added statistics tracking for all operations
 * - Improved error handling and logging
 * - Better memory management
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/cache
 * @since      1.0.0
 * @version    1.1.0 - Added statistics tracking
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Cache_Manager Class
 *
 * Singleton cache manager with statistics tracking
 *
 * @since 1.0.0
 */
class SAW_LMS_Cache_Manager {

	/**
	 * The single instance of the class
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS_Cache_Manager
	 */
	private static $instance = null;

	/**
	 * Active cache driver instance
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS_Cache_Driver
	 */
	private $driver = null;

	/**
	 * Driver detection cache
	 *
	 * @since  1.0.0
	 * @var    array
	 */
	private $driver_availability = array();

	/**
	 * Runtime statistics
	 *
	 * @since  1.1.0
	 * @var    array
	 */
	private $runtime_stats = array(
		'operations' => 0,
		'hits'       => 0,
		'misses'     => 0,
		'writes'     => 0,
		'deletes'    => 0,
		'errors'     => 0,
		'start_time' => 0,
	);

	/**
	 * Get the singleton instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Cache_Manager
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
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->runtime_stats['start_time'] = microtime( true );
		$this->detect_and_init_driver();
	}

	/**
	 * Detect and initialize the best available cache driver
	 *
	 * Priority order:
	 * 1. Check if driver is forced in settings
	 * 2. Try Redis
	 * 3. Try Database
	 * 4. Fallback to Transient
	 *
	 * @since 1.0.0
	 */
	private function detect_and_init_driver() {
		// Check if driver is forced in settings
		$forced_driver = get_option( 'saw_lms_cache_driver', 'auto' );

		if ( 'auto' !== $forced_driver ) {
			// Try to use forced driver
			if ( $this->init_specific_driver( $forced_driver ) ) {
				SAW_LMS_Logger::init()->info(
					'Cache driver forced by settings',
					array(
						'driver' => $forced_driver,
					)
				);
				return;
			}

			// If forced driver failed, log warning and continue auto-detection
			SAW_LMS_Logger::init()->warning(
				'Forced cache driver not available, falling back to auto-detection',
				array(
					'requested_driver' => $forced_driver,
				)
			);
		}

		// Auto-detection: Try Redis first
		if ( $this->init_specific_driver( 'redis' ) ) {
			return;
		}

		// Try Database
		if ( $this->init_specific_driver( 'database' ) ) {
			return;
		}

		// Fallback to Transient (always available)
		$this->init_specific_driver( 'transient' );
	}

	/**
	 * Initialize a specific cache driver
	 *
	 * @since  1.0.0
	 * @param  string $driver_name Driver name ('redis', 'database', 'transient')
	 * @return bool                True if driver initialized successfully
	 */
	private function init_specific_driver( $driver_name ) {
		try {
			switch ( $driver_name ) {
				case 'redis':
					$driver = new SAW_LMS_Redis_Driver();
					break;

				case 'database':
					$driver = new SAW_LMS_Db_Driver();
					break;

				case 'transient':
					$driver = new SAW_LMS_Transient_Driver();
					break;

				default:
					return false;
			}

			// Check if driver is available
			if ( ! $driver->is_available() ) {
				SAW_LMS_Logger::init()->debug(
					'Cache driver not available',
					array(
						'driver' => $driver_name,
					)
				);
				return false;
			}

			// Driver is available and functional
			$this->driver = $driver;

			SAW_LMS_Logger::init()->info(
				'Cache driver initialized',
				array(
					'driver' => $this->driver->get_driver_name(),
				)
			);

			return true;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Cache driver initialization failed',
				array(
					'driver' => $driver_name,
					'error'  => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Get active cache driver
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Cache_Driver|null
	 */
	public function get_driver() {
		return $this->driver;
	}

	/**
	 * Get active driver name
	 *
	 * @since  1.0.0
	 * @return string Driver name or 'none'
	 */
	public function get_driver_name() {
		return $this->driver ? $this->driver->get_driver_name() : 'none';
	}

	/**
	 * Check if cache is available
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function is_available() {
		return $this->driver && $this->driver->is_available();
	}

	/**
	 * Get value from cache
	 *
	 * @since  1.0.0
	 * @param  string $key Cache key
	 * @return mixed       Cached value or false
	 */
	public function get( $key ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			++$this->runtime_stats['operations'];

			$value = $this->driver->get( $key );

			if ( false !== $value && null !== $value ) {
				++$this->runtime_stats['hits'];
			} else {
				++$this->runtime_stats['misses'];
			}

			return $value;

		} catch ( Exception $e ) {
			++$this->runtime_stats['errors'];

			SAW_LMS_Logger::init()->error(
				'Cache get failed',
				array(
					'key'   => $key,
					'error' => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Set value in cache
	 *
	 * @since  1.0.0
	 * @param  string $key   Cache key
	 * @param  mixed  $value Value to cache
	 * @param  int    $ttl   Time to live in seconds
	 * @return bool          True on success
	 */
	public function set( $key, $value, $ttl = 3600 ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			++$this->runtime_stats['operations'];
			++$this->runtime_stats['writes'];

			return $this->driver->set( $key, $value, $ttl );

		} catch ( Exception $e ) {
			++$this->runtime_stats['errors'];

			SAW_LMS_Logger::init()->error(
				'Cache set failed',
				array(
					'key'   => $key,
					'ttl'   => $ttl,
					'error' => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Delete value from cache
	 *
	 * @since  1.0.0
	 * @param  string $key Cache key
	 * @return bool        True on success
	 */
	public function delete( $key ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			++$this->runtime_stats['operations'];
			++$this->runtime_stats['deletes'];

			return $this->driver->delete( $key );

		} catch ( Exception $e ) {
			++$this->runtime_stats['errors'];

			SAW_LMS_Logger::init()->error(
				'Cache delete failed',
				array(
					'key'   => $key,
					'error' => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Clear all cached values
	 *
	 * @since  1.0.0
	 * @return bool True on success
	 */
	public function flush() {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			$result = $this->driver->flush();

			if ( $result ) {
				SAW_LMS_Logger::init()->info( 'Cache flushed successfully' );
			}

			return $result;

		} catch ( Exception $e ) {
			++$this->runtime_stats['errors'];

			SAW_LMS_Logger::init()->error(
				'Cache flush failed',
				array(
					'error' => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Check if key exists in cache
	 *
	 * @since  1.0.0
	 * @param  string $key Cache key
	 * @return bool
	 */
	public function exists( $key ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			return $this->driver->exists( $key );

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Cache exists check failed',
				array(
					'key'   => $key,
					'error' => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Get multiple values from cache
	 *
	 * @since  1.0.0
	 * @param  array $keys Array of cache keys
	 * @return array       Associative array of found values
	 */
	public function get_multiple( $keys ) {
		if ( ! $this->is_available() ) {
			return array();
		}

		try {
			$this->runtime_stats['operations'] += count( $keys );

			$results = $this->driver->get_multiple( $keys );

			// Count hits and misses
			foreach ( $keys as $key ) {
				if ( isset( $results[ $key ] ) ) {
					++$this->runtime_stats['hits'];
				} else {
					++$this->runtime_stats['misses'];
				}
			}

			return $results;

		} catch ( Exception $e ) {
			++$this->runtime_stats['errors'];

			SAW_LMS_Logger::init()->error(
				'Cache get_multiple failed',
				array(
					'keys_count' => count( $keys ),
					'error'      => $e->getMessage(),
				)
			);

			return array();
		}
	}

	/**
	 * Set multiple values in cache
	 *
	 * @since  1.0.0
	 * @param  array $values Associative array of key => value pairs
	 * @param  int   $ttl    Time to live in seconds
	 * @return bool          True on success
	 */
	public function set_multiple( $values, $ttl = 3600 ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			$this->runtime_stats['operations'] += count( $values );
			$this->runtime_stats['writes']     += count( $values );

			return $this->driver->set_multiple( $values, $ttl );

		} catch ( Exception $e ) {
			++$this->runtime_stats['errors'];

			SAW_LMS_Logger::init()->error(
				'Cache set_multiple failed',
				array(
					'values_count' => count( $values ),
					'ttl'          => $ttl,
					'error'        => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Increment numeric value in cache
	 *
	 * @since  1.0.0
	 * @param  string $key    Cache key
	 * @param  int    $offset Amount to increment
	 * @return int|false      New value or false
	 */
	public function increment( $key, $offset = 1 ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			++$this->runtime_stats['operations'];

			return $this->driver->increment( $key, $offset );

		} catch ( Exception $e ) {
			++$this->runtime_stats['errors'];

			SAW_LMS_Logger::init()->error(
				'Cache increment failed',
				array(
					'key'    => $key,
					'offset' => $offset,
					'error'  => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Decrement numeric value in cache
	 *
	 * @since  1.0.0
	 * @param  string $key    Cache key
	 * @param  int    $offset Amount to decrement
	 * @return int|false      New value or false
	 */
	public function decrement( $key, $offset = 1 ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			++$this->runtime_stats['operations'];

			return $this->driver->decrement( $key, $offset );

		} catch ( Exception $e ) {
			++$this->runtime_stats['errors'];

			SAW_LMS_Logger::init()->error(
				'Cache decrement failed',
				array(
					'key'    => $key,
					'offset' => $offset,
					'error'  => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Remember value in cache (with callback for cache miss)
	 *
	 * If value exists in cache, return it.
	 * If not, execute callback, store result, and return it.
	 *
	 * @since  1.0.0
	 * @param  string   $key      Cache key
	 * @param  int      $ttl      Time to live in seconds
	 * @param  callable $callback Function to execute on cache miss
	 * @return mixed              Cached or generated value
	 */
	public function remember( $key, $ttl, $callback ) {
		$value = $this->get( $key );

		if ( false !== $value && null !== $value ) {
			return $value;
		}

		// Generate value
		$value = call_user_func( $callback );

		// Store in cache
		$this->set( $key, $value, $ttl );

		return $value;
	}

	/**
	 * Get cache statistics
	 *
	 * NEW IN v1.1.0
	 *
	 * Returns comprehensive statistics about cache usage
	 * during current request.
	 *
	 * @since  1.1.0
	 * @return array Statistics array
	 */
	public function get_stats() {
		$runtime = microtime( true ) - $this->runtime_stats['start_time'];

		$total_reads = $this->runtime_stats['hits'] + $this->runtime_stats['misses'];
		$hit_rate    = $total_reads > 0
			? round( ( $this->runtime_stats['hits'] / $total_reads ) * 100, 2 )
			: 0.0;

		return array(
			'driver'    => $this->get_driver_name(),
			'available' => $this->is_available(),
			'runtime'   => array(
				'total_operations' => $this->runtime_stats['operations'],
				'reads'            => $total_reads,
				'hits'             => $this->runtime_stats['hits'],
				'misses'           => $this->runtime_stats['misses'],
				'hit_rate'         => $hit_rate,
				'writes'           => $this->runtime_stats['writes'],
				'deletes'          => $this->runtime_stats['deletes'],
				'errors'           => $this->runtime_stats['errors'],
				'uptime_seconds'   => round( $runtime, 3 ),
			),
			'memory'    => array(
				'current'           => memory_get_usage( true ),
				'peak'              => memory_get_peak_usage( true ),
				'current_formatted' => size_format( memory_get_usage( true ), 2 ),
				'peak_formatted'    => size_format( memory_get_peak_usage( true ), 2 ),
			),
		);
	}

	/**
	 * Get formatted statistics for display
	 *
	 * @since  1.1.0
	 * @return string HTML formatted statistics
	 */
	public function get_stats_html() {
		$stats = $this->get_stats();

		ob_start();
		?>
		<div class="saw-lms-cache-stats">
			<strong>Driver:</strong> <?php echo esc_html( $stats['driver'] ); ?><br>
			<strong>Operations:</strong> <?php echo esc_html( $stats['runtime']['total_operations'] ); ?><br>
			<strong>Hit Rate:</strong> <?php echo esc_html( $stats['runtime']['hit_rate'] ); ?>%<br>
			<strong>Memory:</strong> <?php echo esc_html( $stats['memory']['current_formatted'] ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Reset runtime statistics
	 *
	 * @since 1.1.0
	 */
	public function reset_stats() {
		$this->runtime_stats = array(
			'operations' => 0,
			'hits'       => 0,
			'misses'     => 0,
			'writes'     => 0,
			'deletes'    => 0,
			'errors'     => 0,
			'start_time' => microtime( true ),
		);
	}
}
