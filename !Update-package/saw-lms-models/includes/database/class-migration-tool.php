<?php
/**
 * Migration Tool
 *
 * Tool for migrating data from wp_postmeta to structured tables.
 * This is optional and primarily for future use or for sites with existing data.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/database
 * @since      3.0.0
 * @version    3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SAW_LMS_Migration_Tool Class
 *
 * Handles migration of data from postmeta to structured tables.
 *
 * @since 3.0.0
 */
class SAW_LMS_Migration_Tool {

	/**
	 * Logger instance
	 *
	 * @since  3.0.0
	 * @var    SAW_LMS_Logger
	 */
	private static $logger;

	/**
	 * Initialize logger
	 *
	 * @since  3.0.0
	 * @return void
	 */
	private static function init_logger() {
		if ( ! self::$logger && class_exists( 'SAW_LMS_Logger' ) ) {
			self::$logger = SAW_LMS_Logger::init();
		}
	}

	/**
	 * Migrate all content
	 *
	 * Migrates courses, sections, lessons, and quizzes in sequence.
	 *
	 * @since  3.0.0
	 * @return array Migration results summary.
	 */
	public static function migrate_all() {
		self::init_logger();

		$results = array(
			'courses'  => self::migrate_courses(),
			'sections' => self::migrate_sections(),
			'lessons'  => self::migrate_lessons(),
			'quizzes'  => self::migrate_quizzes(),
		);

		if ( self::$logger ) {
			self::$logger->info(
				'Migration completed',
				array( 'results' => $results )
			);
		}

		return $results;
	}

	/**
	 * Migrate courses from postmeta to structured table
	 *
	 * Retrieves all courses and migrates their meta data to wp_saw_lms_courses.
	 *
	 * @since  3.0.0
	 * @return array Migration results (success/fail counts).
	 */
	public static function migrate_courses() {
		self::init_logger();

		$results = array(
			'total'   => 0,
			'success' => 0,
			'failed'  => 0,
			'errors'  => array(),
		);

		// Get all course posts.
		$courses = get_posts(
			array(
				'post_type'      => 'saw_course',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$results['total'] = count( $courses );

		if ( empty( $courses ) ) {
			if ( self::$logger ) {
				self::$logger->info( 'No courses found to migrate' );
			}
			return $results;
		}

		// Load course fields config.
		$fields_config_file = SAW_LMS_PLUGIN_DIR . 'includes/config/course-fields.php';

		if ( ! file_exists( $fields_config_file ) ) {
			$results['errors'][] = 'Course fields config file not found';
			return $results;
		}

		$fields_config = include $fields_config_file;

		// Migrate each course.
		foreach ( $courses as $course_post ) {
			$post_id = $course_post->ID;
			$data    = array();

			// Extract all meta fields.
			foreach ( $fields_config as $meta_box ) {
				if ( ! isset( $meta_box['fields'] ) ) {
					continue;
				}

				foreach ( $meta_box['fields'] as $field_key => $field ) {
					// Get meta value.
					$meta_value = get_post_meta( $post_id, $field_key, true );

					// Remove prefix '_saw_lms_' to get column name.
					$column_name = str_replace( '_saw_lms_', '', $field_key );

					// Handle different field types.
					if ( 'checkbox' === $field['type'] ) {
						$data[ $column_name ] = ! empty( $meta_value ) ? 1 : 0;
					} elseif ( 'number' === $field['type'] ) {
						$data[ $column_name ] = ! empty( $meta_value ) ? floatval( $meta_value ) : 0;
					} else {
						$data[ $column_name ] = ! empty( $meta_value ) ? $meta_value : '';
					}
				}
			}

			// Save to structured table.
			$result = SAW_LMS_Course_Model::save( $post_id, $data );

			if ( $result ) {
				++$results['success'];
			} else {
				++$results['failed'];
				$results['errors'][] = "Failed to migrate course ID: {$post_id}";
			}
		}

		if ( self::$logger ) {
			self::$logger->info(
				'Courses migration completed',
				array( 'results' => $results )
			);
		}

		return $results;
	}

	/**
	 * Migrate sections from postmeta to structured table
	 *
	 * @since  3.0.0
	 * @return array Migration results.
	 */
	public static function migrate_sections() {
		self::init_logger();

		$results = array(
			'total'   => 0,
			'success' => 0,
			'failed'  => 0,
			'errors'  => array(),
		);

		// Get all section posts.
		$sections = get_posts(
			array(
				'post_type'      => 'saw_section',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$results['total'] = count( $sections );

		if ( empty( $sections ) ) {
			if ( self::$logger ) {
				self::$logger->info( 'No sections found to migrate' );
			}
			return $results;
		}

		// Load section fields config.
		$fields_config_file = SAW_LMS_PLUGIN_DIR . 'includes/config/section-fields.php';

		if ( ! file_exists( $fields_config_file ) ) {
			$results['errors'][] = 'Section fields config file not found';
			return $results;
		}

		$fields_config = include $fields_config_file;

		// Migrate each section.
		foreach ( $sections as $section_post ) {
			$post_id = $section_post->ID;
			$data    = array();

			// Extract all meta fields.
			foreach ( $fields_config as $meta_box ) {
				if ( ! isset( $meta_box['fields'] ) ) {
					continue;
				}

				foreach ( $meta_box['fields'] as $field_key => $field ) {
					$meta_value  = get_post_meta( $post_id, $field_key, true );
					$column_name = str_replace( '_saw_lms_', '', $field_key );

					if ( 'checkbox' === $field['type'] ) {
						$data[ $column_name ] = ! empty( $meta_value ) ? 1 : 0;
					} elseif ( 'number' === $field['type'] ) {
						$data[ $column_name ] = ! empty( $meta_value ) ? floatval( $meta_value ) : 0;
					} else {
						$data[ $column_name ] = ! empty( $meta_value ) ? $meta_value : '';
					}
				}
			}

			// Save to structured table.
			$result = SAW_LMS_Section_Model::save( $post_id, $data );

			if ( $result ) {
				++$results['success'];
			} else {
				++$results['failed'];
				$results['errors'][] = "Failed to migrate section ID: {$post_id}";
			}
		}

		if ( self::$logger ) {
			self::$logger->info(
				'Sections migration completed',
				array( 'results' => $results )
			);
		}

		return $results;
	}

	/**
	 * Migrate lessons from postmeta to structured table
	 *
	 * @since  3.0.0
	 * @return array Migration results.
	 */
	public static function migrate_lessons() {
		self::init_logger();

		$results = array(
			'total'   => 0,
			'success' => 0,
			'failed'  => 0,
			'errors'  => array(),
		);

		// Get all lesson posts.
		$lessons = get_posts(
			array(
				'post_type'      => 'saw_lesson',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$results['total'] = count( $lessons );

		if ( empty( $lessons ) ) {
			if ( self::$logger ) {
				self::$logger->info( 'No lessons found to migrate' );
			}
			return $results;
		}

		// Load lesson fields config.
		$fields_config_file = SAW_LMS_PLUGIN_DIR . 'includes/config/lesson-fields.php';

		if ( ! file_exists( $fields_config_file ) ) {
			$results['errors'][] = 'Lesson fields config file not found';
			return $results;
		}

		$fields_config = include $fields_config_file;

		// Migrate each lesson.
		foreach ( $lessons as $lesson_post ) {
			$post_id = $lesson_post->ID;
			$data    = array();

			// Extract all meta fields.
			foreach ( $fields_config as $meta_box ) {
				if ( ! isset( $meta_box['fields'] ) ) {
					continue;
				}

				foreach ( $meta_box['fields'] as $field_key => $field ) {
					$meta_value  = get_post_meta( $post_id, $field_key, true );
					$column_name = str_replace( '_saw_lms_', '', $field_key );

					if ( 'checkbox' === $field['type'] ) {
						$data[ $column_name ] = ! empty( $meta_value ) ? 1 : 0;
					} elseif ( 'number' === $field['type'] ) {
						$data[ $column_name ] = ! empty( $meta_value ) ? floatval( $meta_value ) : 0;
					} else {
						$data[ $column_name ] = ! empty( $meta_value ) ? $meta_value : '';
					}
				}
			}

			// Save to structured table.
			$result = SAW_LMS_Lesson_Model::save( $post_id, $data );

			if ( $result ) {
				++$results['success'];
			} else {
				++$results['failed'];
				$results['errors'][] = "Failed to migrate lesson ID: {$post_id}";
			}
		}

		if ( self::$logger ) {
			self::$logger->info(
				'Lessons migration completed',
				array( 'results' => $results )
			);
		}

		return $results;
	}

	/**
	 * Migrate quizzes from postmeta to structured table
	 *
	 * @since  3.0.0
	 * @return array Migration results.
	 */
	public static function migrate_quizzes() {
		self::init_logger();

		$results = array(
			'total'   => 0,
			'success' => 0,
			'failed'  => 0,
			'errors'  => array(),
		);

		// Get all quiz posts.
		$quizzes = get_posts(
			array(
				'post_type'      => 'saw_quiz',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$results['total'] = count( $quizzes );

		if ( empty( $quizzes ) ) {
			if ( self::$logger ) {
				self::$logger->info( 'No quizzes found to migrate' );
			}
			return $results;
		}

		// Load quiz fields config.
		$fields_config_file = SAW_LMS_PLUGIN_DIR . 'includes/config/quiz-fields.php';

		if ( ! file_exists( $fields_config_file ) ) {
			$results['errors'][] = 'Quiz fields config file not found';
			return $results;
		}

		$fields_config = include $fields_config_file;

		// Migrate each quiz.
		foreach ( $quizzes as $quiz_post ) {
			$post_id = $quiz_post->ID;
			$data    = array();

			// Extract all meta fields.
			foreach ( $fields_config as $meta_box ) {
				if ( ! isset( $meta_box['fields'] ) ) {
					continue;
				}

				foreach ( $meta_box['fields'] as $field_key => $field ) {
					$meta_value  = get_post_meta( $post_id, $field_key, true );
					$column_name = str_replace( '_saw_lms_', '', $field_key );

					if ( 'checkbox' === $field['type'] ) {
						$data[ $column_name ] = ! empty( $meta_value ) ? 1 : 0;
					} elseif ( 'number' === $field['type'] ) {
						$data[ $column_name ] = ! empty( $meta_value ) ? floatval( $meta_value ) : 0;
					} else {
						$data[ $column_name ] = ! empty( $meta_value ) ? $meta_value : '';
					}
				}
			}

			// Save to structured table.
			$result = SAW_LMS_Quiz_Model::save( $post_id, $data );

			if ( $result ) {
				++$results['success'];
			} else {
				++$results['failed'];
				$results['errors'][] = "Failed to migrate quiz ID: {$post_id}";
			}
		}

		if ( self::$logger ) {
			self::$logger->info(
				'Quizzes migration completed',
				array( 'results' => $results )
			);
		}

		return $results;
	}
}
