<?php
/**
 * Error Handler
 *
 * Centralized error handling system that catches PHP errors, exceptions, and fatal errors.
 * Logs them to database and files, sends email notifications for critical errors.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/core
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Error_Handler Class
 *
 * Singleton class for handling errors
 *
 * @since 1.0.0
 */
class SAW_LMS_Error_Handler {

	/**
	 * The single instance of the class
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS_Error_Handler
	 */
	private static $instance = null;

	/**
	 * Logger instance
	 *
	 * @since  1.0.0
	 * @var    SAW_LMS_Logger
	 */
	private $logger;

	/**
	 * Whether handlers are set up
	 *
	 * @since  1.0.0
	 * @var    bool
	 */
	private $handlers_setup = false;

	/**
	 * Error type mappings
	 *
	 * @since  1.0.0
	 * @var    array
	 */
	private $error_types = array(
		E_ERROR             => 'error',
		E_WARNING           => 'warning',
		E_PARSE             => 'error',
		E_NOTICE            => 'notice',
		E_CORE_ERROR        => 'critical',
		E_CORE_WARNING      => 'warning',
		E_COMPILE_ERROR     => 'critical',
		E_COMPILE_WARNING   => 'warning',
		E_USER_ERROR        => 'error',
		E_USER_WARNING      => 'warning',
		E_USER_NOTICE       => 'notice',
		E_STRICT            => 'info',
		E_RECOVERABLE_ERROR => 'error',
		E_DEPRECATED        => 'info',
		E_USER_DEPRECATED   => 'info',
	);

	/**
	 * Get the singleton instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Error_Handler
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
		// Logger will be set later when it's available
		$this->logger = null;
	}

	/**
	 * Set logger instance
	 *
	 * @since 1.0.0
	 * @param SAW_LMS_Logger $logger Logger instance
	 */
	public function set_logger( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Setup error handlers
	 *
	 * @since 1.0.0
	 */
	public function setup_handlers() {
		if ( $this->handlers_setup ) {
			return;
		}

		// Set custom error handler
		set_error_handler( array( $this, 'handle_php_error' ) );

		// Set custom exception handler
		set_exception_handler( array( $this, 'handle_exception' ) );

		// Set shutdown function to catch fatal errors
		register_shutdown_function( array( $this, 'handle_shutdown' ) );

		$this->handlers_setup = true;
	}

	/**
	 * Handle PHP errors
	 *
	 * @since  1.0.0
	 * @param  int    $errno      Error level
	 * @param  string $errstr     Error message
	 * @param  string $errfile    File where error occurred
	 * @param  int    $errline    Line number where error occurred
	 * @return bool
	 */
	public function handle_php_error( $errno, $errstr, $errfile, $errline ) {
		// Don't handle errors if error reporting is turned off
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}

		// Get error type
		$type = isset( $this->error_types[ $errno ] ) ? $this->error_types[ $errno ] : 'error';

		// Create context
		$context = array(
			'errno' => $errno,
			'file'  => $errfile,
			'line'  => $errline,
		);

		// Log the error
		$this->log_error( $type, $errstr, $context );

		// Don't execute PHP internal error handler
		return true;
	}

	/**
	 * Handle uncaught exceptions
	 *
	 * @since 1.0.0
	 * @param Exception|Throwable $exception The exception
	 */
	public function handle_exception( $exception ) {
		$context = array(
			'class' => get_class( $exception ),
			'code'  => $exception->getCode(),
			'file'  => $exception->getFile(),
			'line'  => $exception->getLine(),
			'trace' => $exception->getTraceAsString(),
		);

		$message = sprintf(
			'Uncaught %s: %s',
			get_class( $exception ),
			$exception->getMessage()
		);

		$this->log_error( 'critical', $message, $context );
	}

	/**
	 * Handle fatal errors on shutdown
	 *
	 * @since 1.0.0
	 */
	public function handle_shutdown() {
		$error = error_get_last();

		if ( null === $error ) {
			return;
		}

		// Check if it's a fatal error
		$fatal_errors = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR );

		if ( ! in_array( $error['type'], $fatal_errors, true ) ) {
			return;
		}

		$context = array(
			'errno' => $error['type'],
			'file'  => $error['file'],
			'line'  => $error['line'],
		);

		$this->log_error( 'critical', $error['message'], $context );
	}

	/**
	 * Log error to database and files
	 *
	 * @since 1.0.0
	 * @param string $type    Error type (emergency, alert, critical, error, warning, notice, info, debug)
	 * @param string $message Error message
	 * @param array  $context Additional context
	 */
	public function log_error( $type, $message, $context = array() ) {
		global $wpdb;

		// Sanitize type
		$allowed_types = array( 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug' );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			$type = 'error';
		}

		// Get current user ID
		$user_id = get_current_user_id();

		// Get IP address
		$ip_address = $this->get_client_ip();

		// Get current URL
		$url = $this->get_current_url();

		// Prepare context as JSON
		$context_json = ! empty( $context ) ? wp_json_encode( $context ) : null;

		// Extract file and line from context if available
		$file = isset( $context['file'] ) ? $context['file'] : null;
		$line = isset( $context['line'] ) ? $context['line'] : null;

		// Insert into database
		$table_name = $wpdb->prefix . 'saw_lms_error_log';

		$wpdb->insert(
			$table_name,
			array(
				'error_type' => $type,
				'message'    => $message,
				'context'    => $context_json,
				'file'       => $file,
				'line'       => $line,
				'user_id'    => $user_id ? $user_id : null,
				'ip_address' => $ip_address,
				'url'        => $url,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);

		// Log to file if logger is available
		if ( $this->logger && method_exists( $this->logger, 'log' ) ) {
			$this->logger->log( $type, $message, $context );
		}

		// Send email for critical errors
		if ( in_array( $type, array( 'critical', 'alert', 'emergency' ), true ) ) {
			$this->send_critical_error_email( $type, $message, $context );
		}
	}

	/**
	 * Send email notification for critical errors
	 *
	 * @since 1.0.0
	 * @param string $type    Error type
	 * @param string $message Error message
	 * @param array  $context Error context
	 */
	private function send_critical_error_email( $type, $message, $context ) {
		// Create transient key for this specific error message
		$transient_key = 'saw_lms_critical_email_' . md5( $type . $message );

		// Check if we already sent email for this error in last hour
		if ( get_transient( $transient_key ) ) {
			return;
		}

		// Set transient to prevent spam (1 hour)
		set_transient( $transient_key, true, HOUR_IN_SECONDS );

		// Get admin email
		$admin_email = get_option( 'admin_email' );

		if ( empty( $admin_email ) ) {
			return;
		}

		// Prepare email
		$subject = sprintf(
			'[%s] Critical Error: %s',
			get_bloginfo( 'name' ),
			wp_trim_words( $message, 10 )
		);

		$body = sprintf(
			"A critical error has occurred on your site:\n\n" .
			"Type: %s\n" .
			"Message: %s\n" .
			"Time: %s\n\n",
			strtoupper( $type ),
			$message,
			current_time( 'mysql' )
		);

		// Add context details
		if ( ! empty( $context ) ) {
			$body .= "Details:\n";

			if ( isset( $context['file'] ) ) {
				$body .= "File: {$context['file']}\n";
			}

			if ( isset( $context['line'] ) ) {
				$body .= "Line: {$context['line']}\n";
			}

			if ( isset( $context['trace'] ) ) {
				$body .= "\nStack Trace:\n{$context['trace']}\n";
			}
		}

		$body .= "\n--\n";
		$body .= "This is an automated message from SAW LMS Plugin.\n";
		$body .= 'Site: ' . get_bloginfo( 'url' );

		// Send email
		wp_mail( $admin_email, $subject, $body );
	}

	/**
	 * Get client IP address
	 *
	 * @since  1.0.0
	 * @return string
	 */
	private function get_client_ip() {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
				return sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			}
		}

		return 'UNKNOWN';
	}

	/**
	 * Get current URL
	 *
	 * @since  1.0.0
	 * @return string
	 */
	private function get_current_url() {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$url = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			return home_url( $url );
		}

		return '';
	}

	/**
	 * Get error stats
	 *
	 * @since  1.0.0
	 * @param  int $days Number of days to get stats for
	 * @return array
	 */
	public function get_error_stats( $days = 7 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'saw_lms_error_log';
		$date_from  = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Get counts by type
		$stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT error_type, COUNT(*) as count 
				FROM {$table_name} 
				WHERE created_at >= %s 
				GROUP BY error_type 
				ORDER BY count DESC",
				$date_from
			),
			ARRAY_A
		);

		return $stats;
	}

	/**
	 * Get recent errors
	 *
	 * @since  1.0.0
	 * @param  int    $limit Number of errors to retrieve
	 * @param  string $type Filter by error type (optional)
	 * @return array
	 */
	public function get_recent_errors( $limit = 50, $type = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'saw_lms_error_log';

		if ( $type ) {
			$errors = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name} 
					WHERE error_type = %s 
					ORDER BY created_at DESC 
					LIMIT %d",
					$type,
					$limit
				),
				ARRAY_A
			);
		} else {
			$errors = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name} 
					ORDER BY created_at DESC 
					LIMIT %d",
					$limit
				),
				ARRAY_A
			);
		}

		return $errors;
	}

	/**
	 * Clear old error logs
	 *
	 * @since 1.0.0
	 * @param int $days Delete logs older than X days
	 * @return int Number of deleted rows
	 */
	public function clear_old_logs( $days = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'saw_lms_error_log';
		$date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE created_at < %s",
				$date_limit
			)
		);

		return $deleted;
	}
}
