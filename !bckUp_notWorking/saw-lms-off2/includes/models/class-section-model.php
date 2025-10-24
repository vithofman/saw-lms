<?php
/**
 * Section Model
 *
 * @package     SAW_LMS
 * @subpackage  Models
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Section_Model class
 *
 * Model for Section post type with database operations and caching.
 *
 * @since 1.0.0
 */
class SAW_LMS_Section_Model {

	/**
	 * Cache manager instance
	 *
	 * @var SAW_LMS_Cache_Manager
	 */
	private $cache_manager;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->cache_manager = SAW_LMS_Cache_Manager::get_instance();
	}

	/**
	 * Get section by ID
	 *
	 * @since  1.0.0
	 * @param  int  $section_id Section post ID.
	 * @param  bool $force_refresh Force cache refresh.
	 * @return array|null Section data or null if not found.
	 */
	public function get_section( $section_id, $force_refresh = false ) {
		$cache_key = 'saw_lms_section_' . $section_id;

		if ( ! $force_refresh ) {
			$cached = $this->cache_manager->get( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$post = get_post( $section_id );

		if ( ! $post || 'saw_lms_section' !== $post->post_type ) {
			return null;
		}

		$section_data = array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_content,
			'course_id'   => $post->post_parent,
			'order'       => $post->menu_order,
			'status'      => $post->post_status,
			'created_at'  => $post->post_date,
			'modified_at' => $post->post_modified,
		);

		$this->cache_manager->set( $cache_key, $section_data, 3600 );

		return $section_data;
	}

	/**
	 * Get all lessons for a section
	 *
	 * @since  1.0.0
	 * @param  int  $section_id Section post ID.
	 * @param  bool $force_refresh Force cache refresh.
	 * @return array Array of lesson IDs ordered by menu_order.
	 */
	public function get_section_lessons( $section_id, $force_refresh = false ) {
		$cache_key = 'saw_lms_section_lessons_' . $section_id;

		if ( ! $force_refresh ) {
			$cached = $this->cache_manager->get( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$lessons = get_posts(
			array(
				'post_type'      => 'saw_lms_lesson',
				'post_parent'    => $section_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		$this->cache_manager->set( $cache_key, $lessons, 3600 );

		return $lessons;
	}

	/**
	 * Get all quizzes for a section
	 *
	 * @since  1.0.0
	 * @param  int  $section_id Section post ID.
	 * @param  bool $force_refresh Force cache refresh.
	 * @return array Array of quiz IDs ordered by menu_order.
	 */
	public function get_section_quizzes( $section_id, $force_refresh = false ) {
		$cache_key = 'saw_lms_section_quizzes_' . $section_id;

		if ( ! $force_refresh ) {
			$cached = $this->cache_manager->get( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$quizzes = get_posts(
			array(
				'post_type'      => 'saw_lms_quiz',
				'post_parent'    => $section_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		$this->cache_manager->set( $cache_key, $quizzes, 3600 );

		return $quizzes;
	}

	/**
	 * Create new section
	 *
	 * @since  1.0.0
	 * @param  array $data Section data.
	 * @return int|WP_Error Section ID on success, WP_Error on failure.
	 */
	public function create_section( $data ) {
		$defaults = array(
			'title'       => '',
			'description' => '',
			'course_id'   => 0,
			'order'       => 0,
			'status'      => 'publish',
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Section title is required.', 'saw-lms' ) );
		}

		if ( empty( $data['course_id'] ) ) {
			return new WP_Error( 'missing_course', __( 'Course ID is required.', 'saw-lms' ) );
		}

		$course = get_post( $data['course_id'] );
		if ( ! $course || 'saw_lms_course' !== $course->post_type ) {
			return new WP_Error( 'invalid_course', __( 'Invalid course ID.', 'saw-lms' ) );
		}

		$section_id = wp_insert_post(
			array(
				'post_type'    => 'saw_lms_section',
				'post_title'   => sanitize_text_field( $data['title'] ),
				'post_content' => wp_kses_post( $data['description'] ),
				'post_parent'  => absint( $data['course_id'] ),
				'menu_order'   => absint( $data['order'] ),
				'post_status'  => sanitize_key( $data['status'] ),
			),
			true
		);

		if ( is_wp_error( $section_id ) ) {
			return $section_id;
		}

		$this->invalidate_section_cache( $section_id, $data['course_id'] );

		do_action( 'saw_lms_section_created', $section_id, $data );

		return $section_id;
	}

	/**
	 * Update existing section
	 *
	 * @since  1.0.0
	 * @param  int   $section_id Section post ID.
	 * @param  array $data Section data to update.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function update_section( $section_id, $data ) {
		$post = get_post( $section_id );

		if ( ! $post || 'saw_lms_section' !== $post->post_type ) {
			return new WP_Error( 'invalid_section', __( 'Invalid section ID.', 'saw-lms' ) );
		}

		$update_data = array(
			'ID' => $section_id,
		);

		if ( isset( $data['title'] ) ) {
			$update_data['post_title'] = sanitize_text_field( $data['title'] );
		}

		if ( isset( $data['description'] ) ) {
			$update_data['post_content'] = wp_kses_post( $data['description'] );
		}

		if ( isset( $data['order'] ) ) {
			$update_data['menu_order'] = absint( $data['order'] );
		}

		if ( isset( $data['status'] ) ) {
			$update_data['post_status'] = sanitize_key( $data['status'] );
		}

		$result = wp_update_post( $update_data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->invalidate_section_cache( $section_id, $post->post_parent );

		do_action( 'saw_lms_section_updated', $section_id, $data );

		return true;
	}

	/**
	 * Delete section
	 *
	 * @since  1.0.0
	 * @param  int  $section_id Section post ID.
	 * @param  bool $force_delete Force permanent deletion.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_section( $section_id, $force_delete = false ) {
		$post = get_post( $section_id );

		if ( ! $post || 'saw_lms_section' !== $post->post_type ) {
			return new WP_Error( 'invalid_section', __( 'Invalid section ID.', 'saw-lms' ) );
		}

		$course_id = $post->post_parent;

		$result = wp_delete_post( $section_id, $force_delete );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete section.', 'saw-lms' ) );
		}

		$this->invalidate_section_cache( $section_id, $course_id );

		do_action( 'saw_lms_section_deleted', $section_id, $force_delete );

		return true;
	}

	/**
	 * Invalidate section cache
	 *
	 * @since 1.0.0
	 * @param int $section_id Section post ID.
	 * @param int $course_id Course post ID.
	 */
	private function invalidate_section_cache( $section_id, $course_id ) {
		$this->cache_manager->delete( 'saw_lms_section_' . $section_id );
		$this->cache_manager->delete( 'saw_lms_section_lessons_' . $section_id );
		$this->cache_manager->delete( 'saw_lms_section_quizzes_' . $section_id );
		$this->cache_manager->delete( 'saw_lms_course_sections_' . $course_id );
	}
}