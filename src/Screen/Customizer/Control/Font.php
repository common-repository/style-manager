<?php
/**
 * Customizer font control.
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Screen\Customizer\Control;

use Pixelgrade\StyleManager\Customize\Fonts;
use Pixelgrade\StyleManager\Utils\Fonts as FontsHelper;

/**
 * Customizer font control class.
 *
 * This handles the 'font' control type.
 *
 * @since 2.0.0
 */
class Font extends BaseControl {
	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'font';

	/**
	 * The list of recommended fonts to show at the top of the list.
	 *
	 * @var array
	 */
	public $recommended = [];

	/**
	 * The list of sub-fields.
	 *
	 * @var array
	 */
	public $fields;

	/**
	 * The default value for each sub-field.
	 *
	 * @var array
	 */
	public $default;

	/**
	 * The current field value.
	 *
	 * @var mixed
	 */
	public $current_value;

	/**
	 * The unique CSS ID value to be used throughout this control.
	 *
	 * @var string
	 */
	protected $CSSID;

	/**
	 * Style Manager Fonts service.
	 *
	 * @var Fonts
	 */
	protected $sm_fonts;

	/**
	 * Constructor.
	 *
	 * Supplied $args override class property defaults.
	 *
	 * If $args['settings'] is not defined, use the $id as the setting ID.
	 *
	 *
	 * @param \WP_Customize_Manager $manager
	 * @param string                $id
	 * @param array                 $args
	 */
	public function __construct( $manager, $id, $args = [] ) {
		global $wp_customize;

		parent::__construct( $manager, $id, $args );

		$this->sm_fonts = $args['sm_fonts_service'];
		$this->fields = $args['fields'];

		$this->CSSID = $this->get_CSS_ID();

		$this->add_hooks();

		// Since 4.7 all the customizer data is saved in a post type named changeset.
		// This is how we get it.
		if ( method_exists( $wp_customize, 'changeset_data' ) ) {
			$changeset_data = $wp_customize->changeset_data();

			if ( isset( $changeset_data[ $this->setting->id ] ) ) {
				$this->current_value = $this->standardizeSettingValue( $changeset_data[ $this->setting->id ]['value'] );

				return;
			}
		}

		$this->current_value = $this->standardizeSettingValue( $this->value() );
	}

	protected function add_hooks() {
		if ( ! empty( $this->recommended ) ) {
			add_action( 'style_manager/font_family_select_options', [ $this, 'output_recommended_options_group' ], 10, 3 );
		}

		// Standardize the setting value at a low level so it is consistent everywhere (including in JS).
		add_filter( "customize_sanitize_js_{$this->setting->id}", [ $this, 'standardizeSettingValue' ], 100, 1 );
	}

	/**
	 * Given a font value, standardize it (unencoded).
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	public function standardizeSettingValue( $value ): array {
		$value = FontsHelper::maybeDecodeValue( $value );

		return $this->sm_fonts->standardizeFontValue( $value );
	}

	public function output_recommended_options_group( $active_font_family, $current_value, $field_id ) {
		// We only want each instance to output it's recommended options group.
		if ( $this->id !== $field_id ) {
			return;
		}

		$this->display_recommended_options_group( $active_font_family, $current_value );
	}

	protected function display_recommended_options_group( $active_font_family, $current_value ) {
		// Allow others to add options here.
		do_action( 'style_manager/font_family_before_recommended_fonts_options', $active_font_family, $current_value );

		if ( ! empty( $this->recommended ) ) {

			echo '<optgroup label="' . esc_attr__( 'Recommended', 'style-manager' ) . '">';

			foreach ( $this->recommended as $font_family ) {
				$this->output_font_family_option( $font_family, $active_font_family );
			}
			echo '</optgroup>';
		}

		// Allow others to add options here.
		do_action( 'style_manager/font_family_after_recommended_fonts_options', $active_font_family, $current_value );
	}

	/**
	 * Render the control's content.
	 */
	public function render_content() {
		// The self::value() will consider the defined default value and return that if that is the case.
		$current_value = $this->current_value;
		if ( empty( $current_value ) ) {
			$current_value = $this->get_default_values();
		}

		// Make sure it is an object from here going forward.
		$current_value = (object) $current_value;

		$current_font_family = '';
		if ( isset( $current_value->font_family ) ) {
			$current_font_family = $current_value->font_family;
		}

		$current_font_details = [];
		if ( ! empty( $current_font_family ) ) {
			$current_font_details = $this->sm_fonts->getFontDetails( $current_font_family );
		}

		$select_data = 'data-active_font_family="' . esc_attr( $current_font_family ) . '"'; ?>
		<div class="font-options__wrapper">

			<input type="checkbox" class="font-options__checkbox js-font-option-toggle"
			       id="tooltip_toogle_<?php echo esc_attr( $this->CSSID ); ?>">

			<?php
			$this->display_value_holder( $current_value );
			$this->display_field_title( $current_font_family, $current_font_details ); ?>

			<ul class="font-options__options-list">
				<li class="font-options__option customize-control">
					<select id="select_font_font_family_<?php echo esc_attr( $this->CSSID ); ?>"
					        class="style-manager_font_family"<?php echo $select_data; ?> data-value_entry="font_family">

						<?php
						do_action( 'style_manager/font_family_select_before_options', $current_font_family, $current_value, $this->id );

						do_action( 'style_manager/font_family_select_options', $current_font_family, $current_value, $this->id );

						do_action( 'style_manager/font_family_select_after_options', $current_font_family, $current_value, $this->id ); ?>

					</select>
				</li>
				<?php
				$this->display_font_variant_field( $current_value, $current_font_details );

				$this->display_range_field( 'font-size', $current_value, 'font_size', esc_html__( 'Font Size', 'style-manager' ) );
				$this->display_range_field( 'line-height', $current_value, 'line_height', esc_html__( 'Line height', 'style-manager' ) );
				$this->display_range_field( 'letter-spacing', $current_value, 'letter_spacing', esc_html__( 'Letter Spacing', 'style-manager' ) );

				$this->display_select_field( 'text-align', $current_value, 'text_align', esc_html__( 'Text Align', 'style-manager' ) );
				$this->display_select_field( 'text-transform', $current_value, 'text_transform', esc_html__( 'Text Transform', 'style-manager' ) );
				$this->display_select_field( 'text-decoration', $current_value, 'text_decoration', esc_html__( 'Text Decoration', 'style-manager' ) );
				?>
			</ul>
		</div>

		<?php if ( ! empty( $this->description ) ) : ?>
			<span class="description customize-control-description"><?php echo $this->description; ?></span>
		<?php endif;

		?>
	<?php }

	/**
	 * This input will hold the value of this font field
	 *
	 * @todo Right now we are only using this field for getting the setting ID (via it's link). No value holder :)
	 *
	 * @param $current_value
	 */
	protected function display_value_holder( $current_value ) { ?>
		<input class="style-manager_font_values" id="<?php echo esc_attr( $this->CSSID ); ?>"
	       type="hidden"
			<?php $this->link(); ?>
	       value="<?php // The value will be set by the Customizer core logic from the _wpCustomizeSettings.settings data. ?>"
		/>
<?php }

	protected function display_field_title( $font_family, $current_font_details ) {
		// Determine if we have a "pretty" display for this font family
		$font_family_display = $font_family;
		if ( ! empty( $current_font_details['family_display'] ) ) {
			$font_family_display = $current_font_details['family_display'];
		}
		?>
		<label class="font-options__head  select" for="tooltip_toogle_<?php echo esc_attr( $this->CSSID ); ?>">
			<?php if ( ! empty( $this->label ) ) : ?>
				<span class="font-options__option-title"><?php echo esc_html( $this->label ); ?></span>
			<?php endif; ?>
			<span class="font-options__font-title"
			      id="font_name_<?php echo esc_attr( $this->CSSID ); ?>"><?php echo $font_family_display; ?></span>
		</label>
	<?php }

	protected function display_font_variant_field( $current_value, $current_font_details ) {
		// If the `font-weight` field entry is falsy, this means we don't want to use the field.
		if ( empty( $this->fields['font-weight'] ) ) {
			return;
		}

		// Display is for the initial state. Depending on the selected fonts, the JS logic will show or hide it.
		$display = 'none';
		if ( ! empty( $current_font_details['variants'] ) && $current_font_details['variants'] !== [ 'regular' ] ) {
			$display = 'inline-block';
		}

		$selected = false;
		if ( isset( $current_value->font_variant ) ) {
			$selected = $current_value->font_variant;
		}
		?>
		<li class="style-manager_weights_wrapper customize-control font-options__option"
		    style="display: <?php echo $display; ?>;">
			<label><?php esc_html_e( 'Font Variant', 'style-manager' ); ?></label>
			<select class="style-manager_font_weight"
			        data-value_entry="font_variant" <?php echo ( 'none' === $display ) ? 'data-disabled="true"' : '' ?>>
				<?php
				if ( ! empty( $current_font_details['variants'] ) ) {
					if ( is_string( $current_font_details['variants'] ) ) {
						$current_font_details['variants'] = [ $current_font_details['variants'] ];
					}

					// Output an option with an empty value. Selecting this will NOT force a certain variant in the output.
					echo '<option value="">Auto</option>';

					foreach ( $current_font_details['variants'] as $variant ) {
						$attrs = '';
						// We must make sure that they are converted to strings to avoid dubious conversions like 300italic == 300.
						if ( (string) $variant === (string) $selected ) {
							$attrs = ' selected="selected"';
						}

						echo '<option value="' . esc_attr( $variant ) . '" ' . $attrs . '> ' . $variant . '</option>';
					}
				} ?>
			</select>
		</li>
		<?php
	}

	protected function display_range_field( $field, $currentFontValue, $valueEntry, $label ) {
		// If the field entry is falsy, this means we don't want to use the field.
		if ( empty( $this->fields[ $field ] ) ) {
			return;
		}

		$value = empty( $currentFontValue->$valueEntry ) ? 0 : $currentFontValue->$valueEntry;
		// Standardize the value.
		$value = FontsHelper::standardizeNumericalValue( $value, $field, [ 'fields' => $this->fields ] );

		// We will remember the unit of the value, in case some other system pushed down a value (with an unit)
		// that is different from the field config unit. This way we can retain the unit of the value until
		// the user interacts with the control.
		?>
		<li class="style-manager_<?php echo esc_attr( $valueEntry ); ?>_wrapper customize-control customize-control-range font-options__option">
			<label><?php echo $label ?></label>
			<input type="range"
		        data-value_entry="<?php echo esc_attr( $valueEntry ) ?>"
				<?php $this->range_field_attributes( $this->fields[ $field ] ) ?>
			    value="<?php echo esc_attr( $value['value'] ); ?>"
			    data-value_unit="<?php echo esc_attr( $value['unit'] ); ?>"
			/>
		</li>
<?php
	}

	/**
	 * Output the custom attributes for a range sub-field.
	 *
	 * @param array $attributes
	 */
	protected function range_field_attributes( $attributes ) {
		$attributes = FontsHelper::standardizeRangeFieldAttributes( $attributes );

		foreach ( $attributes as $attr => $value ) {
			echo $attr . '="' . esc_attr( $value ) . '" ';
		}
	}

	protected function display_select_field( $field, $currentFontValue, $valueEntry, $label ) {
		// If the field entry is falsy, this means we don't want to use the field.
		if ( empty( $this->fields[ $field ] ) ) {
			return;
		}

		$valid_values = FontsHelper::getValidSubfieldValues( $valueEntry, false );
		$value        = isset( $currentFontValue->$valueEntry ) && ( empty( $valid_values ) || in_array( $currentFontValue->$valueEntry, $valid_values ) ) ? $currentFontValue->$valueEntry : reset( $valid_values ); ?>
		<li class="style-manager_<?php echo $valueEntry ?>_wrapper customize-control font-options__option">
			<label><?php echo $label ?></label>
			<select data-value_entry="<?php echo esc_attr( $valueEntry ) ?>">
				<?php
				foreach ( FontsHelper::getValidSubfieldValues( $valueEntry, true ) as $option_value => $option_label ) { ?>
					<option <?php $this->display_option_value( $option_value, $value ); ?>><?php echo $option_label; ?></option>
				<?php } ?>
			</select>
		</li>
		<?php
	}

	protected function display_option_value( $value, $current_value ) {

		$return = 'value="' . esc_attr( $value ) . '"';

		if ( $value === $current_value ) {
			$return .= ' selected="selected"';
		}

		echo $return;
	}

	/**
	 * This method displays an <option> tag from the given params
	 *
	 * @param string|array $font_family
	 * @param string|false $active_font_family Optional. The active font family to add the selected attribute to the appropriate opt.
	 *                                         False to not mark any opt as selected.
	 */
	protected function output_font_family_option( $font_family, $active_font_family = false ) {
		echo self::get_font_family_option_markup( $font_family, $active_font_family );
	}

	/**
	 * This method returns an <option> tag from the given params
	 *
	 * @param string|array $font_family
	 * @param string|false $active_font_family Optional. The active font family to add the selected attribute to the appropriate opt.
	 *                                         False to not mark any opt as selected.
	 *
	 * @return string
	 */
	protected function get_font_family_option_markup( $font_family, $active_font_family = false ): string {
		$html = '';

		// Bail if we don't have a font family value.
		if ( empty( $font_family ) ) {
			return apply_filters( 'style_manager/filter_font_option_markup_no_family', $html, $active_font_family );
		}

		$font_type    = $this->sm_fonts->determineFontType( $font_family );
		$font_details = $this->sm_fonts->getFontDetails( $font_family, $font_type );

		// Now determine if we have a "pretty" display for this font family.
		$font_family_display = $font_family;
		if ( ! empty( $font_details['family_display'] ) ) {
			$font_family_display = $font_details['family_display'];
		}

		// Determine if the font is selected.
		$selected = ( false !== $active_font_family && $active_font_family === $font_family ) ? ' selected="selected" ' : '';

		// Determine the option class.
		$option_class = ( false !== strpos( $font_type, '_font' ) ) ? $font_type : $font_type . '_font';

		$html .= '<option class="' . esc_attr( $option_class ) . '" value="' . esc_attr( $font_family ) . '" ' . $selected . '>' . $font_family_display . '</option>';

		return apply_filters( 'style_manager/filter_font_option_markup', $html, $font_family, $active_font_family, $font_type );
	}

	/** ==== Helpers ==== */

	protected function get_default_values(): array {

		$defaults = [];

		if ( isset( $this->default ) && is_array( $this->default ) ) {

			// Handle special logic for when the $value array is not an associative array.
			if ( ! $this->isAssocArray( $this->default ) ) {

				// The first entry is the font-family.
				if ( isset( $this->default[0] ) ) {
					$defaults['font_family'] = $this->default[0];
				}

				// The second entry is the variant.
				if ( isset( $this->default[1] ) ) {
					$defaults['font_variant'] = $this->default[1];
				}
			} else {
				$defaults = $this->default;
			}
		}

		return $this->sm_fonts->standardizeFontValue( $defaults );
	}

	protected function get_CSS_ID(): string {
		return str_replace( [ '[', ']' ], '_', $this->id );
	}

	protected function isAssocArray( $array ): bool {
		return ( $array !== array_values( $array ) );
	}
}
