<?php
/**
 * Logger
 * 
 * PSR-3 compatible logger that writes to files and WordPress debug log
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/utilities
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Logger Class
 * 
 * PSR-3 compatible logger implementation
 * 
 * @since 1.0.0
 */
class SAW_LMS_Logger {

	/**
	 * The single instance of the class
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS_Logger
	 */
	private static $instance = null;

	/**
	 * Log directory path
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private $log_dir;

	/**
	 * Log file path
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private $log_file;

	/**
	 * PSR-3 log levels
	 *
	 * @since  1.0.0
	 * @var    array
	 */
	private $log_levels = array(
		'emergency' => 0,
		'alert'     => 1,
		'critical'  => 2,
		'error'     => 3,
		'warning'   => 4,
		'notice'    => 5,
		'info'      => 6,
		'debug'     => 7,
	);

	/**
	 * Get the singleton instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Logger
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
		$this->setup_log_directory();
	}

	/**
	 * Setup log directory and file
	 *
	 * @since 1.0.0
	 */
	private function setup_log_directory() {
		$upload_dir = wp_upload_dir();
		$this->log_dir = $upload_dir['basedir'] . '/saw-lms/logs';

		// Create directory if it doesn't exist
		if ( ! file_exists( $this->log_dir ) ) {
			wp_mkdir_p( $this->log_dir );
		}

		// Set log file with date
		$this->log_file = $this->log_dir . '/saw-lms-' . date( 'Y-m-d' ) . '.log';
	}

	/**
	 * System is unusable
	 *
	 * @since 1.0.0
	 * @param string $message
	 * @param array  $context
	 */
	public function emergency( $message, array $context = array() ) {
		$this->log( 'emergency', $message, $context );
	}

	/**
	 * Action must be taken immediately
	 *
	 * @since 1.0.0
	 * @param string $message
	 * @param array  $context
	 */
	public function alert( $message, array $context = array() ) {
		$this->log( 'alert', $message, $context );
	}

	/**
	 * Critical conditions
	 *
	 * @since 1.0.0
	 * @param string $message
	 * @param array  $context
	 */
	public function critical( $message, array $context = array() ) {
		$this->log( 'critical', $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action
	 *
	 * @since 1.0.0
	 * @param string $message
	 * @param array  $context
	 */
	public function error( $message, array $context = array() ) {
		$this->log( 'error', $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors
	 *
	 * @since 1.0.0
	 * @param string $message
	 * @param array  $context
	 */
	public function warning( $message, array $context = array() ) {
		$this->log( 'warning', $message, $context );
	}

	/**
	 * Normal but significant events
	 *
	 * @since 1.0.0
	 * @param string $message
	 * @param array  $context
	 */
	public function notice( $message, array $context = array() ) {
		$this->log( 'notice', $message, $context );
	}

	/**
	 * Interesting events
	 *
	 * @since 1.0.0
	 * @param string $message
	 * @param array  $context
	 */
	public function info( $message, array $context = array() ) {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Detailed debug information
	 *
	 * @since 1.0.0
	 * @param string $message
	 * @param array  $context
	 */
	public function debug( $message, array $context = array() ) {
		$this->log( 'debug', $message, $context );
	}

	/**
	 * Logs with an arbitrary level
	 *
	 * @since 1.0.0
	 * @param string $level
	 * @param string $message
	 * @param array  $context
	 */
	public function log( $level, $message, array $context = array() ) {
		// Validate log level
		if ( ! isset( $this->log_levels[ $level ] ) ) {
			$level = 'info';
		}

		// Interpolate context values into message
		$message = $this->interpolate( $message, $context );

		// Format log entry
		$log_entry = $this->format_log_entry( $level, $message, $context );

		// Write to custom log file
		$this->write_to_file( $log_entry );

		// Write to WordPress debug log if enabled
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			$this->write_to_wp_debug_log( $log_entry );
		}
	}

	/**
	 * Interpolate context values into message placeholders
	 *
	 * @since  1.0.0
	 * @param  string $message
	 * @param  array  $context
	 * @return string
	 */
	private function interpolate( $message, array $context = array() ) {
		// Build a replacement array with braces around the context keys
		$replace = array();
		foreach ( $context as $key => $val ) {
			// Check that the value can be cast to string
			if ( ! is_array( $val ) && ( ! is_object( $val ) || method_exists( $val, '__toString' ) ) ) {
				$replace[ '{' . $key . '}' ] = $val;
			}
		}

		// Interpolate replacement values into the message and return
		return strtr( $message, $replace );
	}

	/**
	 * Format log entry
	 *
	 * @since  1.0.0
	 * @param  string $level
	 * @param  string $message
	 * @param  array  $context
	 * @return string
	 */
	private function format_log_entry( $level, $message, array $context = array() ) {
		$timestamp = current_time( 'mysql' );
		$level_str = strtoupper( $level );

		// Start with timestamp and level
		$entry = "[{$timestamp}] {$level_str}: {$message}";

		// Add context if available
		if ( ! empty( $context ) ) {
			// Remove common keys that are already in message
			$context_to_log = $context;
			unset( $context_to_log['exception'] );

			if ( ! empty( $context_to_log ) ) {
				$entry .= ' | Context: ' . wp_json_encode( $context_to_log );
			}
		}

		// Add exception if present
		if ( isset( $context['exception'] ) && $context['exception'] instanceof Exception ) {
			$exception = $context['exception'];
			$entry .= sprintf(
				' | Exception: %s in %s:%d',
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine()
			);
		}

		return $entry . PHP_EOL;
	}

	/**
	 * Write log entry to file
	 *
	 * @since 1.0.0
	 * @param string $entry
	 */
	private function write_to_file( $entry ) {
		// Check if file is writable
		if ( ! is_writable( $this->log_dir ) ) {
			return;
		}

		// Write to file
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$file = fopen( $this->log_file, 'a' );
		if ( $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			fwrite( $file, $entry );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $file );
		}

		// Rotate log files if current file is too large (>10MB)
		if ( file_exists( $this->log_file ) && filesize( $this->log_file ) > 10485760 ) {
			$this->rotate_log_file();
		}
	}

	/**
	 * Write to WordPress debug log
	 *
	 * @since 1.0.0
	 * @param string $entry
	 */
	private function write_to_wp_debug_log( $entry ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[SAW LMS] ' . trim( $entry ) );
	}

	/**
	 * Rotate log file
	 *
	 * @since 1.0.0
	 */
	private function rotate_log_file() {
		if ( ! file_exists( $this->log_file ) ) {
			return;
		}

		// Create backup filename with timestamp
		$backup_file = $this->log_dir . '/saw-lms-' . date( 'Y-m-d-His' ) . '.log';

		// Rename current log file
		rename( $this->log_file, $backup_file );

		// Cleanup old log files (keep last 30 days)
		$this->cleanup_old_logs( 30 );
	}

	/**
	 * Cleanup old log files
	 *
	 * @since 1.0.0
	 * @param int $days Number of days to keep
	 */
	private function cleanup_old_logs( $days = 30 ) {
		$files = glob( $this->log_dir . '/saw-lms-*.log' );
		
		if ( ! $files ) {
			return;
		}

		$now = time();
		$threshold = $days * DAY_IN_SECONDS;

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				$file_time = filemtime( $file );
				if ( ( $now - $file_time ) > $threshold ) {
					unlink( $file );
				}
			}
		}
	}

	/**
	 * Get recent logs
	 *
	 * @since  1.0.0
	 * @param  int $lines Number of lines to retrieve
	 * @return array
	 */
	public function get_recent_logs( $lines = 100 ) {
		if ( ! file_exists( $this->log_file ) ) {
			return array();
		}

		// Read last N lines from file
		$file = fopen( $this->log_file, 'r' );
		if ( ! $file ) {
			return array();
		}

		// Use tail-like approach
		$log_lines = array();
		$buffer = 4096;
		fseek( $file, -1, SEEK_END );

		// Read backwards
		$output = '';
		$chunk = '';
		while ( ftell( $file ) > 0 && count( $log_lines ) < $lines ) {
			$seek = min( ftell( $file ), $buffer );
			fseek( $file, -$seek, SEEK_CUR );
			$chunk = fread( $file, $seek ) . $chunk;
			fseek( $file, -mb_strlen( $chunk, '8bit' ), SEEK_CUR );
			
			$log_lines = explode( "\n", $chunk );
		}

		fclose( $file );

		// Reverse to get chronological order
		$log_lines = array_reverse( $log_lines );

		// Return last N lines
		return array_slice( $log_lines, -$lines );
	}

	/**
	 * Clear all logs
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function clear_logs() {
		$files = glob( $this->log_dir . '/saw-lms-*.log' );
		
		if ( ! $files ) {
			return false;
		}

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}

		return true;
	}

	/**
	 * Get log file path
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_log_file() {
		return $this->log_file;
	}

	/**
	 * Get log directory path
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_log_dir() {
		return $this->log_dir;
	}

	/**
	 * Check if logging is enabled
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function is_enabled() {
		return is_writable( $this->log_dir );
	}
}