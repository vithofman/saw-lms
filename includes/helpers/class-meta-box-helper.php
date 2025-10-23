<?php
/**
 * Meta Box Helper
 *
 * Centralized helper for rendering and sanitizing meta box fields.
 * Supports config-based meta boxes for all custom post types.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/includes/helpers
 * @since      3.0.0
 * @version    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SAW_LMS_Meta_Box_Helper Class
 *
 * Static helper class for rendering meta box fields and sanitizing values.
 * Uses admin design system (CSS utilities and components).
 *
 * @since 3.0.0
 */
class SAW_LMS_Meta_Box_Helper {

	/**
	 * Render a field based on its type
	 *
	 * Main entry point for rendering any field type.
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param array  $field Field configuration.
	 * @param mixed  $value Current field value.
	 * @return void
	 */
	public static function render_field( $key, $field, $value ) {
		// Get field type.
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		// Apply default value if empty.
		if ( empty( $value ) && isset( $field['default'] ) ) {
			$value = $field['default'];
		}

		// Render field wrapper.
		echo '<div class="saw-lms-field mb-3">';

		// Render label if exists.
		if ( ! empty( $field['label'] ) ) {
			echo '<label for="' . esc_attr( $key ) . '" class="saw-lms-field-label font-bold mb-1">';
			echo esc_html( $field['label'] );

			// Required indicator.
			if ( ! empty( $field['required'] ) ) {
				echo ' <span class="text-red">*</span>';
			}

			echo '</label>';
		}

		// Render field based on type.
		switch ( $type ) {
			case 'text':
			case 'url':
				self::render_text( $key, $value, $field );
				break;

			case 'number':
				self::render_number( $key, $value, $field );
				break;

			case 'checkbox':
				self::render_checkbox( $key, $value, $field );
				break;

			case 'select':
				self::render_select( $key, $value, $field );
				break;

			case 'textarea':
				self::render_textarea( $key, $value, $field );
				break;

			case 'post_select':
				self::render_post_select( $key, $value, $field );
				break;

			case 'date':
				self::render_date( $key, $value, $field );
				break;

			case 'readonly':
				self::render_readonly( $key, $value, $field );
				break;

			default:
				self::render_text( $key, $value, $field );
				break;
		}

		// Render description if exists.
		if ( ! empty( $field['description'] ) ) {
			echo '<p class="saw-lms-field-description text-sm text-muted mt-1">';
			echo esc_html( $field['description'] );
			echo '</p>';
		}

		echo '</div>';
	}

	/**
	 * Render text input field
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param mixed  $value Current value.
	 * @param array  $field Field config.
	 * @return void
	 */
	private static function render_text( $key, $value, $field ) {
		$type        = isset( $field['type'] ) && 'url' === $field['type'] ? 'url' : 'text';
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$readonly    = ! empty( $field['readonly'] );

		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $key ); ?>"
			name="<?php echo esc_attr( $key ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="form-input"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			<?php echo $readonly ? 'readonly' : ''; ?>
		/>
		<?php
	}

	/**
	 * Render number input field
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param mixed  $value Current value.
	 * @param array  $field Field config.
	 * @return void
	 */
	private static function render_number( $key, $value, $field ) {
		$min         = isset( $field['min'] ) ? $field['min'] : '';
		$max         = isset( $field['max'] ) ? $field['max'] : '';
		$step        = isset( $field['step'] ) ? $field['step'] : '1';
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$readonly    = ! empty( $field['readonly'] );

		?>
		<input
			type="number"
			id="<?php echo esc_attr( $key ); ?>"
			name="<?php echo esc_attr( $key ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="form-input"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			<?php echo '' !== $min ? 'min="' . esc_attr( $min ) . '"' : ''; ?>
			<?php echo '' !== $max ? 'max="' . esc_attr( $max ) . '"' : ''; ?>
			step="<?php echo esc_attr( $step ); ?>"
			<?php echo $readonly ? 'readonly' : ''; ?>
		/>
		<?php
	}

	/**
	 * Render checkbox field
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param mixed  $value Current value.
	 * @param array  $field Field config.
	 * @return void
	 */
	private static function render_checkbox( $key, $value, $field ) {
		$checked = ! empty( $value ) || '1' === $value;
		$label   = isset( $field['checkbox_label'] ) ? $field['checkbox_label'] : '';

		?>
		<label class="form-checkbox">
			<input
				type="checkbox"
				id="<?php echo esc_attr( $key ); ?>"
				name="<?php echo esc_attr( $key ); ?>"
				value="1"
				<?php checked( $checked, true ); ?>
			/>
			<?php if ( $label ) : ?>
				<span><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Render select dropdown field
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param mixed  $value Current value.
	 * @param array  $field Field config.
	 * @return void
	 */
	private static function render_select( $key, $value, $field ) {
		$options = isset( $field['options'] ) ? $field['options'] : array();

		?>
		<select
			id="<?php echo esc_attr( $key ); ?>"
			name="<?php echo esc_attr( $key ); ?>"
			class="form-select"
		>
			<?php if ( empty( $field['required'] ) ) : ?>
				<option value="">— <?php esc_html_e( 'Select', 'saw-lms' ); ?> —</option>
			<?php endif; ?>

			<?php foreach ( $options as $option_value => $option_label ) : ?>
				<option
					value="<?php echo esc_attr( $option_value ); ?>"
					<?php selected( $value, $option_value ); ?>
				>
					<?php echo esc_html( $option_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render textarea field
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param mixed  $value Current value.
	 * @param array  $field Field config.
	 * @return void
	 */
	private static function render_textarea( $key, $value, $field ) {
		$rows        = isset( $field['rows'] ) ? $field['rows'] : 5;
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

		?>
		<textarea
			id="<?php echo esc_attr( $key ); ?>"
			name="<?php echo esc_attr( $key ); ?>"
			class="form-textarea"
			rows="<?php echo esc_attr( $rows ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
		><?php echo esc_textarea( $value ); ?></textarea>
		<?php
	}

	/**
	 * Render post select field
	 *
	 * Allows selecting one or multiple posts from a specified post type.
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param mixed  $value Current value (post ID or array of IDs).
	 * @param array  $field Field config.
	 * @return void
	 */
	private static function render_post_select( $key, $value, $field ) {
		$post_type = isset( $field['post_type'] ) ? $field['post_type'] : 'post';
		$multiple  = ! empty( $field['multiple'] );

		// Convert value to array for multiple select.
		if ( $multiple && ! is_array( $value ) ) {
			$value = ! empty( $value ) ? array( $value ) : array();
		}

		// Get posts for dropdown.
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);

		// Handle user post type (get users instead).
		if ( 'user' === $post_type ) {
			$users = get_users(
				array(
					'orderby' => 'display_name',
					'order'   => 'ASC',
				)
			);

			?>
			<select
				id="<?php echo esc_attr( $key ); ?>"
				name="<?php echo esc_attr( $key ); ?><?php echo $multiple ? '[]' : ''; ?>"
				class="form-select"
				<?php echo $multiple ? 'multiple' : ''; ?>
			>
				<?php if ( ! $multiple ) : ?>
					<option value="">— <?php esc_html_e( 'Select User', 'saw-lms' ); ?> —</option>
				<?php endif; ?>

				<?php foreach ( $users as $user ) : ?>
					<?php
					$selected = $multiple
						? in_array( $user->ID, $value, true )
						: $value == $user->ID;
					?>
					<option
						value="<?php echo esc_attr( $user->ID ); ?>"
						<?php selected( $selected, true ); ?>
					>
						<?php echo esc_html( $user->display_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
			return;
		}

		// Regular post type select.
		?>
		<select
			id="<?php echo esc_attr( $key ); ?>"
			name="<?php echo esc_attr( $key ); ?><?php echo $multiple ? '[]' : ''; ?>"
			class="form-select"
			<?php echo $multiple ? 'multiple' : ''; ?>
		>
			<?php if ( ! $multiple ) : ?>
				<option value="">— <?php esc_html_e( 'Select', 'saw-lms' ); ?> —</option>
			<?php endif; ?>

			<?php foreach ( $posts as $post ) : ?>
				<?php
				$selected = $multiple
					? in_array( $post->ID, $value, true )
					: $value == $post->ID;
				?>
				<option
					value="<?php echo esc_attr( $post->ID ); ?>"
					<?php selected( $selected, true ); ?>
				>
					<?php echo esc_html( $post->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render date input field
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param mixed  $value Current value.
	 * @param array  $field Field config.
	 * @return void
	 */
	private static function render_date( $key, $value, $field ) {
		?>
		<input
			type="date"
			id="<?php echo esc_attr( $key ); ?>"
			name="<?php echo esc_attr( $key ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="form-input"
		/>
		<?php
	}

	/**
	 * Render readonly field
	 *
	 * Displays value but prevents editing (e.g., statistics).
	 *
	 * @since 3.0.0
	 * @param string $key   Meta key.
	 * @param mixed  $value Current value.
	 * @param array  $field Field config.
	 * @return void
	 */
	private static function render_readonly( $key, $value, $field ) {
		?>
		<input
			type="text"
			id="<?php echo esc_attr( $key ); ?>"
			name="<?php echo esc_attr( $key ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="form-input"
			readonly
			disabled
		/>
		<?php
	}

	/**
	 * Sanitize value based on field type
	 *
	 * @since 3.0.0
	 * @param mixed  $value Raw input value.
	 * @param string $type  Field type.
	 * @return mixed Sanitized value.
	 */
	public static function sanitize_value( $value, $type ) {
		switch ( $type ) {
			case 'text':
				return sanitize_text_field( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'number':
				return is_numeric( $value ) ? floatval( $value ) : 0;

			case 'checkbox':
				return ! empty( $value ) ? '1' : '';

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'select':
				return sanitize_text_field( $value );

			case 'post_select':
				// Handle multiple select (array of IDs).
				if ( is_array( $value ) ) {
					return array_map( 'absint', $value );
				}
				return absint( $value );

			case 'date':
				// Validate date format (YYYY-MM-DD).
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
					return sanitize_text_field( $value );
				}
				return '';

			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get field value from post meta with fallback to default
	 *
	 * Helper method to retrieve field value with proper default handling.
	 *
	 * @since 3.0.0
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param array  $field   Field configuration.
	 * @return mixed Field value or default.
	 */
	public static function get_field_value( $post_id, $key, $field ) {
		$value = get_post_meta( $post_id, $key, true );

		// Apply default if empty.
		if ( empty( $value ) && isset( $field['default'] ) ) {
			return $field['default'];
		}

		return $value;
	}
}