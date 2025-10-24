<?php
/**
 * Transient Cache Driver
 *
 * Implements caching using WordPress Transients API.
 * This is the fallback driver - always available on any WordPress installation.
 * Transients are stored in wp_options table (or object cache if available).
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/cache/drivers
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Transient_Driver Class
 *
 * @since 1.0.0
 */
class SAW_LMS_Transient_Driver implements SAW_LMS_Cache_Driver {

	/**
	 * Cache key prefix
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private $prefix = 'saw_lms_';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Nothing to initialize - Transients API is always available
	}

	/**
	 * Get prefixed cache key
	 *
	 * @since  1.0.0
	 * @param  string $key Original key
	 * @return string      Prefixed key
	 */
	private function get_prefixed_key( $key ) {
		// WordPress transient keys are max 172 characters
		// Our prefix is 8 chars, leaving 164 for actual key
		$prefixed = $this->prefix . $key;

		if ( strlen( $prefixed ) > 172 ) {
			// Hash long keys
			$prefixed = $this->prefix . md5( $key );
		}

		return $prefixed;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get( $key ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			$value = get_transient( $prefixed_key );

			// get_transient returns false if not found or expired
			if ( false === $value ) {
				return false;
			}

			return $value;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache get failed',
				array(
					'key'   => $key,
					'error' => $e->getMessage(),
				)
			);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set( $key, $value, $ttl = 3600 ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			// WordPress transients max expiration is ~2 years
			// Limit to prevent issues
			$ttl = min( $ttl, YEAR_IN_SECONDS * 2 );

			return set_transient( $prefixed_key, $value, $ttl );

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache set failed',
				array(
					'key'   => $key,
					'ttl'   => $ttl,
					'error' => $e->getMessage(),
				)
			);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete( $key ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			return delete_transient( $prefixed_key );

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache delete failed',
				array(
					'key'   => $key,
					'error' => $e->getMessage(),
				)
			);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush() {
		global $wpdb;

		try {
			// Delete all transients with our prefix
			// Transients are stored as: _transient_{key} and _transient_timeout_{key}
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} 
					WHERE option_name LIKE %s 
					OR option_name LIKE %s",
					$wpdb->esc_like( '_transient_' . $this->prefix ) . '%',
					$wpdb->esc_like( '_transient_timeout_' . $this->prefix ) . '%'
				)
			);

			SAW_LMS_Logger::init()->debug(
				'Transient cache flushed',
				array(
					'deleted_options' => $deleted,
				)
			);

			return true;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache flush failed',
				array(
					'error' => $e->getMessage(),
				)
			);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists( $key ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			// get_transient returns false if not exists or expired
			$value = get_transient( $prefixed_key );

			return false !== $value;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache exists check failed',
				array(
					'key'   => $key,
					'error' => $e->getMessage(),
				)
			);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_multiple( $keys ) {
		$results = array();

		try {
			// Transients API doesn't have batch get, so we loop
			foreach ( $keys as $key ) {
				$value = $this->get( $key );

				if ( false !== $value ) {
					$results[ $key ] = $value;
				}
			}

			return $results;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache get_multiple failed',
				array(
					'keys_count' => count( $keys ),
					'error'      => $e->getMessage(),
				)
			);
		}

		return $results;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set_multiple( $values, $ttl = 3600 ) {
		try {
			$success = true;

			// Transients API doesn't have batch set, so we loop
			foreach ( $values as $key => $value ) {
				if ( ! $this->set( $key, $value, $ttl ) ) {
					$success = false;
				}
			}

			return $success;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache set_multiple failed',
				array(
					'values_count' => count( $values ),
					'error'        => $e->getMessage(),
				)
			);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function increment( $key, $offset = 1 ) {
		try {
			// Get current value
			$value = $this->get( $key );
			$value = $value ? (int) $value : 0;

			// Increment
			$new_value = $value + $offset;

			// Save back (keep same TTL by using default)
			if ( $this->set( $key, $new_value, 3600 ) ) {
				return $new_value;
			}
		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache increment failed',
				array(
					'key'    => $key,
					'offset' => $offset,
					'error'  => $e->getMessage(),
				)
			);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function decrement( $key, $offset = 1 ) {
		try {
			// Get current value
			$value = $this->get( $key );
			$value = $value ? (int) $value : 0;

			// Decrement
			$new_value = $value - $offset;

			// Save back (keep same TTL by using default)
			if ( $this->set( $key, $new_value, 3600 ) ) {
				return $new_value;
			}
		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache decrement failed',
				array(
					'key'    => $key,
					'offset' => $offset,
					'error'  => $e->getMessage(),
				)
			);
		}

		return false;
	}

	/**
	 * Cleanup expired transients (called by WP-Cron)
	 *
	 * WordPress has its own cleanup mechanism, but we can help
	 * by specifically targeting our transients.
	 *
	 * @since  1.0.0
	 * @return int Number of deleted transients
	 */
	public function cleanup_expired() {
		global $wpdb;

		try {
			// Find expired transient timeout keys
			$time = time();

			$expired_keys = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT option_name FROM {$wpdb->options} 
					WHERE option_name LIKE %s 
					AND option_value < %d 
					LIMIT 100",
					$wpdb->esc_like( '_transient_timeout_' . $this->prefix ) . '%',
					$time
				)
			);

			if ( empty( $expired_keys ) ) {
				return 0;
			}

			$deleted = 0;

			foreach ( $expired_keys as $timeout_key ) {
				// Get the transient key (remove _transient_timeout_ prefix)
				$transient_key = str_replace( '_transient_timeout_', '_transient_', $timeout_key );
				$key           = str_replace( '_transient_', '', $transient_key );

				// Delete both the value and timeout
				if ( delete_transient( $key ) ) {
					++$deleted;
				}
			}

			if ( $deleted > 0 ) {
				SAW_LMS_Logger::init()->info(
					'Transient cache cleanup completed',
					array(
						'deleted_count' => $deleted,
					)
				);
			}

			return $deleted;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache cleanup failed',
				array(
					'error' => $e->getMessage(),
				)
			);
		}

		return 0;
	}

	/**
	 * Get cache statistics
	 *
	 * Returns statistics about transient cache usage.
	 *
	 * @since  1.0.0
	 * @return array Cache statistics
	 */
	public function get_stats() {
		global $wpdb;

		$stats = array(
			'total'      => 0,
			'active'     => 0,
			'expired'    => 0,
			'size_bytes' => 0,
		);

		try {
			// Count total transients (value + timeout = 2 rows per transient)
			$total_options = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} 
					WHERE option_name LIKE %s",
					$wpdb->esc_like( '_transient_' . $this->prefix ) . '%'
				)
			);

			$stats['total'] = (int) ( $total_options / 2 );

			// Count expired transients
			$time          = time();
			$expired_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} 
					WHERE option_name LIKE %s 
					AND option_value < %d",
					$wpdb->esc_like( '_transient_timeout_' . $this->prefix ) . '%',
					$time
				)
			);

			$stats['expired'] = (int) $expired_count;
			$stats['active']  = $stats['total'] - $stats['expired'];

			// Approximate size (only value options, not timeout options)
			$stats['size_bytes'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
					WHERE option_name LIKE %s 
					AND option_name NOT LIKE %s",
					$wpdb->esc_like( '_transient_' . $this->prefix ) . '%',
					$wpdb->esc_like( '_transient_timeout_' ) . '%'
				)
			);

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Transient cache stats failed',
				array(
					'error' => $e->getMessage(),
				)
			);
		}

		return $stats;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_driver_name() {
		return 'transient';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available() {
		// Transients are always available in WordPress
		return true;
	}
}
