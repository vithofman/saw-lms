<?php
/**
 * Redis Cache Driver - FIXED VERSION
 * 
 * Implements caching using Redis with hybrid approach:
 * 1. First tries to use WP_Object_Cache (if Redis plugin is active)
 * 2. Falls back to direct Redis connection
 * 3. Provides best performance for high-traffic sites
 *
 * CRITICAL FIX: Uses unique prefix per website to prevent data collisions
 * on shared Redis servers (required by hosting providers like Hetzner).
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/cache/drivers
 * @since      1.0.0
 * @version    1.0.1 - Fixed unique prefix for shared Redis
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Redis_Driver Class
 * 
 * @since 1.0.0
 */
class SAW_LMS_Redis_Driver implements SAW_LMS_Cache_Driver {

	/**
	 * Redis connection instance
	 *
	 * @since  1.0.0
	 * @var    Redis|null
	 */
	private $redis = null;

	/**
	 * Whether to use WP Object Cache
	 *
	 * @since  1.0.0
	 * @var    bool
	 */
	private $use_wp_cache = false;

	/**
	 * Cache key prefix (UNIQUE per website!)
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private $prefix = '';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// CRITICAL: Set unique prefix BEFORE init connection
		$this->set_unique_prefix();
		$this->init_connection();
	}

	/**
	 * Set unique cache prefix for this website
	 *
	 * This is CRITICAL for shared Redis servers to prevent data collisions.
	 * 
	 * Priority:
	 * 1. SAW_LMS_REDIS_PREFIX constant (set in wp-config.php)
	 * 2. Auto-generated from site URL hash
	 *
	 * @since 1.0.1
	 */
	private function set_unique_prefix() {
		// Option 1: Manual prefix from wp-config.php (recommended)
		if ( defined( 'SAW_LMS_REDIS_PREFIX' ) && ! empty( SAW_LMS_REDIS_PREFIX ) ) {
			$this->prefix = SAW_LMS_REDIS_PREFIX;
			
			SAW_LMS_Logger::init()->debug( 'Redis: Using manual prefix from wp-config.php', array(
				'prefix' => $this->prefix,
			) );
			
			return;
		}

		// Option 2: Auto-generate unique prefix from site URL
		$site_url = get_site_url();
		$site_hash = substr( md5( $site_url ), 0, 8 ); // 8 characters is enough
		
		$this->prefix = 'saw_lms_' . $site_hash . '_';
		
		SAW_LMS_Logger::init()->info( 'Redis: Auto-generated unique prefix', array(
			'prefix'   => $this->prefix,
			'site_url' => $site_url,
			'note'     => 'Consider setting SAW_LMS_REDIS_PREFIX in wp-config.php for consistency',
		) );
	}

	/**
	 * Initialize Redis connection
	 *
	 * @since 1.0.0
	 */
	private function init_connection() {
		// Check if WP Object Cache is using Redis
		if ( $this->is_wp_object_cache_redis() ) {
			$this->use_wp_cache = true;
			SAW_LMS_Logger::init()->debug( 'Redis driver: Using WP_Object_Cache' );
			return;
		}

		// Try direct Redis connection
		if ( extension_loaded( 'redis' ) ) {
			try {
				$this->redis = new Redis();
				
				// Get Redis config from wp-config.php or use defaults
				$host = defined( 'SAW_LMS_REDIS_HOST' ) ? SAW_LMS_REDIS_HOST : '127.0.0.1';
				$port = defined( 'SAW_LMS_REDIS_PORT' ) ? SAW_LMS_REDIS_PORT : 6379;
				$timeout = defined( 'SAW_LMS_REDIS_TIMEOUT' ) ? SAW_LMS_REDIS_TIMEOUT : 2;
				$password = defined( 'SAW_LMS_REDIS_PASSWORD' ) ? SAW_LMS_REDIS_PASSWORD : null;
				$database = defined( 'SAW_LMS_REDIS_DATABASE' ) ? SAW_LMS_REDIS_DATABASE : 0;

				// Connect
				$connected = $this->redis->connect( $host, $port, $timeout );
				
				if ( ! $connected ) {
					throw new Exception( 'Failed to connect to Redis server' );
				}

				// Authenticate if password is set
				if ( $password ) {
					$this->redis->auth( $password );
				}

				// Select database
				if ( $database > 0 ) {
					$this->redis->select( $database );
				}

				SAW_LMS_Logger::init()->debug( 'Redis driver: Direct connection established', array(
					'host'     => $host,
					'port'     => $port,
					'database' => $database,
					'prefix'   => $this->prefix,
				) );

			} catch ( Exception $e ) {
				$this->redis = null;
				SAW_LMS_Logger::init()->warning( 'Redis driver: Connection failed', array(
					'error' => $e->getMessage(),
				) );
			}
		}
	}

	/**
	 * Check if WP Object Cache is using Redis
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	private function is_wp_object_cache_redis() {
		global $wp_object_cache;

		if ( ! isset( $wp_object_cache ) ) {
			return false;
		}

		// Check for Redis Object Cache plugin
		if ( method_exists( $wp_object_cache, 'redis_instance' ) ) {
			return true;
		}

		// Check for W3 Total Cache Redis
		if ( defined( 'W3TC' ) && method_exists( $wp_object_cache, '_get_cache' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get prefixed cache key
	 *
	 * @since  1.0.0
	 * @param  string $key Original key
	 * @return string      Prefixed key with unique website identifier
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
			if ( $this->use_wp_cache ) {
				return wp_cache_get( $prefixed_key, 'saw_lms' );
			}

			if ( $this->redis ) {
				$value = $this->redis->get( $prefixed_key );
				
				if ( false === $value ) {
					return false;
				}

				return maybe_unserialize( $value );
			}

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Redis get failed', array(
				'key'   => $key,
				'error' => $e->getMessage(),
			) );
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set( $key, $value, $ttl = 3600 ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			if ( $this->use_wp_cache ) {
				return wp_cache_set( $prefixed_key, $value, 'saw_lms', $ttl );
			}

			if ( $this->redis ) {
				$serialized = maybe_serialize( $value );
				
				if ( $ttl > 0 ) {
					return $this->redis->setex( $prefixed_key, $ttl, $serialized );
				}
				
				return $this->redis->set( $prefixed_key, $serialized );
			}

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Redis set failed', array(
				'key'   => $key,
				'ttl'   => $ttl,
				'error' => $e->getMessage(),
			) );
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete( $key ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			if ( $this->use_wp_cache ) {
				return wp_cache_delete( $prefixed_key, 'saw_lms' );
			}

			if ( $this->redis ) {
				return (bool) $this->redis->del( $prefixed_key );
			}

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Redis delete failed', array(
				'key'   => $key,
				'error' => $e->getMessage(),
			) );
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush() {
		try {
			if ( $this->use_wp_cache ) {
				// SECURITY: Only flush saw_lms group, not entire cache
				wp_cache_flush_group( 'saw_lms' );
				return true;
			}

			if ( $this->redis ) {
				// SECURITY: Only delete keys with OUR UNIQUE prefix
				// This is CRITICAL for shared Redis - never use FLUSHDB or FLUSHALL!
				$keys = $this->redis->keys( $this->prefix . '*' );
				
				if ( empty( $keys ) ) {
					return true;
				}

				// Delete in batches for performance
				$batch_size = 1000;
				$batches = array_chunk( $keys, $batch_size );
				
				foreach ( $batches as $batch ) {
					$this->redis->del( $batch );
				}

				return true;
			}

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Redis flush failed', array(
				'error' => $e->getMessage(),
			) );
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists( $key ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			if ( $this->use_wp_cache ) {
				return false !== wp_cache_get( $prefixed_key, 'saw_lms' );
			}

			if ( $this->redis ) {
				return (bool) $this->redis->exists( $prefixed_key );
			}

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Redis exists check failed', array(
				'key'   => $key,
				'error' => $e->getMessage(),
			) );
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_multiple( $keys ) {
		$results = array();

		try {
			if ( $this->use_wp_cache ) {
				foreach ( $keys as $key ) {
					$prefixed_key = $this->get_prefixed_key( $key );
					$value = wp_cache_get( $prefixed_key, 'saw_lms' );
					
					if ( false !== $value ) {
						$results[ $key ] = $value;
					}
				}

				return $results;
			}

			if ( $this->redis ) {
				$prefixed_keys = array_map( array( $this, 'get_prefixed_key' ), $keys );
				$values = $this->redis->mget( $prefixed_keys );

				foreach ( $keys as $index => $key ) {
					if ( isset( $values[ $index ] ) && false !== $values[ $index ] ) {
						$results[ $key ] = maybe_unserialize( $values[ $index ] );
					}
				}

				return $results;
			}

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Redis get_multiple failed', array(
				'keys_count' => count( $keys ),
				'error'      => $e->getMessage(),
			) );
		}

		return $results;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set_multiple( $values, $ttl = 3600 ) {
		try {
			if ( $this->use_wp_cache ) {
				$success = true;
				
				foreach ( $values as $key => $value ) {
					$prefixed_key = $this->get_prefixed_key( $key );
					
					if ( ! wp_cache_set( $prefixed_key, $value, 'saw_lms', $ttl ) ) {
						$success = false;
					}
				}

				return $success;
			}

			if ( $this->redis ) {
				// Use pipeline for better performance
				$this->redis->multi( Redis::PIPELINE );

				foreach ( $values as $key => $value ) {
					$prefixed_key = $this->get_prefixed_key( $key );
					$serialized = maybe_serialize( $value );

					if ( $ttl > 0 ) {
						$this->redis->setex( $prefixed_key, $ttl, $serialized );
					} else {
						$this->redis->set( $prefixed_key, $serialized );
					}
				}

				$this->redis->exec();
				return true;
			}

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Redis set_multiple failed', array(
				'values_count' => count( $values ),
				'error'        => $e->getMessage(),
			) );
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function increment( $key, $offset = 1 ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			if ( $this->use_wp_cache ) {
				// WP Object Cache doesn't have native increment for Redis
				$value = wp_cache_get( $prefixed_key, 'saw_lms' );
				$value = $value ? (int) $value : 0;
				$value += $offset;
				wp_cache_set( $prefixed_key, $value, 'saw_lms' );
				return $value;
			}

			if ( $this->redis ) {
				return $this->redis->incrBy( $prefixed_key, $offset );
			}

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Redis increment failed', array(
				'key'    => $key,
				'offset' => $offset,
				'error'  => $e->getMessage(),
			) );
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function decrement( $key, $offset = 1 ) {
		$prefixed_key = $this->get_prefixed_key( $key );

		try {
			if ( $this->use_wp_cache ) {
				// WP Object Cache doesn't have native decrement for Redis
				$value = wp_cache_get( $prefixed_key, 'saw_lms' );
				$value = $value ? (int) $value : 0;
				$value -= $offset;
				wp_cache_set( $prefixed_key, $value, 'saw_lms' );
				return $value;
			}

			if ( $this->redis ) {
				return $this->redis->decrBy( $prefixed_key, $offset );
			}

		} catch ( Exception $e ) {
			SAW_LMS_Logger::init()->error( 'Redis decrement failed', array(
				'key'    => $key,
				'offset' => $offset,
				'error'  => $e->getMessage(),
			) );
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_driver_name() {
		return 'redis';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available() {
		// WP Object Cache with Redis is available
		if ( $this->use_wp_cache ) {
			return true;
		}

		// Direct Redis connection is available
		if ( $this->redis instanceof Redis ) {
			try {
				// Test connection with ping
				$this->redis->ping();
				return true;
			} catch ( Exception $e ) {
				SAW_LMS_Logger::init()->warning( 'Redis ping failed', array(
					'error' => $e->getMessage(),
				) );
				return false;
			}
		}

		return false;
	}

	/**
	 * Get current prefix (for debugging)
	 *
	 * @since  1.0.1
	 * @return string Current cache prefix
	 */
	public function get_prefix() {
		return $this->prefix;
	}

	/**
	 * Destructor - close Redis connection
	 *
	 * @since 1.0.0
	 */
	public function __destruct() {
		if ( $this->redis instanceof Redis ) {
			try {
				$this->redis->close();
			} catch ( Exception $e ) {
				// Silent fail on destructor
			}
		}
	}
}