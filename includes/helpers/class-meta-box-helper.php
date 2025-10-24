<?php
/**
 * Meta Box Helper Class
 *
 * Provides reusable methods for rendering and handling meta box fields.
 * UPDATED in v3.0.0: Added render_tabbed_meta_box() for tab support.
 * UPDATED in v3.1.1: Added support for 'heading' and 'date' field types.
 * FIXED in v3.1.5: COMPLETED render_field() - all field types now render properly!
 * UPDATED in v3.2.4: Added render_sub_tabbed_content() for vertical sub-tabs support.
 *
 * @package     SAW_LMS
 * @subpackage  Helpers
 * @since       3.0.0
 * @version     3.2.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Meta_Box_Helper Class
 *
 * Static helper methods for meta box rendering and data handling.
 *
 * @since 3.0.0
 */
class SAW_LMS_Meta_Box_Helper {

	/**
	 * Render a tabbed meta box
	 *
	 * Creates a meta box with multiple tabs. Each tab contains fields from a separate config file.
	 *
	 * UPDATED in v3.2.4: Added support for sub-tabs in Settings tab.
	 *
	 * @since 3.1.0
	 * @param int   $post_id Post ID.
	 * @param array $tabs    Array of tabs configuration.
	 *                       Format: array(
	 *                           'tab_id' => array(
	 *                               'label'  => 'Tab Label',
	 *                               'fields' => array( ... field configs ... ),
	 *                           ),
	 *                       )
	 * @return void
	 */
	public static function render_tabbed_meta_box( $post_id, $tabs ) {
		if ( empty( $tabs ) ) {
			return;
		}

		echo '<div class="saw-tabs-wrapper">';

		// Render tab navigation.
		echo '<div class="saw-tabs-nav">';
		$first = true;
		foreach ( $tabs as $tab_id => $tab_config ) {
			$active_class = $first ? ' saw-tab-active' : '';
			printf(
				'<button type="button" class="saw-tab-button%s" data-tab="%s">%s</button>',
				esc_attr( $active_class ),
				esc_attr( $tab_id ),
				esc_html( $tab_config['label'] )
			);
			$first = false;
		}
		echo '</div>';

		// Render tab content.
		echo '<div class="saw-tabs-content">';
		$first = true;
		foreach ( $tabs as $tab_id => $tab_config ) {
			$active_class = $first ? ' saw-tab-content-active' : '';
			printf(
				'<div class="saw-tab-content%s" data-tab-content="%s">',
				esc_attr( $active_class ),
				esc_attr( $tab_id )
			);

			// Check if this tab has sub-tabs (Settings tab special case).
			if ( 'settings' === $tab_id && self::has_sub_tabs( $tab_config['fields'] ) ) {
				// Render with sub-tabs.
				self::render_sub_tabbed_content( $post_id, $tab_config['fields'] );
			} else {
				// Render fields normally.
				if ( ! empty( $tab_config['fields'] ) ) {
					foreach ( $tab_config['fields'] as $key => $field ) {
						$value = self::get_field_value( $post_id, $key, $field );
						self::render_field( $key, $field, $value );
					}
				}
			}

			echo '</div>'; // .saw-tab-content
			$first = false;
		}
		echo '</div>'; // .saw-tabs-content

		echo '</div>'; // .saw-tabs-wrapper
	}

	/**
	 * Check if fields array contains sub-tabs structure
	 *
	 * Sub-tabs structure: array with keys that are arrays containing 'label', 'icon', 'fields'.
	 *
	 * @since 3.2.4
	 * @param array $fields Fields configuration.
	 * @return bool True if has sub-tabs.
	 */
	private static function has_sub_tabs( $fields ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return false;
		}

		// Check first item - if it has 'label' and 'fields' keys, it's a sub-tab.
		$first_item = reset( $fields );
		return is_array( $first_item ) && isset( $first_item['label'] ) && isset( $first_item['fields'] );
	}

	/**
	 * Render sub-tabbed content
	 *
	 * Renders vertical sub-tabs menu with panels for Settings tab.
	 *
	 * @since 3.2.4
	 * @param int   $post_id  Post ID.
	 * @param array $sub_tabs Array of sub-tabs configuration.
	 *                        Format: array(
	 *                            'sub_tab_id' => array(
	 *                                'label'       => 'Sub-tab Label',
	 *                                'icon'        => '⚙️',
	 *                                'description' => 'Optional description',
	 *                                'fields'      => array( ... field configs ... ),
	 *                            ),
	 *                        )
	 * @return void
	 */
	public static function render_sub_tabbed_content( $post_id, $sub_tabs ) {
		if ( empty( $sub_tabs ) ) {
			return;
		}

		echo '<div class="saw-sub-tabs-container">';

		// LEFT SIDE: Vertical menu.
		echo '<div class="saw-sub-tabs-menu">';
		$first = true;
		foreach ( $sub_tabs as $sub_tab_id => $sub_tab_config ) {
			$active_class = $first ? ' saw-sub-tab-active' : '';
			$icon         = isset( $sub_tab_config['icon'] ) ? $sub_tab_config['icon'] : '';
			$label        = $sub_tab_config['label'];

			printf(
				'<button type="button" class="saw-sub-tab-button%s" data-panel="%s">',
				esc_attr( $active_class ),
				esc_attr( $sub_tab_id )
			);

			// Icon.
			if ( $icon ) {
				printf(
					'<span class="saw-sub-tab-icon">%s</span>',
					esc_html( $icon )
				);
			}

			// Label.
			printf(
				'<span class="saw-sub-tab-label">%s</span>',
				esc_html( $label )
			);

			echo '</button>';

			$first = false;
		}
		echo '</div>'; // .saw-sub-tabs-menu

		// RIGHT SIDE: Panels with fields.
		echo '<div class="saw-sub-tabs-panels">';
		$first = true;
		foreach ( $sub_tabs as $sub_tab_id => $sub_tab_config ) {
			$active_class = $first ? ' saw-sub-tab-panel-active' : '';

			printf(
				'<div class="saw-sub-tab-panel%s" data-panel-id="%s">',
				esc_attr( $active_class ),
				esc_attr( $sub_tab_id )
			);

			// Panel heading (optional).
			if ( ! empty( $sub_tab_config['description'] ) ) {
				echo '<div class="saw-sub-panel-heading">';
				printf(
					'<h3>%s %s</h3>',
					! empty( $sub_tab_config['icon'] ) ? esc_html( $sub_tab_config['icon'] ) : '',
					esc_html( $sub_tab_config['label'] )
				);
				printf(
					'<p class="description">%s</p>',
					esc_html( $sub_tab_config['description'] )
				);
				echo '</div>';
			}

			// Render fields for this sub-tab.
			if ( ! empty( $sub_tab_config['fields'] ) ) {
				foreach ( $sub_tab_config['fields'] as $key => $field ) {
					$value = self::get_field_value( $post_id, $key, $field );
					self::render_field( $key, $field, $value );
				}
			}

			echo '</div>'; // .saw-sub-tab-panel
			$first = false;
		}
		echo '</div>'; // .saw-sub-tabs-panels

		echo '</div>'; // .saw-sub-tabs-container
	}

	/**
	 * Get field value
	 *
	 * Retrieves post meta value with default fallback.
	 *
	 * @since 3.0.0
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param array  $field   Field configuration.
	 * @return mixed Field value.
	 */
	public static function get_field_value( $post_id, $key, $field ) {
		$value = get_post_meta( $post_id, $key, true );

		// Return default if value is empty.
		if ( '' === $value && isset( $field['default'] ) ) {
			return $field['default'];
		}

		return $value;
	}

	/**
	 * Render a single field
	 *
	 * Outputs HTML for a meta box field based on its type.
	 *
	 * UPDATED in v3.1.1: Added 'heading' and 'date' field types.
	 * FIXED in v3.1.5: COMPLETED all field types - no more missing cases!
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param array  $field Field configuration.
	 * @param mixed  $value Current value.
	 * @return void
	 */
	public static function render_field( $key, $field, $value ) {
		$type        = isset( $field['type'] ) ? $field['type'] : 'text';
		$label       = isset( $field['label'] ) ? $field['label'] : '';
		$description = isset( $field['description'] ) ? $field['description'] : '';
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$readonly    = ! empty( $field['readonly'] );
		$required    = ! empty( $field['required'] );
		$rows        = isset( $field['rows'] ) ? absint( $field['rows'] ) : 4;

		// SPECIAL CASE: Heading (section delimiter).
		if ( 'heading' === $type ) {
			echo '<div class="saw-field-heading">';
			echo '<h3>' . esc_html( $label ) . '</h3>';
			if ( $description ) {
				echo '<p class="description">' . esc_html( $description ) . '</p>';
			}
			echo '</div>';
			return; // Early return - no standard field wrapper.
		}

		// Standard field wrapper.
		echo '<div class="saw-field-group">';

		// Label (except for checkbox).
		if ( $label && 'checkbox' !== $type ) {
			printf(
				'<label for="%s" class="saw-field-label">%s%s</label>',
				esc_attr( $key ),
				esc_html( $label ),
				$required ? ' <span class="required">*</span>' : ''
			);
		}

		// Field input.
		echo '<div class="saw-field-input">';

		switch ( $type ) {
			case 'text':
			case 'number':
			case 'url':
			case 'email':
				$extra_attrs = '';
				if ( 'number' === $type && isset( $field['min'] ) ) {
					$extra_attrs .= ' min="' . esc_attr( $field['min'] ) . '"';
				}
				if ( 'number' === $type && isset( $field['max'] ) ) {
					$extra_attrs .= ' max="' . esc_attr( $field['max'] ) . '"';
				}
				if ( 'number' === $type && isset( $field['step'] ) ) {
					$extra_attrs .= ' step="' . esc_attr( $field['step'] ) . '"';
				}

				printf(
					'<input type="%s" id="%s" name="%s" value="%s" placeholder="%s" class="form-input"%s%s%s>',
					esc_attr( $type ),
					esc_attr( $key ),
					esc_attr( $key ),
					esc_attr( $value ),
					esc_attr( $placeholder ),
					$readonly ? ' readonly' : '',
					$required ? ' required' : '',
					$extra_attrs
				);
				break;

			case 'date':
				// Date input field.
				printf(
					'<input type="date" id="%s" name="%s" value="%s" class="form-input"%s%s>',
					esc_attr( $key ),
					esc_attr( $key ),
					esc_attr( $value ),
					$readonly ? ' readonly' : '',
					$required ? ' required' : ''
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%s" name="%s" rows="%d" class="form-textarea" placeholder="%s"%s%s>%s</textarea>',
					esc_attr( $key ),
					esc_attr( $key ),
					$rows,
					esc_attr( $placeholder ),
					$readonly ? ' readonly' : '',
					$required ? ' required' : '',
					esc_textarea( $value )
				);
				break;

			case 'select':
				printf(
					'<select id="%s" name="%s" class="form-select"%s%s>',
					esc_attr( $key ),
					esc_attr( $key ),
					$readonly ? ' disabled' : '',
					$required ? ' required' : ''
				);

				if ( ! empty( $field['options'] ) ) {
					foreach ( $field['options'] as $option_value => $option_label ) {
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $option_value ),
							selected( $value, $option_value, false ),
							esc_html( $option_label )
						);
					}
				}

				echo '</select>';
				break;

			case 'checkbox':
				$checkbox_label = ! empty( $field['checkbox_label'] ) ? $field['checkbox_label'] : $label;
				printf(
					'<label class="form-checkbox"><input type="checkbox" id="%s" name="%s" value="1"%s%s> <span>%s</span></label>',
					esc_attr( $key ),
					esc_attr( $key ),
					checked( $value, '1', false ),
					$readonly ? ' disabled' : '',
					esc_html( $checkbox_label )
				);
				$description = ''; // Don't show description twice.
				break;

			case 'radio':
				if ( ! empty( $field['options'] ) ) {
					foreach ( $field['options'] as $option_value => $option_label ) {
						printf(
							'<label class="form-radio"><input type="radio" name="%s" value="%s"%s%s> <span>%s</span></label><br>',
							esc_attr( $key ),
							esc_attr( $option_value ),
							checked( $value, $option_value, false ),
							$readonly ? ' disabled' : '',
							esc_html( $option_label )
						);
					}
				}
				break;

			case 'post_select':
				$post_type = isset( $field['post_type'] ) ? $field['post_type'] : 'post';
				$multiple  = ! empty( $field['multiple'] );
				$selected  = $multiple && is_array( $value ) ? $value : array( $value );

				printf(
					'<select id="%s" name="%s%s" class="form-select"%s%s%s>',
					esc_attr( $key ),
					esc_attr( $key ),
					$multiple ? '[]' : '',
					$multiple ? ' multiple' : '',
					$readonly ? ' disabled' : '',
					$required ? ' required' : ''
				);

				// Empty option for single select.
				if ( ! $multiple ) {
					echo '<option value="">-- ' . esc_html__( 'Select', 'saw-lms' ) . ' --</option>';
				}

				// Get posts of specified type.
				$posts = get_posts(
					array(
						'post_type'      => $post_type,
						'posts_per_page' => -1,
						'orderby'        => 'title',
						'order'          => 'ASC',
						'post_status'    => 'publish',
					)
				);

				foreach ( $posts as $post ) {
					printf(
						'<option value="%d"%s>%s</option>',
						esc_attr( $post->ID ),
						in_array( $post->ID, $selected, true ) ? ' selected' : '',
						esc_html( $post->post_title )
					);
				}

				echo '</select>';
				break;

			case 'readonly':
				// Readonly text field (displays value but can't be edited).
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="form-input" readonly>',
					esc_attr( $key ),
					esc_attr( $key ),
					esc_attr( $value )
				);
				break;

			default:
				// Fallback for unknown types - render as text.
				printf(
					'<input type="text" id="%s" name="%s" value="%s" placeholder="%s" class="form-input"%s%s>',
					esc_attr( $key ),
					esc_attr( $key ),
					esc_attr( $value ),
					esc_attr( $placeholder ),
					$readonly ? ' readonly' : '',
					$required ? ' required' : ''
				);
				break;
		}

		echo '</div>'; // .saw-field-input

		// Description (if not already shown for checkbox).
		if ( $description ) {
			printf(
				'<p class="saw-field-description">%s</p>',
				esc_html( $description )
			);
		}

		echo '</div>'; // .saw-field-group
	}

	/**
	 * Sanitize field value
	 *
	 * Sanitizes input based on field type.
	 *
	 * @since 3.0.0
	 * @param mixed  $value Input value.
	 * @param string $type  Field type.
	 * @return mixed Sanitized value.
	 */
	public static function sanitize_value( $value, $type ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'number':
				return is_numeric( $value ) ? floatval( $value ) : 0;

			case 'checkbox':
				return $value === '1' ? '1' : '';

			case 'textarea':
				return wp_kses_post( $value );

			case 'post_select':
				// Handle multiple select (array of IDs).
				if ( is_array( $value ) ) {
					return array_map( 'absint', $value );
				}
				// Single select (single ID).
				return absint( $value );

			case 'date':
				// Validate date format YYYY-MM-DD.
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
					return sanitize_text_field( $value );
				}
				return '';

			case 'text':
			case 'select':
			case 'radio':
			case 'readonly':
			default:
				return sanitize_text_field( $value );
		}
	}
}