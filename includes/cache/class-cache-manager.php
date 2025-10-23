<?php
/**
 * Cache Manager
 * 
 * Central cache management system with intelligent driver auto-detection.
 * Automatically selects the best available cache driver:
 * 1. Redis (fastest)
 * 2. Database (middle ground)
 * 3. Transient (always available fallback)
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/cache
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Cache_Manager Class
 * 
 * Singleton cache manager
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
				SAW_LMS_Logger::init()->info( 'Cache driver forced by settings', array(
					'driver' => $forced_driver,
				) );
				return;
			}

			// If forced driver failed, log warning and continue auto-detection
			SAW_LMS_Logger::init()->warning( 'Forced cache driver not available, falling back to auto-detection', array(
				'requested_driver' => $forced_driver,
			) );
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
				SAW_LMS_Logger::init()->debug( 'Cache driver not available', array(
					'driver' => $driver_name,
				) );
				return false;
			}

			// Driver is available and functional
			$this->driver = $driver;
			
			SAW_LMS_Logger::init()->info( 'Cache driver initialized', array(
				'driver' => $this->driver->get_driver_name(),
			) );

			return true;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Cache driver initialization failed', array(
				'driver' => $driver_name,
				'error' => $e->getMessage(),
			) );

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
	 * Check if cache system is available
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function is_available() {
		return null !== $this->driver;
	}

	/**
	 * Retrieve cached value by key
	 *
	 * @since  1.0.0
	 * @param  string $key Cache key
	 * @return mixed       Cached value or false if not found
	 */
	public function get( $key ) {
		if ( ! $this->driver ) {
			return false;
		}

		return $this->driver->get( $key );
	}

	/**
	 * Store value in cache
	 *
	 * @since  1.0.0
	 * @param  string $key   Cache key
	 * @param  mixed  $value Value to cache
	 * @param  int    $ttl   Time to live in seconds (default 3600)
	 * @return bool          True on success, false on failure
	 */
	public function set( $key, $value, $ttl = 3600 ) {
		if ( ! $this->driver ) {
			return false;
		}

		return $this->driver->set( $key, $value, $ttl );
	}

	/**
	 * Delete cached value by key
	 *
	 * @since  1.0.0
	 * @param  string $key Cache key
	 * @return bool        True on success, false on failure
	 */
	public function delete( $key ) {
		if ( ! $this->driver ) {
			return false;
		}

		return $this->driver->delete( $key );
	}

	/**
	 * Clear all cached values
	 *
	 * @since  1.0.0
	 * @return bool True on success, false on failure
	 */
	public function flush() {
		if ( ! $this->driver ) {
			return false;
		}

		SAW_LMS_Logger::init()->info( 'Cache flushed', array(
			'driver' => $this->get_driver_name(),
		) );

		return $this->driver->flush();
	}

	/**
	 * Check if key exists in cache
	 *
	 * @since  1.0.0
	 * @param  string $key Cache key
	 * @return bool        True if exists, false otherwise
	 */
	public function exists( $key ) {
		if ( ! $this->driver ) {
			return false;
		}

		return $this->driver->exists( $key );
	}

	/**
	 * Retrieve multiple cached values
	 *
	 * @since  1.0.0
	 * @param  array $keys Array of cache keys
	 * @return array       Associative array of key => value pairs
	 */
	public function get_multiple( $keys ) {
		if ( ! $this->driver ) {
			return array();
		}

		return $this->driver->get_multiple( $keys );
	}

	/**
	 * Store multiple values in cache
	 *
	 * @since  1.0.0
	 * @param  array $values Associative array of key => value pairs
	 * @param  int   $ttl    Time to live in seconds (default 3600)
	 * @return bool          True on success, false on failure
	 */
	public function set_multiple( $values, $ttl = 3600 ) {
		if ( ! $this->driver ) {
			return false;
		}

		return $this->driver->set_multiple( $values, $ttl );
	}

	/**
	 * Increment numeric value in cache
	 *
	 * @since  1.0.0
	 * @param  string $key    Cache key
	 * @param  int    $offset Amount to increment (default 1)
	 * @return int|false      New value on success, false on failure
	 */
	public function increment( $key, $offset = 1 ) {
		if ( ! $this->driver ) {
			return false;
		}

		return $this->driver->increment( $key, $offset );
	}

	/**
	 * Decrement numeric value in cache
	 *
	 * @since  1.0.0
	 * @param  string $key    Cache key
	 * @param  int    $offset Amount to decrement (default 1)
	 * @return int|false      New value on success, false on failure
	 */
	public function decrement( $key, $offset = 1 ) {
		if ( ! $this->driver ) {
			return false;
		}

		return $this->driver->decrement( $key, $offset );
	}

	/**
	 * Remember (get from cache or generate and cache)
	 *
	 * This is a very useful helper method that:
	 * 1. Checks if value exists in cache
	 * 2. If yes, returns cached value
	 * 3. If no, calls callback, caches result, and returns it
	 *
	 * Example usage:
	 * $stats = $cache->remember('group_stats_123', 900, function() use ($group_id) {
	 *     return expensive_calculation($group_id);
	 * });
	 *
	 * @since  1.0.0
	 * @param  string   $key      Cache key
	 * @param  int      $ttl      Time to live in seconds
	 * @param  callable $callback Function to generate value if not cached
	 * @return mixed              Cached or generated value
	 */
	public function remember( $key, $ttl, $callback ) {
		// Try to get from cache
		$value = $this->get( $key );

		// If found in cache, return it
		if ( false !== $value ) {
			return $value;
		}

		// Not in cache, generate value
		if ( ! is_callable( $callback ) ) {
			SAW_LMS_Logger::init()->error( 'Cache remember: Invalid callback', array(
				'key' => $key,
			) );
			return false;
		}

		try {
			// Call callback to generate value
			$value = call_user_func( $callback );

			// Cache the generated value
			$this->set( $key, $value, $ttl );

			return $value;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Cache remember: Callback failed', array(
				'key' => $key,
				'error' => $e->getMessage(),
			) );

			return false;
		}
	}

	/**
	 * Get cache statistics from active driver
	 *
	 * @since  1.0.0
	 * @return array|null Statistics or null if not available
	 */
	public function get_stats() {
		if ( ! $this->driver ) {
			return null;
		}

		// Check if driver has stats method
		if ( method_exists( $this->driver, 'get_stats' ) ) {
			return $this->driver->get_stats();
		}

		return array(
			'driver' => $this->get_driver_name(),
			'stats_available' => false,
		);
	}

	/**
	 * Force switch to a specific driver
	 *
	 * Useful for testing or manual override.
	 *
	 * @since  1.0.0
	 * @param  string $driver_name Driver name ('redis', 'database', 'transient')
	 * @return bool                True on success, false on failure
	 */
	public function switch_driver( $driver_name ) {
		if ( $this->init_specific_driver( $driver_name ) ) {
			update_option( 'saw_lms_cache_driver', $driver_name );
			
			SAW_LMS_Logger::init()->info( 'Cache driver switched', array(
				'new_driver' => $driver_name,
			) );

			return true;
		}

		return false;
	}

	/**
	 * Reset to automatic driver detection
	 *
	 * @since 1.0.0
	 */
	public function reset_to_auto() {
		update_option( 'saw_lms_cache_driver', 'auto' );
		$this->detect_and_init_driver();

		SAW_LMS_Logger::init()->info( 'Cache driver reset to auto-detection', array(
			'detected_driver' => $this->get_driver_name(),
		) );
	}

	/**
	 * Test all available cache drivers
	 *
	 * Useful for diagnostics and settings page.
	 *
	 * @since  1.0.0
	 * @return array Array of driver availability
	 */
	public function test_drivers() {
		$results = array();

		$drivers = array( 'redis', 'database', 'transient' );

		foreach ( $drivers as $driver_name ) {
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
				}

				$results[ $driver_name ] = array(
					'available' => $driver->is_available(),
					'name' => $driver->get_driver_name(),
				);

				// If available, do a quick functionality test
				if ( $results[ $driver_name ]['available'] ) {
					$test_key = 'saw_lms_driver_test';
					$test_value = time();

					$set_result = $driver->set( $test_key, $test_value, 60 );
					$get_result = $driver->get( $test_key );
					$driver->delete( $test_key );

					$results[ $driver_name ]['functional'] = (
						$set_result && 
						$get_result === $test_value
					);
				} else {
					$results[ $driver_name ]['functional'] = false;
				}

			} catch ( Exception $e ) {
				$results[ $driver_name ] = array(
					'available' => false,
					'functional' => false,
					'error' => $e->getMessage(),
				);
			}
		}

		return $results;
	}
}