<?php
/**
 * Lesson Model
 *
 * @package     SAW_LMS
 * @subpackage  Models
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * SAW_LMS_Lesson_Model class
 *
 * Model for Lesson post type with database operations and caching.
 *
 * @since 1.0.0
 */
class SAW_LMS_Lesson_Model {

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
	 * Get lesson by ID
	 *
	 * @since  1.0.0
	 * @param  int  $lesson_id Lesson post ID.
	 * @param  bool $force_refresh Force cache refresh.
	 * @return array|null Lesson data or null if not found.
	 */
	public function get_lesson( $lesson_id, $force_refresh = false ) {
		$cache_key = 'saw_lms_lesson_' . $lesson_id;

		if ( ! $force_refresh ) {
			$cached = $this->cache_manager->get( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$post = get_post( $lesson_id );

		if ( ! $post || 'saw_lms_lesson' !== $post->post_type ) {
			return null;
		}

		$lesson_data = array(
			'id'            => $post->ID,
			'title'         => $post->post_title,
			'slug'          => $post->post_name,
			'content'       => $post->post_content,
			'section_id'    => $post->post_parent,
			'order'         => $post->menu_order,
			'status'        => $post->post_status,
			'created_at'    => $post->post_date,
			'modified_at'   => $post->post_modified,
			'lesson_type'   => get_post_meta( $post->ID, '_saw_lms_lesson_type', true ),
			'video_url'     => get_post_meta( $post->ID, '_saw_lms_video_url', true ),
			'video_length'  => get_post_meta( $post->ID, '_saw_lms_video_length', true ),
			'attachments'   => $this->get_lesson_attachments( $post->ID ),
		);

		$this->cache_manager->set( $cache_key, $lesson_data, 3600 );

		return $lesson_data;
	}

	/**
	 * Get lesson attachments
	 *
	 * @since  1.0.0
	 * @param  int $lesson_id Lesson post ID.
	 * @return array Array of attachment data.
	 */
	private function get_lesson_attachments( $lesson_id ) {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_parent'    => $lesson_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$attachment_data = array();

		foreach ( $attachments as $attachment ) {
			$attachment_data[] = array(
				'id'       => $attachment->ID,
				'title'    => $attachment->post_title,
				'filename' => basename( get_attached_file( $attachment->ID ) ),
				'url'      => wp_get_attachment_url( $attachment->ID ),
				'filesize' => size_format( filesize( get_attached_file( $attachment->ID ) ) ),
			);
		}

		return $attachment_data;
	}

	/**
	 * Create new lesson
	 *
	 * @since  1.0.0
	 * @param  array $data Lesson data.
	 * @return int|WP_Error Lesson ID on success, WP_Error on failure.
	 */
	public function create_lesson( $data ) {
		$defaults = array(
			'title'        => '',
			'content'      => '',
			'section_id'   => 0,
			'order'        => 0,
			'status'       => 'publish',
			'lesson_type'  => 'text',
			'video_url'    => '',
			'video_length' => 0,
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Lesson title is required.', 'saw-lms' ) );
		}

		if ( empty( $data['section_id'] ) ) {
			return new WP_Error( 'missing_section', __( 'Section ID is required.', 'saw-lms' ) );
		}

		$section = get_post( $data['section_id'] );
		if ( ! $section || 'saw_lms_section' !== $section->post_type ) {
			return new WP_Error( 'invalid_section', __( 'Invalid section ID.', 'saw-lms' ) );
		}

		$lesson_id = wp_insert_post(
			array(
				'post_type'    => 'saw_lms_lesson',
				'post_title'   => sanitize_text_field( $data['title'] ),
				'post_content' => wp_kses_post( $data['content'] ),
				'post_parent'  => absint( $data['section_id'] ),
				'menu_order'   => absint( $data['order'] ),
				'post_status'  => sanitize_key( $data['status'] ),
			),
			true
		);

		if ( is_wp_error( $lesson_id ) ) {
			return $lesson_id;
		}

		update_post_meta( $lesson_id, '_saw_lms_lesson_type', sanitize_text_field( $data['lesson_type'] ) );

		if ( 'video' === $data['lesson_type'] ) {
			update_post_meta( $lesson_id, '_saw_lms_video_url', esc_url_raw( $data['video_url'] ) );
			update_post_meta( $lesson_id, '_saw_lms_video_length', absint( $data['video_length'] ) );
		}

		$this->invalidate_lesson_cache( $lesson_id, $data['section_id'] );

		do_action( 'saw_lms_lesson_created', $lesson_id, $data );

		return $lesson_id;
	}

	/**
	 * Update existing lesson
	 *
	 * @since  1.0.0
	 * @param  int   $lesson_id Lesson post ID.
	 * @param  array $data Lesson data to update.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function update_lesson( $lesson_id, $data ) {
		$post = get_post( $lesson_id );

		if ( ! $post || 'saw_lms_lesson' !== $post->post_type ) {
			return new WP_Error( 'invalid_lesson', __( 'Invalid lesson ID.', 'saw-lms' ) );
		}

		$update_data = array(
			'ID' => $lesson_id,
		);

		if ( isset( $data['title'] ) ) {
			$update_data['post_title'] = sanitize_text_field( $data['title'] );
		}

		if ( isset( $data['content'] ) ) {
			$update_data['post_content'] = wp_kses_post( $data['content'] );
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

		if ( isset( $data['lesson_type'] ) ) {
			update_post_meta( $lesson_id, '_saw_lms_lesson_type', sanitize_text_field( $data['lesson_type'] ) );
		}

		if ( isset( $data['video_url'] ) ) {
			update_post_meta( $lesson_id, '_saw_lms_video_url', esc_url_raw( $data['video_url'] ) );
		}

		if ( isset( $data['video_length'] ) ) {
			update_post_meta( $lesson_id, '_saw_lms_video_length', absint( $data['video_length'] ) );
		}

		$this->invalidate_lesson_cache( $lesson_id, $post->post_parent );

		do_action( 'saw_lms_lesson_updated', $lesson_id, $data );

		return true;
	}

	/**
	 * Delete lesson
	 *
	 * @since  1.0.0
	 * @param  int  $lesson_id Lesson post ID.
	 * @param  bool $force_delete Force permanent deletion.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_lesson( $lesson_id, $force_delete = false ) {
		$post = get_post( $lesson_id );

		if ( ! $post || 'saw_lms_lesson' !== $post->post_type ) {
			return new WP_Error( 'invalid_lesson', __( 'Invalid lesson ID.', 'saw-lms' ) );
		}

		$section_id = $post->post_parent;

		$result = wp_delete_post( $lesson_id, $force_delete );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete lesson.', 'saw-lms' ) );
		}

		$this->invalidate_lesson_cache( $lesson_id, $section_id );

		do_action( 'saw_lms_lesson_deleted', $lesson_id, $force_delete );

		return true;
	}

	/**
	 * Mark lesson as completed for user
	 *
	 * @since  1.0.0
	 * @param  int $lesson_id Lesson post ID.
	 * @param  int $user_id User ID.
	 * @return bool True on success, false on failure.
	 */
	public function mark_completed( $lesson_id, $user_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'saw_lms_progress';

		$section_id = get_post_field( 'post_parent', $lesson_id );
		$section    = get_post( $section_id );
		$course_id  = $section ? $section->post_parent : 0;

		$result = $wpdb->replace(
			$table,
			array(
				'user_id'       => absint( $user_id ),
				'course_id'     => absint( $course_id ),
				'lesson_id'     => absint( $lesson_id ),
				'status'        => 'completed',
				'completed_at'  => current_time( 'mysql' ),
				'last_accessed' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( $result ) {
			$this->cache_manager->delete( 'saw_lms_user_progress_' . $user_id . '_' . $course_id );
			do_action( 'saw_lms_lesson_completed', $lesson_id, $user_id, $course_id );
			return true;
		}

		return false;
	}

	/**
	 * Invalidate lesson cache
	 *
	 * @since 1.0.0
	 * @param int $lesson_id Lesson post ID.
	 * @param int $section_id Section post ID.
	 */
	private function invalidate_lesson_cache( $lesson_id, $section_id ) {
		$this->cache_manager->delete( 'saw_lms_lesson_' . $lesson_id );
		$this->cache_manager->delete( 'saw_lms_section_lessons_' . $section_id );
	}
}