<?php
/**
 * Lesson Custom Post Type
 *
 * Handles registration and functionality for the Lesson CPT.
 * REFACTORED in v3.0.0: Config-based meta boxes using lesson-fields.php
 * FIXED in v3.1.4: Removed enqueue_admin_assets() - now handled centrally by class-admin-assets.php
 * UPDATED in v3.2.7: Moved fields below Gutenberg editor using edit_form_after_editor hook.
 *
 * @package     SAW_LMS
 * @subpackage  Post_Types
 * @since       2.1.0
 * @version     3.2.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Lesson Class
 *
 * Manages the Lesson custom post type including registration,
 * meta boxes, admin columns, and lesson-specific functionality.
 *
 * UPDATED in v3.0.0: Refactored to use config-based meta boxes.
 * FIXED in v3.1.4: Removed duplicate asset enqueueing.
 * UPDATED in v3.2.7: Fields now render below Gutenberg editor.
 *
 * @since 2.1.0
 */
class SAW_LMS_Lesson {

	/**
	 * Post type slug
	 *
	 * @var string
	 */
	const POST_TYPE = 'saw_lesson';

	/**
	 * Lesson types
	 *
	 * @var array
	 */
	const LESSON_TYPES = array(
		'video'      => 'Video',
		'text'       => 'Text/Article',
		'document'   => 'Document/PDF',
		'assignment' => 'Assignment',
	);

	/**
	 * Singleton instance
	 *
	 * @var SAW_LMS_Lesson|null
	 */
	private static $instance = null;

	/**
	 * Fields configuration
	 *
	 * Loaded from includes/config/lesson-fields.php
	 *
	 * @since 3.0.0
	 * @var array
	 */
	private $fields_config = array();

	/**
	 * Get singleton instance
	 *
	 * @return SAW_LMS_Lesson
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
	 * Register hooks for the Lesson CPT.
	 *
	 * UPDATED in v3.0.0: Load fields config.
	 * UPDATED in v3.1.4: Removed enqueue hook.
	 * UPDATED in v3.2.7: Removed add_meta_boxes hook, added edit_form_after_editor hook.
	 *
	 * @since 2.1.0
	 */
	private function __construct() {
		// Load fields configuration.
		$config_file = SAW_LMS_PLUGIN_DIR . 'includes/config/lesson-fields.php';
		if ( file_exists( $config_file ) ) {
			$this->fields_config = include $config_file;
		}

		// Register post type.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Render fields below editor (v3.2.7).
		add_action( 'edit_form_after_editor', array( $this, 'render_fields_below_editor' ) );

		// Save meta data.
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

		// Admin columns.
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );

		// NOTE: Admin assets are now handled centrally by class-admin-assets.php (v3.1.4).
	}

	/**
	 * Register Lesson post type
	 *
	 * UPDATED in v3.1.4: Changed show_in_menu to false (menu managed by class-admin-menu.php).
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Lessons', 'Post Type General Name', 'saw-lms' ),
			'singular_name'         => _x( 'Lesson', 'Post Type Singular Name', 'saw-lms' ),
			'menu_name'             => __( 'Lessons', 'saw-lms' ),
			'name_admin_bar'        => __( 'Lesson', 'saw-lms' ),
			'archives'              => __( 'Lesson Archives', 'saw-lms' ),
			'attributes'            => __( 'Lesson Attributes', 'saw-lms' ),
			'parent_item_colon'     => __( 'Parent Lesson:', 'saw-lms' ),
			'all_items'             => __( 'All Lessons', 'saw-lms' ),
			'add_new_item'          => __( 'Add New Lesson', 'saw-lms' ),
			'add_new'               => __( 'Add New', 'saw-lms' ),
			'new_item'              => __( 'New Lesson', 'saw-lms' ),
			'edit_item'             => __( 'Edit Lesson', 'saw-lms' ),
			'update_item'           => __( 'Update Lesson', 'saw-lms' ),
			'view_item'             => __( 'View Lesson', 'saw-lms' ),
			'view_items'            => __( 'View Lessons', 'saw-lms' ),
			'search_items'          => __( 'Search Lesson', 'saw-lms' ),
			'not_found'             => __( 'Not found', 'saw-lms' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'saw-lms' ),
		);

		$args = array(
			'label'               => __( 'Lesson', 'saw-lms' ),
			'description'         => __( 'SAW LMS Lessons', 'saw-lms' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
			'hierarchical'        => true,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false, // Menu handled by class-admin-menu.php.
			'menu_position'       => 27,
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => array( 'saw_lesson', 'saw_lessons' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'lessons',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Render fields below Gutenberg editor
	 *
	 * Uses edit_form_after_editor hook to render fields below the editor.
	 *
	 * @since 3.2.7
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_fields_below_editor( $post ) {
		// Only render for our post type.
		if ( self::POST_TYPE !== $post->post_type ) {
			return;
		}

		// Convert fields config to tabs format for consistency.
		$tabs = array();
		foreach ( $this->fields_config as $box_id => $box_config ) {
			$tabs[ $box_id ] = array(
				'label'  => $box_config['title'],
				'fields' => $box_config['fields'],
			);
		}

		// Render using helper.
		SAW_LMS_Meta_Box_Helper::render_below_editor_tabs(
			$post,
			$tabs,
			'saw_lms_lesson_meta',
			'saw_lms_lesson_nonce'
		);
	}

	/**
	 * Save meta box data
	 *
	 * REFACTORED in v3.0.0: Universal save method with sanitization.
	 *
	 * @since 2.1.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Security checks.
		if ( ! isset( $_POST['saw_lms_lesson_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_lesson_nonce'] ) ), 'saw_lms_lesson_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Loop through all fields from config.
		foreach ( $this->fields_config as $box_config ) {
			foreach ( $box_config['fields'] as $key => $field ) {

				// Skip readonly fields.
				if ( ! empty( $field['readonly'] ) ) {
					continue;
				}

				// Handle field value.
				if ( isset( $_POST[ $key ] ) ) {
					// Sanitize value based on field type.
					$value = SAW_LMS_Meta_Box_Helper::sanitize_value(
						wp_unslash( $_POST[ $key ] ),
						$field['type']
					);

					update_post_meta( $post_id, $key, $value );
				} else {
					// Checkbox unchecked = empty string.
					if ( 'checkbox' === $field['type'] ) {
						update_post_meta( $post_id, $key, '' );
					}
				}
			}
		}

		// Invalidate lesson cache.
		$section_id = get_post_meta( $post_id, '_saw_lms_section_id', true );
		if ( $section_id ) {
			wp_cache_delete( 'section_lessons_' . $section_id, 'saw_lms_sections' );
		}

		/**
		 * Fires after lesson meta is saved.
		 *
		 * @since 2.1.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		do_action( 'saw_lms_lesson_meta_saved', $post_id, $post );
	}

	/**
	 * Add admin columns
	 *
	 * @since 2.1.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_admin_columns( $columns ) {
		// Remove date temporarily.
		$date = $columns['date'];
		unset( $columns['date'] );

		// Add custom columns.
		$columns['section']  = __( 'Section', 'saw-lms' );
		$columns['type']     = __( 'Type', 'saw-lms' );
		$columns['duration'] = __( 'Duration', 'saw-lms' );

		// Re-add date.
		$columns['date'] = $date;

		return $columns;
	}

	/**
	 * Render admin column content
	 *
	 * @since 2.1.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'section':
				$section_id = get_post_meta( $post_id, '_saw_lms_section_id', true );
				if ( $section_id ) {
					$section = get_post( $section_id );
					if ( $section ) {
						$edit_link = get_edit_post_link( $section_id );
						echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $section->post_title ) . '</a>';
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;

			case 'type':
				$type = get_post_meta( $post_id, '_saw_lms_lesson_type', true );
				if ( $type && isset( self::LESSON_TYPES[ $type ] ) ) {
					echo '<span class="saw-lms-lesson-type saw-lms-type-' . esc_attr( $type ) . '">';
					echo esc_html( self::LESSON_TYPES[ $type ] );
					echo '</span>';
				} else {
					echo '—';
				}
				break;

			case 'duration':
				$duration = get_post_meta( $post_id, '_saw_lms_duration', true );
				if ( ! empty( $duration ) ) {
					/* translators: %s: duration in minutes */
					printf( esc_html__( '%s min', 'saw-lms' ), esc_html( $duration ) );
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @since 2.1.0
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['duration'] = 'duration';
		return $columns;
	}

	/**
	 * Get lesson by ID (helper method)
	 *
	 * @since 2.1.0
	 * @param int $lesson_id Lesson post ID.
	 * @return WP_Post|null Lesson post object or null.
	 */
	public static function get_lesson( $lesson_id ) {
		$lesson = get_post( $lesson_id );

		if ( ! $lesson || self::POST_TYPE !== $lesson->post_type ) {
			return null;
		}

		return $lesson;
	}

	/**
	 * Check if lesson exists
	 *
	 * @since 2.1.0
	 * @param int $lesson_id Lesson post ID.
	 * @return bool True if lesson exists, false otherwise.
	 */
	public static function lesson_exists( $lesson_id ) {
		return null !== self::get_lesson( $lesson_id );
	}
}