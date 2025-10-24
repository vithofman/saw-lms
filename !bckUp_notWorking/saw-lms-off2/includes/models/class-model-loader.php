<?php
/**
 * Model Loader
 *
 * @package     SAW_LMS
 * @subpackage  Models
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Model_Loader class
 *
 * Centralized loader for all model classes.
 *
 * @since 1.0.0
 */
class SAW_LMS_Model_Loader {

	/**
	 * Model instances
	 *
	 * @var array
	 */
	private static $models = array();

	/**
	 * Get Course model instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Course_Model
	 */
	public static function get_course_model() {
		if ( ! isset( self::$models['course'] ) ) {
			self::$models['course'] = new SAW_LMS_Course_Model();
		}

		return self::$models['course'];
	}

	/**
	 * Get Section model instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Section_Model
	 */
	public static function get_section_model() {
		if ( ! isset( self::$models['section'] ) ) {
			self::$models['section'] = new SAW_LMS_Section_Model();
		}

		return self::$models['section'];
	}

	/**
	 * Get Lesson model instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Lesson_Model
	 */
	public static function get_lesson_model() {
		if ( ! isset( self::$models['lesson'] ) ) {
			self::$models['lesson'] = new SAW_LMS_Lesson_Model();
		}

		return self::$models['lesson'];
	}

	/**
	 * Get Quiz model instance
	 *
	 * @since  1.0.0
	 * @return SAW_LMS_Quiz_Model
	 */
	public static function get_quiz_model() {
		if ( ! isset( self::$models['quiz'] ) ) {
			self::$models['quiz'] = new SAW_LMS_Quiz_Model();
		}

		return self::$models['quiz'];
	}

	/**
	 * Clear all model instances
	 *
	 * @since 1.0.0
	 */
	public static function clear_instances() {
		self::$models = array();
	}
}