<?php
/**
 * Database Cache Driver
 *
 * Implements caching using WordPress database (wp_saw_lms_cache table).
 * Features:
 * - Lazy cleanup: Expired records deleted on get()
 * - Bulk cleanup via WP-Cron (daily)
 * - All queries use $wpdb->prepare() for security
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
 * SAW_LMS_Db_Driver Class
 *
 * @since 1.0.0
 */
class SAW_LMS_Db_Driver implements SAW_LMS_Cache_Driver {

	/**
	 * Database table name
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private $table_name;

	/**
	 * WordPress database object
	 *
	 * @since  1.0.0
	 * @var    wpdb
	 */
	private $wpdb;

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
		global $wpdb;

		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'saw_lms_cache';

		// Schedule cleanup cron if not already scheduled
		if ( ! wp_next_scheduled( 'saw_lms_cleanup_cache' ) ) {
			wp_schedule_event( time(), 'daily', 'saw_lms_cleanup_cache' );
		}

		// Hook cleanup function
		add_action( 'saw_lms_cleanup_cache', array( $this, 'cleanup_expired' ) );
	}

	/**
	 * Get prefixed cache key
	 *
	 * @since  1.0.0
	 * @param  string $key Original key
	 * @return string      Prefixed key
	 */
	private function get_prefixed_key( $key ) {
		return $this->prefix . $key;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get( $key ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			$row = $this->wpdb->get_row(
				$this->wpdb->prepare(
					"SELECT cache_value, expires_at FROM {$this->table_name} WHERE cache_key = %s LIMIT 1",
					$prefixed_key
				)
			);

			if ( ! $row ) {
				return false;
			}

			// Check if expired (lazy cleanup)
			$expires_at = strtotime( $row->expires_at );

			if ( $expires_at < time() ) {
				// Delete expired entry
				$this->delete( $key );
				return false;
			}

			// Unserialize and return
			return maybe_unserialize( $row->cache_value );

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache get failed',
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
			$serialized = maybe_serialize( $value );
			$expires_at = date( 'Y-m-d H:i:s', time() + $ttl );

			// Check if key already exists
			$exists = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT id FROM {$this->table_name} WHERE cache_key = %s LIMIT 1",
					$prefixed_key
				)
			);

			if ( $exists ) {
				// Update existing record
				$result = $this->wpdb->update(
					$this->table_name,
					array(
						'cache_value' => $serialized,
						'expires_at'  => $expires_at,
					),
					array( 'cache_key' => $prefixed_key ),
					array( '%s', '%s' ),
					array( '%s' )
				);

				return false !== $result;
			}

			// Insert new record
			$result = $this->wpdb->insert(
				$this->table_name,
				array(
					'cache_key'   => $prefixed_key,
					'cache_value' => $serialized,
					'expires_at'  => $expires_at,
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s' )
			);

			return false !== $result;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache set failed',
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
			$result = $this->wpdb->delete(
				$this->table_name,
				array( 'cache_key' => $prefixed_key ),
				array( '%s' )
			);

			return false !== $result;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache delete failed',
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
		try {
			// Delete only keys with our prefix
			$result = $this->wpdb->query(
				$this->wpdb->prepare(
					"DELETE FROM {$this->table_name} WHERE cache_key LIKE %s",
					$this->wpdb->esc_like( $this->prefix ) . '%'
				)
			);

			return false !== $result;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache flush failed',
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
			$count = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_name} 
					WHERE cache_key = %s 
					AND expires_at > NOW() 
					LIMIT 1",
					$prefixed_key
				)
			);

			return $count > 0;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache exists check failed',
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
			$prefixed_keys = array_map( array( $this, 'get_prefixed_key' ), $keys );

			// Prepare placeholders
			$placeholders = implode( ', ', array_fill( 0, count( $prefixed_keys ), '%s' ) );

			$rows = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT cache_key, cache_value, expires_at FROM {$this->table_name} 
					WHERE cache_key IN ($placeholders)",
					$prefixed_keys
				)
			);

			if ( empty( $rows ) ) {
				return $results;
			}

			$current_time = time();

			foreach ( $rows as $row ) {
				$expires_at = strtotime( $row->expires_at );

				// Skip expired entries
				if ( $expires_at < $current_time ) {
					continue;
				}

				// Remove prefix from key for return array
				$original_key             = str_replace( $this->prefix, '', $row->cache_key );
				$results[ $original_key ] = maybe_unserialize( $row->cache_value );
			}

			return $results;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache get_multiple failed',
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
			$expires_at = date( 'Y-m-d H:i:s', time() + $ttl );
			$success    = true;

			foreach ( $values as $key => $value ) {
				if ( ! $this->set( $key, $value, $ttl ) ) {
					$success = false;
				}
			}

			return $success;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache set_multiple failed',
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
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			// Get current value
			$value = $this->get( $key );
			$value = $value ? (int) $value : 0;

			// Increment
			$new_value = $value + $offset;

			// Save back
			if ( $this->set( $key, $new_value ) ) {
				return $new_value;
			}
		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache increment failed',
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
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			// Get current value
			$value = $this->get( $key );
			$value = $value ? (int) $value : 0;

			// Decrement
			$new_value = $value - $offset;

			// Save back
			if ( $this->set( $key, $new_value ) ) {
				return $new_value;
			}
		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache decrement failed',
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
	 * Cleanup expired cache entries (called by WP-Cron)
	 *
	 * This method runs daily and removes all expired cache entries
	 * to keep the database table clean.
	 *
	 * @since  1.0.0
	 * @return int Number of deleted rows
	 */
	public function cleanup_expired() {
		try {
			$deleted = $this->wpdb->query(
				"DELETE FROM {$this->table_name} WHERE expires_at < NOW()"
			);

			if ( $deleted > 0 ) {
				SAW_LMS_Logger::init()->info(
					'Database cache cleanup completed',
					array(
						'deleted_count' => $deleted,
					)
				);
			}

			return $deleted;

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache cleanup failed',
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
	 * Returns statistics about cache usage:
	 * - Total entries
	 * - Active entries (not expired)
	 * - Expired entries
	 * - Total size (approximate)
	 *
	 * @since  1.0.0
	 * @return array Cache statistics
	 */
	public function get_stats() {
		$stats = array(
			'total'      => 0,
			'active'     => 0,
			'expired'    => 0,
			'size_bytes' => 0,
		);

		try {
			$stats['total'] = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_name} WHERE cache_key LIKE %s",
					$this->wpdb->esc_like( $this->prefix ) . '%'
				)
			);

			$stats['active'] = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_name} 
					WHERE cache_key LIKE %s AND expires_at > NOW()",
					$this->wpdb->esc_like( $this->prefix ) . '%'
				)
			);

			$stats['expired'] = $stats['total'] - $stats['active'];

			$stats['size_bytes'] = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT SUM(LENGTH(cache_value)) FROM {$this->table_name} 
					WHERE cache_key LIKE %s",
					$this->wpdb->esc_like( $this->prefix ) . '%'
				)
			);

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error(
				'Database cache stats failed',
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
		return 'database';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available() {
		// Check if table exists
		$table_exists = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$this->table_name
			)
		);

		if ( $table_exists !== $this->table_name ) {
			SAW_LMS_Logger::init()->warning(
				'Database cache table does not exist',
				array(
					'table' => $this->table_name,
				)
			);
			return false;
		}

		return true;
	}
}
