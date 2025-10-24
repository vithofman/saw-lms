<?php
/**
 * Cache Driver Interface
 *
 * Defines the contract for all cache drivers in the SAW LMS plugin.
 * Every cache driver (Redis, Database, Transient) must implement this interface.
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
 * SAW_LMS_Cache_Driver Interface
 *
 * @since 1.0.0
 */
interface SAW_LMS_Cache_Driver {

	/**
	 * Retrieve cached value by key
	 *
	 * @since  1.0.0
	 * @param  string $key Cache key
	 * @return mixed       Cached value or false if not found/expired
	 */
	public function get( $key );

	/**
	 * Store value in cache
	 *
	 * @since  1.0.0
	 * @param  string $key   Cache key
	 * @param  mixed  $value Value to cache
	 * @param  int    $ttl   Time to live in seconds (default 3600)
	 * @return bool          True on success, false on failure
	 */
	public function set( $key, $value, $ttl = 3600 );

	/**
	 * Delete cached value by key
	 *
	 * @since  1.0.0
	 * @param  string $key Cache key
	 * @return bool        True on success, false on failure
	 */
	public function delete( $key );

	/**
	 * Clear all cached values
	 *
	 * @since  1.0.0
	 * @return bool True on success, false on failure
	 */
	public function flush();

	/**
	 * Check if key exists in cache
	 *
	 * @since  1.0.0
	 * @param  string $key Cache key
	 * @return bool        True if exists and not expired, false otherwise
	 */
	public function exists( $key );

	/**
	 * Retrieve multiple cached values by keys
	 *
	 * @since  1.0.0
	 * @param  array $keys Array of cache keys
	 * @return array       Associative array of key => value pairs
	 */
	public function get_multiple( $keys );

	/**
	 * Store multiple values in cache
	 *
	 * @since  1.0.0
	 * @param  array $values Associative array of key => value pairs
	 * @param  int   $ttl    Time to live in seconds (default 3600)
	 * @return bool          True on success, false on failure
	 */
	public function set_multiple( $values, $ttl = 3600 );

	/**
	 * Increment numeric value in cache
	 *
	 * @since  1.0.0
	 * @param  string $key    Cache key
	 * @param  int    $offset Amount to increment (default 1)
	 * @return int|false      New value on success, false on failure
	 */
	public function increment( $key, $offset = 1 );

	/**
	 * Decrement numeric value in cache
	 *
	 * @since  1.0.0
	 * @param  string $key    Cache key
	 * @param  int    $offset Amount to decrement (default 1)
	 * @return int|false      New value on success, false on failure
	 */
	public function decrement( $key, $offset = 1 );

	/**
	 * Get driver name
	 *
	 * @since  1.0.0
	 * @return string Driver name (e.g., 'redis', 'database', 'transient')
	 */
	public function get_driver_name();

	/**
	 * Check if driver is available and functional
	 *
	 * This method should verify all requirements for the driver:
	 * - PHP extension loaded
	 * - Connection successful
	 * - Database table exists (for DB driver)
	 * - etc.
	 *
	 * @since  1.0.0
	 * @return bool True if driver is available, false otherwise
	 */
	public function is_available();
}
