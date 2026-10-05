<?php
/**
 * ACF Repeater Field Type for AB Addon.
 *
 * Implements full Repeater field support for ACF Free.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AB_ACF_Field_Repeater' ) && class_exists( 'acf_field' ) ) :

	/**
	 * AB_ACF_Field_Repeater class.
	 */
	class AB_ACF_Field_Repeater extends acf_field {

		/**
		 * Initialize field type settings.
		 */
		public function initialize() {
			$this->name          = 'repeater';
			$this->label         = __( 'Repeater', 'ab-addon' );
			$this->category      = 'layout';
			$this->description   = __( 'A set of subfields which can be repeated again and again.', 'ab-addon' );
			$this->doc_url       = 'https://www.advancedcustomfields.com/resources/repeater/';
			$this->tutorial_url  = 'https://www.advancedcustomfields.com/resources/repeater/how-to-use-the-repeater-field/';
			$this->preview_image = function_exists( 'acf_get_url' ) ? acf_get_url() . '/assets/images/field-type-previews/field-preview-repeater.png' : '';
			$this->pro           = false;
			$this->have_rows     = 'rows';

			$this->defaults = array(
				'sub_fields'   => array(),
				'min'          => 0,
				'max'          => 0,
				'layout'       => 'table',
				'button_label' => __( 'Add Row', 'ab-addon' ),
				'collapsed'    => '',
			);

			// Field filters for export / import.
			$this->add_field_filter( 'acf/prepare_field_for_export', array( $this, 'prepare_field_for_export' ) );
			$this->add_field_filter( 'acf/prepare_field_for_import', array( $this, 'prepare_field_for_import' ) );
		}

		/**
		 * Filter applied to field after loading from database.
		 *
		 * @param array $field The field settings.
		 * @return array
		 */
		public function load_field( $field ) {
			$sub_fields = acf_get_fields( $field );
			if ( $sub_fields ) {
				$field['sub_fields'] = $sub_fields;
			}
			return $field;
		}

		/**
		 * Render field settings in the Field Group editor.
		 *
		 * @param array $field The field settings.
		 */
		public function render_field_settings( $field ) {
			$this->render_field_general_settings( $field );
		}

		/**
		 * Render General settings tab in ACF 6.x.
		 *
		 * @param array $field The field settings.
		 */
		public function render_field_general_settings( $field ) {
			$args = array(
				'fields'      => ! empty( $field['sub_fields'] ) ? $field['sub_fields'] : array(),
				'parent'      => ! empty( $field['ID'] ) ? $field['ID'] : 0,
				'is_subfield' => true,
			);
			?>
			<div class="acf-field acf-field-setting-sub_fields" data-setting="repeater" data-name="sub_fields">
				<div class="acf-label">
					<label><?php esc_html_e( 'Sub Fields', 'acf' ); ?></label>
					<p class="description"><?php esc_html_e( 'Define the fields that repeat in each row', 'ab-addon' ); ?></p>
				</div>
				<div class="acf-input acf-input-sub">
					<?php acf_get_view( 'acf-field-group/fields', $args ); ?>
				</div>
			</div>
			<?php

			// Layout setting.
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Layout', 'acf' ),
					'instructions' => __( 'Specify the style used to render the repeating fields', 'ab-addon' ),
					'type'         => 'radio',
					'name'         => 'layout',
					'layout'       => 'horizontal',
					'choices'      => array(
						'table' => __( 'Table', 'acf' ),
						'block' => __( 'Block', 'acf' ),
						'row'   => __( 'Row', 'acf' ),
					),
				)
			);

			// Button label setting.
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Button Label', 'acf' ),
					'instructions' => __( 'The text displayed on the "Add Row" button', 'ab-addon' ),
					'type'         => 'text',
					'name'         => 'button_label',
					'placeholder'  => __( 'Add Row', 'ab-addon' ),
				)
			);
		}

		/**
		 * Render Validation settings tab in ACF 6.x.
		 *
		 * @param array $field The field settings.
		 */
		public function render_field_validation_settings( $field ) {
			// Min rows.
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Minimum Rows', 'acf' ),
					'instructions' => __( 'Set minimum number of rows allowed', 'ab-addon' ),
					'type'         => 'number',
					'name'         => 'min',
					'placeholder'  => '0',
				)
			);

			// Max rows.
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Maximum Rows', 'acf' ),
					'instructions' => __( 'Set maximum number of rows allowed', 'ab-addon' ),
					'type'         => 'number',
					'name'         => 'max',
					'placeholder'  => '0',
				)
			);
		}

		/**
		 * Render Presentation settings tab in ACF 6.x.
		 *
		 * @param array $field The field settings.
		 */
		public function render_field_presentation_settings( $field ) {
			$choices = array( '' => __( '- Select Subfield -', 'ab-addon' ) );
			if ( ! empty( $field['sub_fields'] ) ) {
				foreach ( $field['sub_fields'] as $sub_field ) {
					$choices[ $sub_field['key'] ] = $sub_field['label'];
				}
			}

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Collapsed', 'acf' ),
					'instructions' => __( 'Select a sub field to display in the header when a row is collapsed', 'ab-addon' ),
					'type'         => 'select',
					'name'         => 'collapsed',
					'choices'      => $choices,
				)
			);
		}

		/**
		 * Loads value from database.
		 *
		 * @param mixed $value   The stored value.
		 * @param mixed $post_id The post ID.
		 * @param array $field   The field array.
		 * @return mixed
		 */
		public function load_value( $value, $post_id, $field ) {
			if ( empty( $field['sub_fields'] ) ) {
				return false;
			}

			// If value is already an array of rows.
			if ( is_array( $value ) ) {
				return $value;
			}

			$total = intval( $value );
			if ( $total <= 0 ) {
				return false;
			}

			$rows = array();
			for ( $i = 0; $i < $total; $i++ ) {
				$row = array();
				foreach ( $field['sub_fields'] as $sub_field ) {
					$sub_field_copy         = $sub_field;
					$sub_field_copy['name'] = "{$field['name']}_{$i}_{$sub_field['name']}";
					$row[ $sub_field['key'] ] = acf_get_value( $post_id, $sub_field_copy );
				}
				$rows[] = $row;
			}

			return ! empty( $rows ) ? $rows : false;
		}

		/**
		 * Formats value for template output (get_field, have_rows, the_row, get_sub_field).
		 *
		 * @param mixed   $value       The loaded value.
		 * @param mixed   $post_id     The post ID.
		 * @param array   $field       The field array.
		 * @param boolean $escape_html Whether to escape HTML.
		 * @return mixed
		 */
		public function format_value( $value, $post_id, $field, $escape_html = false ) {
			if ( empty( $value ) || ! is_array( $value ) || empty( $field['sub_fields'] ) ) {
				return false;
			}

			$formatted_rows = array();

			foreach ( $value as $i => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$formatted_row = array();

				foreach ( $field['sub_fields'] as $sub_field ) {
					$key       = $sub_field['key'];
					$name      = $sub_field['name'];
					$sub_value = isset( $row[ $key ] ) ? $row[ $key ] : ( isset( $row[ $name ] ) ? $row[name] : null );

					$sub_field_copy         = $sub_field;
					$sub_field_copy['name'] = "{$field['name']}_{$i}_{$sub_field['name']}";

					$sub_formatted = acf_format_value( $sub_value, $post_id, $sub_field_copy, $escape_html );

					// Store by name for $row['subfield_name'] and by key for get_sub_field($key).
					$formatted_row[ $name ] = $sub_formatted;
					$formatted_row[ $key ]  = $sub_formatted;
				}

				$formatted_rows[] = $formatted_row;
			}

			return ! empty( $formatted_rows ) ? $formatted_rows : false;
		}

		/**
		 * Updates value in database on post save.
		 *
		 * @param mixed $value   The submitted row data.
		 * @param mixed $post_id The post ID.
		 * @param array $field   The field array.
		 * @return int Total row count.
		 */
		public function update_value( $value, $post_id, $field ) {
			if ( empty( $field['sub_fields'] ) ) {
				return null;
			}

			// Remove template clone row if present.
			if ( is_array( $value ) && isset( $value['acfcloneindex'] ) ) {
				unset( $value['acfcloneindex'] );
			}

			$new_rows  = is_array( $value ) ? array_values( $value ) : array();
			$new_total = count( $new_rows );

			// Old total from postmeta.
			$old_total = (int) acf_get_metadata( $post_id, $field['name'] );

			// If row count reduced, clean up deleted rows from database.
			if ( $old_total > $new_total ) {
				for ( $i = $new_total; $i < $old_total; $i++ ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$meta_key = "{$field['name']}_{$i}_{$sub_field['name']}";
						acf_delete_metadata( $post_id, $meta_key );
						acf_delete_metadata( $post_id, '_' . $meta_key );
					}
				}
			}

			// If all rows removed.
			if ( 0 === $new_total ) {
				acf_delete_metadata( $post_id, $field['name'] );
				acf_delete_metadata( $post_id, '_' . $field['name'] );
				return 0;
			}

			// Save each row.
			foreach ( $new_rows as $i => $row_data ) {
				foreach ( $field['sub_fields'] as $sub_field ) {
					$key = $sub_field['key'];
					$name = $sub_field['name'];

					$sub_value = isset( $row_data[ $key ] ) ? $row_data[ $key ] : ( isset( $row_data[ $name ] ) ? $row_data[ $name ] : null );

					$sub_field_copy         = $sub_field;
					$sub_field_copy['name'] = "{$field['name']}_{$i}_{$sub_field['name']}";

					acf_update_value( $sub_value, $post_id, $sub_field_copy );
				}
			}

			// Update row count and field reference.
			acf_update_metadata( $post_id, $field['name'], $new_total );
			acf_update_metadata( $post_id, '_' . $field['name'], $field['key'] );

			return $new_total;
		}

		/**
		 * Validates submitted repeater value.
		 *
		 * @param boolean $valid The current validation state.
		 * @param mixed   $value The submitted value.
		 * @param array   $field The field array.
		 * @param string  $input The input attribute name.
		 * @return mixed
		 */
		public function validate_value( $valid, $value, $field, $input ) {
			if ( is_array( $value ) && isset( $value['acfcloneindex'] ) ) {
				unset( $value['acfcloneindex'] );
			}

			$rows  = is_array( $value ) ? array_values( $value ) : array();
			$count = count( $rows );

			if ( ! empty( $field['min'] ) && $count < (int) $field['min'] ) {
				return sprintf(
					/* translators: 1: Field label, 2: Minimum count */
					_n( '%1$s requires at least %2$d row.', '%1$s requires at least %2$d rows.', (int) $field['min'], 'ab-addon' ),
					$field['label'],
					(int) $field['min']
				);
			}

			if ( ! empty( $field['max'] ) && $count > (int) $field['max'] ) {
				return sprintf(
					/* translators: 1: Field label, 2: Maximum count */
					_n( '%1$s allows a maximum of %2$d row.', '%1$s allows a maximum of %2$d rows.', (int) $field['max'], 'ab-addon' ),
					$field['label'],
					(int) $field['max']
				);
			}

			// Validate subfields in each row.
			foreach ( $rows as $i => $row_data ) {
				foreach ( $field['sub_fields'] as $sub_field ) {
					$key = $sub_field['key'];
					$v   = isset( $row_data[ $key ] ) ? $row_data[ $key ] : null;
					$sub_input = "{$input}[{$i}][{$key}]";
					acf_validate_value( $v, $sub_field, $sub_input );
				}
			}

			return $valid;
		}

		/**
		 * Deletes value and all subfield meta for this repeater.
		 *
		 * @param mixed  $post_id  The post ID.
		 * @param string $meta_key The meta key.
		 * @param array  $field    The field array.
		 */
		public function delete_value( $post_id, $meta_key, $field ) {
			$total = (int) acf_get_metadata( $post_id, $field['name'] );
			if ( ! empty( $field['sub_fields'] ) ) {
				for ( $i = 0; $i < $total; $i++ ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$sub_key = "{$field['name']}_{$i}_{$sub_field['name']}";
						acf_delete_metadata( $post_id, $sub_key );
						acf_delete_metadata( $post_id, '_' . $sub_key );
					}
				}
			}
			acf_delete_metadata( $post_id, $field['name'] );
			acf_delete_metadata( $post_id, '_' . $field['name'] );
		}

		/**
		 * Deletes subfield posts when repeater field is deleted.
		 *
		 * @param array $field The field array.
		 */
		public function delete_field( $field ) {
			if ( ! empty( $field['sub_fields'] ) ) {
				foreach ( $field['sub_fields'] as $sub_field ) {
					acf_delete_field( $sub_field['ID'] );
				}
			}
		}

		/**
		 * Duplicates subfield posts when repeater field is duplicated.
		 *
		 * @param array $field The field array.
		 * @return array
		 */
		public function duplicate_field( $field ) {
			$sub_fields = acf_extract_var( $field, 'sub_fields' );
			$field      = acf_update_field( $field );
			if ( $sub_fields ) {
				acf_duplicate_fields( $sub_fields, $field['ID'] );
			}
			return $field;
		}

		/**
		 * Prepares field for export.
		 *
		 * @param array $field The field array.
		 * @return array
		 */
		public function prepare_field_for_export( $field ) {
			if ( ! empty( $field['sub_fields'] ) ) {
				$field['sub_fields'] = acf_prepare_fields_for_export( $field['sub_fields'] );
			}
			return $field;
		}

		/**
		 * Prepares field for import.
		 *
		 * @param array $field The field array.
		 * @return array
		 */
		public function prepare_field_for_import( $field ) {
			if ( ! empty( $field['sub_fields'] ) ) {
				$sub_fields = acf_extract_var( $field, 'sub_fields' );
				foreach ( $sub_fields as $i => $sub_field ) {
					$sub_fields[ $i ]['parent']     = $field['key'];
					$sub_fields[ $i ]['menu_order'] = $i;
				}
				return array_merge( array( $field ), $sub_fields );
			}
			return $field;
		}

		/**
		 * Renders the HTML interface for editing repeater rows on post edit screen.
		 *
		 * @param array $field The field array.
		 */
		public function render_field( $field ) {
			if ( empty( $field['sub_fields'] ) ) {
				echo '<p class="description">' . esc_html__( 'No sub fields defined for this repeater.', 'ab-addon' ) . '</p>';
				return;
			}

			$layout       = ! empty( $field['layout'] ) ? $field['layout'] : 'table';
			$button_label = ! empty( $field['button_label'] ) ? $field['button_label'] : __( 'Add Row', 'ab-addon' );
			$min          = ! empty( $field['min'] ) ? (int) $field['min'] : 0;
			$max          = ! empty( $field['max'] ) ? (int) $field['max'] : 0;
			$values       = is_array( $field['value'] ) ? array_values( $field['value'] ) : array();

			?>
			<div class="acf-repeater ab-acf-repeater -<?php echo esc_attr( $layout ); ?>" data-min="<?php echo esc_attr( $min ); ?>" data-max="<?php echo esc_attr( $max ); ?>">
				<?php
				if ( 'table' === $layout ) {
					$this->render_table_layout( $field, $values );
				} elseif ( 'block' === $layout ) {
					$this->render_block_layout( $field, $values );
				} else {
					$this->render_row_layout( $field, $values );
				}
				?>
				<div class="acf-actions">
					<a class="acf-btn acf-repeater-add-row button button-primary" href="#" data-event="add-row">
						<span class="dashicons dashicons-plus-alt2"></span> <?php echo esc_html( $button_label ); ?>
					</a>
				</div>
			</div>
			<?php
		}

		/**
		 * Render repeater in Table layout.
		 *
		 * @param array $field  Field data.
		 * @param array $values Stored row values.
		 */
		protected function render_table_layout( $field, $values ) {
			?>
			<table class="acf-table">
				<thead>
					<tr>
						<th class="acf-row-handle order" style="width: 40px; text-align: center;">#</th>
						<?php foreach ( $field['sub_fields'] as $sub_field ) :
							$sub_field = acf_prepare_field( $sub_field );
							if ( ! $sub_field ) {
								continue;
							}
							$th_attrs = array(
								'class'     => 'acf-th',
								'data-name' => $sub_field['name'],
								'data-type' => $sub_field['type'],
								'data-key'  => $sub_field['key'],
							);
							if ( ! empty( $sub_field['wrapper']['width'] ) ) {
								$th_attrs['style'] = 'width: ' . esc_attr( $sub_field['wrapper']['width'] ) . '%;';
							}
							?>
							<th <?php echo acf_esc_attrs( $th_attrs ); ?>>
								<?php acf_render_field_label( $sub_field ); ?>
								<?php acf_render_field_instructions( $sub_field ); ?>
							</th>
						<?php endforeach; ?>
						<th class="acf-row-handle remove" style="width: 50px;"></th>
					</tr>
				</thead>
				<tbody class="ui-sortable">
					<?php
					// Render existing rows.
					foreach ( $values as $i => $row_val ) {
						$this->render_table_row( $field, $row_val, $i, false );
					}
					// Render clone template row.
					$this->render_table_row( $field, array(), 'acfcloneindex', true );
					?>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Render a single table row.
		 *
		 * @param array       $field    Field data.
		 * @param array       $row_val  Row values.
		 * @param int|string  $i        Row index.
		 * @param boolean     $is_clone Whether this is the clone template.
		 */
		protected function render_table_row( $field, $row_val, $i, $is_clone = false ) {
			$row_class = 'acf-row';
			if ( $is_clone ) {
				$row_class .= ' acf-clone';
			}
			$row_id = 'row-' . $i;
			?>
			<tr class="<?php echo esc_attr( $row_class ); ?>" data-id="<?php echo esc_attr( $row_id ); ?>">
				<td class="acf-row-handle order" title="<?php esc_attr_e( 'Drag to reorder', 'ab-addon' ); ?>">
					<span><?php echo $is_clone ? '' : esc_html( (int) $i + 1 ); ?></span>
				</td>
				<?php
				foreach ( $field['sub_fields'] as $sub_field ) {
					$sub_field_render = $sub_field;

					// Value.
					if ( ! $is_clone && is_array( $row_val ) ) {
						if ( isset( $row_val[ $sub_field['key'] ] ) ) {
							$sub_field_render['value'] = $row_val[ $sub_field['key'] ];
						} elseif ( isset( $row_val[ $sub_field['name'] ] ) ) {
							$sub_field_render['value'] = $row_val[ $sub_field['name'] ];
						}
					} elseif ( isset( $sub_field['default_value'] ) ) {
						$sub_field_render['value'] = $sub_field['default_value'];
					}

					// Update prefix for row nesting.
					$sub_field_render['prefix'] = "{$field['name']}[{$row_id}]";

					if ( $is_clone ) {
						$sub_field_render['disabled'] = 1;
					}

					acf_render_field_wrap( $sub_field_render, 'td' );
				}
				?>
				<td class="acf-row-handle remove">
					<a class="acf-icon -plus small acf-js-tooltip" href="#" data-event="add-row" title="<?php esc_attr_e( 'Add row', 'ab-addon' ); ?>"></a>
					<a class="acf-icon -minus small acf-js-tooltip" href="#" data-event="remove-row" title="<?php esc_attr_e( 'Remove row', 'ab-addon' ); ?>"></a>
				</td>
			</tr>
			<?php
		}

		/**
		 * Render repeater in Block layout.
		 *
		 * @param array $field  Field data.
		 * @param array $values Stored row values.
		 */
		protected function render_block_layout( $field, $values ) {
			?>
			<div class="acf-repeater-rows -block ui-sortable">
				<?php
				foreach ( $values as $i => $row_val ) {
					$this->render_block_row( $field, $row_val, $i, false );
				}
				$this->render_block_row( $field, array(), 'acfcloneindex', true );
				?>
			</div>
			<?php
		}

		/**
		 * Render a single block row.
		 *
		 * @param array      $field    Field data.
		 * @param array      $row_val  Row values.
		 * @param int|string $i        Row index.
		 * @param boolean    $is_clone Whether this is the clone template.
		 */
		protected function render_block_row( $field, $row_val, $i, $is_clone = false ) {
			$row_class = 'acf-row -block';
			if ( $is_clone ) {
				$row_class .= ' acf-clone';
			}
			$row_id = 'row-' . $i;
			?>
			<div class="<?php echo esc_attr( $row_class ); ?>" data-id="<?php echo esc_attr( $row_id ); ?>">
				<div class="acf-row-header">
					<span class="acf-row-handle order" title="<?php esc_attr_e( 'Drag to reorder', 'ab-addon' ); ?>">
						<span class="dashicons dashicons-menu"></span>
					</span>
					<span class="acf-row-number"><?php echo $is_clone ? '' : sprintf( esc_html__( 'Row %d', 'ab-addon' ), (int) $i + 1 ); ?></span>
					<div class="acf-row-actions">
						<a class="acf-icon -plus small" href="#" data-event="add-row" title="<?php esc_attr_e( 'Add row', 'ab-addon' ); ?>"></a>
						<a class="acf-icon -minus small" href="#" data-event="remove-row" title="<?php esc_attr_e( 'Remove row', 'ab-addon' ); ?>"></a>
					</div>
				</div>
				<div class="acf-fields -top -border">
					<?php
					foreach ( $field['sub_fields'] as $sub_field ) {
						$sub_field_render = $sub_field;
						if ( ! $is_clone && is_array( $row_val ) ) {
							if ( isset( $row_val[ $sub_field['key'] ] ) ) {
								$sub_field_render['value'] = $row_val[ $sub_field['key'] ];
							} elseif ( isset( $row_val[ $sub_field['name'] ] ) ) {
								$sub_field_render['value'] = $row_val[ $sub_field['name'] ];
							}
						} elseif ( isset( $sub_field['default_value'] ) ) {
							$sub_field_render['value'] = $sub_field['default_value'];
						}
						$sub_field_render['prefix'] = "{$field['name']}[{$row_id}]";
						if ( $is_clone ) {
							$sub_field_render['disabled'] = 1;
						}
						acf_render_field_wrap( $sub_field_render, 'div' );
					}
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Render repeater in Row layout.
		 *
		 * @param array $field  Field data.
		 * @param array $values Stored row values.
		 */
		protected function render_row_layout( $field, $values ) {
			?>
			<div class="acf-repeater-rows -row ui-sortable">
				<?php
				foreach ( $values as $i => $row_val ) {
					$this->render_row_item( $field, $row_val, $i, false );
				}
				$this->render_row_item( $field, array(), 'acfcloneindex', true );
				?>
			</div>
			<?php
		}

		/**
		 * Render a single row in row layout.
		 *
		 * @param array      $field    Field data.
		 * @param array      $row_val  Row values.
		 * @param int|string $i        Row index.
		 * @param boolean    $is_clone Whether this is the clone template.
		 */
		protected function render_row_item( $field, $row_val, $i, $is_clone = false ) {
			$row_class = 'acf-row -row-layout';
			if ( $is_clone ) {
				$row_class .= ' acf-clone';
			}
			$row_id = 'row-' . $i;
			?>
			<div class="<?php echo esc_attr( $row_class ); ?>" data-id="<?php echo esc_attr( $row_id ); ?>">
				<div class="acf-row-header">
					<span class="acf-row-handle order" title="<?php esc_attr_e( 'Drag to reorder', 'ab-addon' ); ?>">
						<span class="dashicons dashicons-menu"></span>
					</span>
					<span class="acf-row-number"><?php echo $is_clone ? '' : sprintf( esc_html__( 'Row %d', 'ab-addon' ), (int) $i + 1 ); ?></span>
					<div class="acf-row-actions">
						<a class="acf-icon -plus small" href="#" data-event="add-row" title="<?php esc_attr_e( 'Add row', 'ab-addon' ); ?>"></a>
						<a class="acf-icon -minus small" href="#" data-event="remove-row" title="<?php esc_attr_e( 'Remove row', 'ab-addon' ); ?>"></a>
					</div>
				</div>
				<div class="acf-fields -left -border">
					<?php
					foreach ( $field['sub_fields'] as $sub_field ) {
						$sub_field_render = $sub_field;
						if ( ! $is_clone && is_array( $row_val ) ) {
							if ( isset( $row_val[ $sub_field['key'] ] ) ) {
								$sub_field_render['value'] = $row_val[ $sub_field['key'] ];
							} elseif ( isset( $row_val[ $sub_field['name'] ] ) ) {
								$sub_field_render['value'] = $row_val[ $sub_field['name'] ];
							}
						} elseif ( isset( $sub_field['default_value'] ) ) {
							$sub_field_render['value'] = $sub_field['default_value'];
						}
						$sub_field_render['prefix'] = "{$field['name']}[{$row_id}]";
						if ( $is_clone ) {
							$sub_field_render['disabled'] = 1;
						}
						acf_render_field_wrap( $sub_field_render, 'div' );
					}
					?>
				</div>
			</div>
			<?php
		}
	}

endif;
