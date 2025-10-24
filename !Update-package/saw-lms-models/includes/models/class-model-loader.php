<?php
/**
 * Model Loader
 *
 * Autoloader for all SAW LMS model classes.
 * Loads Course, Section, Lesson, and Quiz models.
 *
 * This class is responsible for loading all model files and ensuring
 * they are available for use throughout the plugin.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/models
 * @since      3.0.0
 * @version    3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Model_Loader Class
 *
 * Loads all model classes for SAW LMS plugin.
 *
 * @since 3.0.0
 */
class SAW_LMS_Model_Loader {

	/**
	 * Models directory path
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	private static $models_dir;

	/**
	 * Loaded models
	 *
	 * @since  3.0.0
	 * @var    array
	 */
	private static $loaded_models = array();

	/**
	 * Load all models
	 *
	 * This method loads all model files required for the plugin.
	 * Models are loaded in dependency order to avoid errors.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public static function load_models() {
		// Set models directory.
		self::$models_dir = SAW_LMS_PLUGIN_DIR . 'includes/models/';

		// Check if models directory exists.
		if ( ! file_exists( self::$models_dir ) ) {
			if ( class_exists( 'SAW_LMS_Logger' ) ) {
				$logger = SAW_LMS_Logger::init();
				$logger->warning(
					'Models directory not found',
					array( 'path' => self::$models_dir )
				);
			}
			return;
		}

		// Define models to load (in order).
		$models = array(
			'class-course-model.php',
			'class-section-model.php',
			'class-lesson-model.php',
			'class-quiz-model.php',
		);

		// Load each model.
		foreach ( $models as $model_file ) {
			self::load_model( $model_file );
		}

		// Log loaded models.
		if ( class_exists( 'SAW_LMS_Logger' ) ) {
			$logger = SAW_LMS_Logger::init();
			$logger->info(
				'Models loaded successfully',
				array(
					'count'  => count( self::$loaded_models ),
					'models' => self::$loaded_models,
				)
			);
		}

		/**
		 * Fires after all models are loaded.
		 *
		 * @since 3.0.0
		 */
		do_action( 'saw_lms_models_loaded' );
	}

	/**
	 * Load a single model file
	 *
	 * Loads a model file and tracks it in the loaded models array.
	 *
	 * @since  3.0.0
	 * @param  string $model_file Model filename.
	 * @return bool               True if loaded successfully, false otherwise.
	 */
	private static function load_model( $model_file ) {
		$file_path = self::$models_dir . $model_file;

		// Check if file exists.
		if ( ! file_exists( $file_path ) ) {
			if ( class_exists( 'SAW_LMS_Logger' ) ) {
				$logger = SAW_LMS_Logger::init();
				$logger->warning(
					'Model file not found',
					array(
						'file' => $model_file,
						'path' => $file_path,
					)
				);
			}
			return false;
		}

		// Load the file.
		require_once $file_path;

		// Track loaded model.
		self::$loaded_models[] = $model_file;

		return true;
	}

	/**
	 * Get loaded models
	 *
	 * Returns an array of all loaded model files.
	 *
	 * @since  3.0.0
	 * @return array Array of loaded model filenames.
	 */
	public static function get_loaded_models() {
		return self::$loaded_models;
	}

	/**
	 * Check if a model is loaded
	 *
	 * Checks if a specific model file has been loaded.
	 *
	 * @since  3.0.0
	 * @param  string $model_file Model filename to check.
	 * @return bool               True if loaded, false otherwise.
	 */
	public static function is_model_loaded( $model_file ) {
		return in_array( $model_file, self::$loaded_models, true );
	}
}
