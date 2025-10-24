<?php
/**
 * Course Model
 *
 * @package     SAW_LMS
 * @subpackage  Models
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Course_Model class
 *
 * Model for Course post type with database operations and caching.
 *
 * @since 1.0.0
 */
class SAW_LMS_Course_Model {

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
	 * Get course by ID
	 *
	 * @since  1.0.0
	 * @param  int  $course_id Course post ID.
	 * @param  bool $force_refresh Force cache refresh.
	 * @return array|null Course data or null if not found.
	 */
	public function get_course( $course_id, $force_refresh = false ) {
		$cache_key = 'saw_lms_course_' . $course_id;

		if ( ! $force_refresh ) {
			$cached = $this->cache_manager->get( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$post = get_post( $course_id );

		if ( ! $post || 'saw_lms_course' !== $post->post_type ) {
			return null;
		}

		$course_data = array(
			'id'               => $post->ID,
			'title'            => $post->post_title,
			'slug'             => $post->post_name,
			'description'      => $post->post_content,
			'excerpt'          => $post->post_excerpt,
			'status'           => $post->post_status,
			'author_id'        => $post->post_author,
			'created_at'       => $post->post_date,
			'modified_at'      => $post->post_modified,
			'level'            => get_post_meta( $post->ID, '_saw_lms_course_level', true ),
			'duration'         => get_post_meta( $post->ID, '_saw_lms_course_duration', true ),
			'price'            => get_post_meta( $post->ID, '_saw_lms_course_price', true ),
			'thumbnail_url'    => get_the_post_thumbnail_url( $post->ID, 'large' ),
			'categories'       => wp_get_post_terms( $post->ID, 'saw_lms_course_category', array( 'fields' => 'names' ) ),
			'tags'             => wp_get_post_terms( $post->ID, 'saw_lms_course_tag', array( 'fields' => 'names' ) ),
			'enrollment_count' => $this->get_enrollment_count( $post->ID ),
		);

		$this->cache_manager->set( $cache_key, $course_data, 3600 );

		return $course_data;
	}

	/**
	 * Get all sections for a course
	 *
	 * @since  1.0.0
	 * @param  int  $course_id Course post ID.
	 * @param  bool $force_refresh Force cache refresh.
	 * @return array Array of section IDs ordered by menu_order.
	 */
	public function get_course_sections( $course_id, $force_refresh = false ) {
		$cache_key = 'saw_lms_course_sections_' . $course_id;

		if ( ! $force_refresh ) {
			$cached = $this->cache_manager->get( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$sections = get_posts(
			array(
				'post_type'      => 'saw_lms_section',
				'post_parent'    => $course_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		$this->cache_manager->set( $cache_key, $sections, 3600 );

		return $sections;
	}

	/**
	 * Get enrollment count for course
	 *
	 * @since  1.0.0
	 * @param  int  $course_id Course post ID.
	 * @param  bool $force_refresh Force cache refresh.
	 * @return int Enrollment count.
	 */
	public function get_enrollment_count( $course_id, $force_refresh = false ) {
		global $wpdb;

		$cache_key = 'saw_lms_course_enrollment_count_' . $course_id;

		if ( ! $force_refresh ) {
			$cached = $this->cache_manager->get( $cache_key );
			if ( false !== $cached ) {
				return (int) $cached;
			}
		}

		$table = $wpdb->prefix . 'saw_lms_enrollments';
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE course_id = %d AND status = 'active'",
				$course_id
			)
		);

		$count = absint( $count );

		$this->cache_manager->set( $cache_key, $count, 1800 );

		return $count;
	}

	/**
	 * Create new course
	 *
	 * @since  1.0.0
	 * @param  array $data Course data.
	 * @return int|WP_Error Course ID on success, WP_Error on failure.
	 */
	public function create_course( $data ) {
		$defaults = array(
			'title'       => '',
			'description' => '',
			'excerpt'     => '',
			'status'      => 'draft',
			'author_id'   => get_current_user_id(),
			'level'       => 'beginner',
			'duration'    => 0,
			'price'       => 0,
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Course title is required.', 'saw-lms' ) );
		}

		$course_id = wp_insert_post(
			array(
				'post_type'    => 'saw_lms_course',
				'post_title'   => sanitize_text_field( $data['title'] ),
				'post_content' => wp_kses_post( $data['description'] ),
				'post_excerpt' => sanitize_textarea_field( $data['excerpt'] ),
				'post_status'  => sanitize_key( $data['status'] ),
				'post_author'  => absint( $data['author_id'] ),
			),
			true
		);

		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		update_post_meta( $course_id, '_saw_lms_course_level', sanitize_text_field( $data['level'] ) );
		update_post_meta( $course_id, '_saw_lms_course_duration', absint( $data['duration'] ) );
		update_post_meta( $course_id, '_saw_lms_course_price', floatval( $data['price'] ) );

		$this->invalidate_course_cache( $course_id );

		do_action( 'saw_lms_course_created', $course_id, $data );

		return $course_id;
	}

	/**
	 * Update existing course
	 *
	 * @since  1.0.0
	 * @param  int   $course_id Course post ID.
	 * @param  array $data Course data to update.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function update_course( $course_id, $data ) {
		$post = get_post( $course_id );

		if ( ! $post || 'saw_lms_course' !== $post->post_type ) {
			return new WP_Error( 'invalid_course', __( 'Invalid course ID.', 'saw-lms' ) );
		}

		$update_data = array(
			'ID' => $course_id,
		);

		if ( isset( $data['title'] ) ) {
			$update_data['post_title'] = sanitize_text_field( $data['title'] );
		}

		if ( isset( $data['description'] ) ) {
			$update_data['post_content'] = wp_kses_post( $data['description'] );
		}

		if ( isset( $data['excerpt'] ) ) {
			$update_data['post_excerpt'] = sanitize_textarea_field( $data['excerpt'] );
		}

		if ( isset( $data['status'] ) ) {
			$update_data['post_status'] = sanitize_key( $data['status'] );
		}

		$result = wp_update_post( $update_data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( isset( $data['level'] ) ) {
			update_post_meta( $course_id, '_saw_lms_course_level', sanitize_text_field( $data['level'] ) );
		}

		if ( isset( $data['duration'] ) ) {
			update_post_meta( $course_id, '_saw_lms_course_duration', absint( $data['duration'] ) );
		}

		if ( isset( $data['price'] ) ) {
			update_post_meta( $course_id, '_saw_lms_course_price', floatval( $data['price'] ) );
		}

		$this->invalidate_course_cache( $course_id );

		do_action( 'saw_lms_course_updated', $course_id, $data );

		return true;
	}

	/**
	 * Delete course
	 *
	 * @since  1.0.0
	 * @param  int  $course_id Course post ID.
	 * @param  bool $force_delete Force permanent deletion.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_course( $course_id, $force_delete = false ) {
		$post = get_post( $course_id );

		if ( ! $post || 'saw_lms_course' !== $post->post_type ) {
			return new WP_Error( 'invalid_course', __( 'Invalid course ID.', 'saw-lms' ) );
		}

		$result = wp_delete_post( $course_id, $force_delete );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete course.', 'saw-lms' ) );
		}

		$this->invalidate_course_cache( $course_id );

		do_action( 'saw_lms_course_deleted', $course_id, $force_delete );

		return true;
	}

	/**
	 * Invalidate course cache
	 *
	 * @since 1.0.0
	 * @param int $course_id Course post ID.
	 */
	private function invalidate_course_cache( $course_id ) {
		$this->cache_manager->delete( 'saw_lms_course_' . $course_id );
		$this->cache_manager->delete( 'saw_lms_course_sections_' . $course_id );
		$this->cache_manager->delete( 'saw_lms_course_enrollment_count_' . $course_id );
	}
}