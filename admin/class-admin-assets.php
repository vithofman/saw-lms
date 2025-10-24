<?php
/**
 * Admin Assets Loader - FINAL VERSION WITH VERTICAL LAYOUT
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/admin
 * @since      1.0.0
 * @version    3.2.7-FINAL
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Admin_Assets Class
 *
 * @since 1.0.0
 */
class SAW_LMS_Admin_Assets {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register all admin styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		if ( ! $this->should_load_assets() ) {
			return;
		}

		$assets_url = SAW_LMS_PLUGIN_URL . 'assets/css/admin/';

		// 1. Variables
		wp_enqueue_style(
			$this->plugin_name . '-admin-variables',
			$assets_url . 'variables.css',
			array(),
			$this->version,
			'all'
		);

		// 2. Utilities
		wp_enqueue_style(
			$this->plugin_name . '-admin-utilities',
			$assets_url . 'utilities.css',
			array( $this->plugin_name . '-admin-variables' ),
			$this->version,
			'all'
		);

		// 3. Components
		wp_enqueue_style(
			$this->plugin_name . '-admin-components',
			$assets_url . 'components.css',
			array(
				$this->plugin_name . '-admin-variables',
				$this->plugin_name . '-admin-utilities',
			),
			$this->version,
			'all'
		);

		// 4. Tabs
		wp_enqueue_style(
			$this->plugin_name . '-admin-tabs',
			$assets_url . 'tabs.css',
			array(
				$this->plugin_name . '-admin-variables',
				$this->plugin_name . '-admin-components',
			),
			$this->version,
			'all'
		);

		// 5. Sub-tabs
		wp_enqueue_style(
			$this->plugin_name . '-admin-sub-tabs',
			$assets_url . 'sub-tabs.css',
			array(
				$this->plugin_name . '-admin-variables',
				$this->plugin_name . '-admin-components',
				$this->plugin_name . '-admin-tabs',
			),
			$this->version,
			'all'
		);

		// 6. Layouts
		wp_enqueue_style(
			$this->plugin_name . '-admin-layouts',
			$assets_url . 'layouts.css',
			array(
				$this->plugin_name . '-admin-variables',
				$this->plugin_name . '-admin-utilities',
				$this->plugin_name . '-admin-components',
			),
			$this->version,
			'all'
		);

		// 8. Admin Menu
		wp_enqueue_style(
			$this->plugin_name . '-admin-menu',
			$assets_url . 'admin-menu.css',
			array( $this->plugin_name . '-admin-variables' ),
			$this->version,
			'all'
		);
	}

	/**
	 * Register all admin scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! $this->should_load_assets() ) {
			return;
		}

		$assets_url = SAW_LMS_PLUGIN_URL . 'assets/js/admin/';

		// Admin utilities
		wp_enqueue_script(
			$this->plugin_name . '-admin-utilities',
			$assets_url . 'utilities.js',
			array(),
			$this->version,
			true
		);

		// Tabs JS
		wp_enqueue_script(
			$this->plugin_name . '-admin-tabs',
			$assets_url . 'tabs.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Sub-tabs JS
		wp_enqueue_script(
			$this->plugin_name . '-admin-sub-tabs',
			$assets_url . 'sub-tabs.js',
			array( 'jquery', $this->plugin_name . '-admin-tabs' ),
			$this->version,
			true
		);

		// Gutenberg Settings Tab (only on Course screen)
		$screen = get_current_screen();
		if ( $screen && $screen->post_type === 'saw_course' && $screen->base === 'post' ) {
			wp_enqueue_script(
				$this->plugin_name . '-gutenberg-settings-tab',
				$assets_url . 'gutenberg-settings-tab.js',
				array( 'jquery' ),
				$this->version,
				true
			);
		}

		// Localize script
		wp_localize_script(
			$this->plugin_name . '-admin-utilities',
			'sawLmsAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'saw_lms_admin_nonce' ),
				'pluginUrl' => SAW_LMS_PLUGIN_URL,
				'version'   => $this->version,
				'i18n'      => array(
					'error'         => __( 'Error', 'saw-lms' ),
					'success'       => __( 'Success', 'saw-lms' ),
					'loading'       => __( 'Loading...', 'saw-lms' ),
					'confirmDelete' => __( 'Are you sure you want to delete this?', 'saw-lms' ),
					'saved'         => __( 'Changes saved successfully', 'saw-lms' ),
					'saveFailed'    => __( 'Failed to save changes', 'saw-lms' ),
					'tryAgain'      => __( 'Please try again', 'saw-lms' ),
					'networkError'  => __( 'Network error. Please check your connection.', 'saw-lms' ),
				),
			)
		);
	}

	/**
	 * Determine if assets should be loaded
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	private function should_load_assets() {
		if ( ! is_admin() ) {
			return false;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			global $pagenow;

			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			if ( ! empty( $page ) && strpos( $page, 'saw-lms' ) === 0 ) {
				return true;
			}

			$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
			if ( ! empty( $post_type ) ) {
				$saw_post_types = array( 'saw_course', 'saw_section', 'saw_lesson', 'saw_quiz' );
				if ( in_array( $post_type, $saw_post_types, true ) ) {
					return true;
				}
			}

			if ( isset( $pagenow ) && in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				$post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;
				if ( $post_id > 0 ) {
					$post = get_post( $post_id );
					if ( $post ) {
						$saw_post_types = array( 'saw_course', 'saw_section', 'saw_lesson', 'saw_quiz' );
						if ( in_array( $post->post_type, $saw_post_types, true ) ) {
							return true;
						}
					}
				}
			}

			return false;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! empty( $page ) && strpos( $page, 'saw-lms' ) === 0 ) {
			return true;
		}

		if ( isset( $screen->post_type ) ) {
			$saw_post_types = array(
				'saw_course',
				'saw_section',
				'saw_lesson',
				'saw_quiz',
			);

			if ( in_array( $screen->post_type, $saw_post_types, true ) ) {
				return true;
			}
		}

		return false;
	}

	public function add_admin_body_class( $classes ) {
		if ( ! $this->should_load_assets() ) {
			return $classes;
		}
		$classes .= ' saw-lms-admin ';
		return $classes;
	}

	public function print_custom_css_vars() {
		if ( ! $this->should_load_assets() ) {
			return;
		}
		$custom_primary_color = get_option( 'saw_lms_primary_color', '' );
		if ( ! empty( $custom_primary_color ) ) {
			echo '<style id="saw-lms-custom-vars">';
			echo ':root { --saw-primary: ' . esc_attr( $custom_primary_color ) . '; }';
			echo '</style>';
		}
	}

	public function add_menu_icon_css() {
		echo '<style>
			#adminmenu #toplevel_page_saw-lms .wp-menu-image img {
				width: 20px;
				height: 20px;
				opacity: 0.6;
			}
			#adminmenu #toplevel_page_saw-lms:hover .wp-menu-image img,
			#adminmenu #toplevel_page_saw-lms.wp-has-current-submenu .wp-menu-image img {
				opacity: 1;
			}
		</style>';
	}

	public function hide_unrelated_notices() {
		if ( ! $this->should_load_assets() ) {
			return;
		}
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.0.0
	 */
	public function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		add_action( 'admin_head', array( $this, 'print_custom_css_vars' ) );
		add_action( 'admin_head', array( $this, 'add_menu_icon_css' ) );
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
	}
}