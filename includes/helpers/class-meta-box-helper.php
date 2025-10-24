<?php
/**
 * Meta Box Helper Class
 *
 * Provides reusable methods for rendering and handling meta box fields.
 * UPDATED in v3.0.0: Added render_tabbed_meta_box() for tab support.
 * UPDATED in v3.1.1: Added support for 'heading' and 'date' field types.
 *
 * @package     SAW_LMS
 * @subpackage  Helpers
 * @since       3.0.0
 * @version     3.1.1
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

			// Render fields for this tab.
			if ( ! empty( $tab_config['fields'] ) ) {
				foreach ( $tab_config['fields'] as $key => $field ) {
					$value = self::get_field_value( $post_id, $key, $field );
					self::render_field( $key, $field, $value );
				}
			}

			echo '</div>';
			$first = false;
		}
		echo '</div>'; // .saw-tabs-content

		echo '</div>'; // .saw-tabs-wrapper
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

		// Label.
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
				printf(
					'<label class="form-checkbox"><input type="checkbox" id="%s" name="%s" value="1"%s%s> <span>%s</span></label>',
					esc_attr( $key ),
					esc_attr( $key ),
					checked( $value, '1', false ),
					$readonly ? ' disabled' : '',
					esc_html( ! empty( $field['checkbox_label'] ) ? $field['checkbox_label'] : $label )
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
				$posts     = get_posts(
					array(
						'post_type'      => $post_type,
						'posts_per_page' => -1,
						'orderby'        => 'title',
						'order'          => 'ASC',
						'post_status'    => 'publish',
					)
				);

				printf(
					'<select id="%s" name="%s" class="form-select"%s%s>',
					esc_attr( $key ),
					esc_attr( $key ),
					$readonly ? ' disabled' : '',
					$required ? ' required' : ''
				);

				echo '<option value="">— Select —</option>';

				foreach ( $posts as $post ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $post->ID ),
						selected( $value, $post->ID, false ),
						esc_html( $post->post_title )
					);
				}

				echo '</select>';
				break;

			case 'readonly':
				printf(
					'<div class="saw-readonly-value">%s</div>',
					esc_html( $value ? $value : '—' )
				);
				break;

			default:
				// Unknown field type - show warning in debug mode.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					printf(
						'<div class="notice notice-warning inline"><p>Unknown field type: <strong>%s</strong></p></div>',
						esc_html( $type )
					);
				}
				break;
		}

		echo '</div>'; // .saw-field-input

		// Description (only if not already shown in checkbox).
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
	 * UPDATED in v3.1.1: Added 'date' sanitization.
	 *
	 * @since 3.0.0
	 * @param mixed  $value      Value to sanitize.
	 * @param string $field_type Field type.
	 * @return mixed Sanitized value.
	 */
	public static function sanitize_value( $value, $field_type ) {
		switch ( $field_type ) {
			case 'text':
			case 'select':
			case 'radio':
				return sanitize_text_field( $value );

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'email':
				return sanitize_email( $value );

			case 'number':
				return is_numeric( $value ) ? $value : 0;

			case 'date':
				// Validate date format (YYYY-MM-DD).
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
					return sanitize_text_field( $value );
				}
				return '';

			case 'checkbox':
				return ( '1' === $value || 1 === $value ) ? '1' : '';

			case 'post_select':
				return absint( $value );

			default:
				return sanitize_text_field( $value );
		}
	}
}