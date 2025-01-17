<?php
/**
 * This is the class that handles the logic for Color Palettes.
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Customize;

use Pixelgrade\StyleManager\Utils\ArrayHelpers;
use Pixelgrade\StyleManager\Vendor\Cedaro\WP\Plugin\AbstractHookProvider;
use Pixelgrade\StyleManager\Vendor\Psr\Log\LoggerInterface;
use function Pixelgrade\StyleManager\is_sm_supported;

/**
 * Provides the color palettes logic.
 *
 * @since 2.0.0
 */
class ColorPalettes extends AbstractHookProvider {

	const SM_COLOR_PALETTE_OPTION_KEY = 'sm_color_palette_in_use';
	const SM_COLOR_PALETTE_VARIATION_OPTION_KEY = 'sm_color_palette_variation';
	const SM_IS_CUSTOM_COLOR_PALETTE_OPTION_KEY = 'sm_is_custom_color_palette';

	/**
	 * Design assets.
	 *
	 * @var DesignAssets
	 */
	protected $design_assets;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param DesignAssets    $design_assets Design assets.
	 * @param LoggerInterface $logger        Logger.
	 */
	public function __construct(
		DesignAssets $design_assets,
		LoggerInterface $logger
	) {

		$this->design_assets = $design_assets;
		$this->logger        = $logger;
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public function register_hooks() {
		/*
		 * Handle the Customizer Style Manager section config.
		 */
		$this->add_filter( 'style_manager/filter_fields', 'add_style_manager_new_section_master_colors_config', 13, 1 );
		$this->add_filter( 'style_manager/sm_panel_config', 'reorganize_customizer_controls', 10, 2 );

		// This needs to come after the external theme config has been applied
		$this->add_filter( 'style_manager/filter_fields', 'maybe_enhance_dark_mode_control', 120, 1 );

		$this->add_filter( 'style_manager/final_config', 'alter_master_controls_connected_fields', 100, 1 );
		$this->add_filter( 'style_manager/final_config', 'add_color_usage_section', 110, 1 );
		$this->add_filter( 'style_manager/final_config', 'add_fine_tune_palette_section', 120, 1 );

		$this->add_filter( 'novablocks_block_editor_settings', 'add_color_palettes_to_novablocks_settings' );

		/**
		 * Add color palettes usage to site data.
		 */
		$this->add_filter( 'style_manager/get_site_data', 'add_palettes_to_site_data', 10, 1 );

		// Add data to be passed to JS.
		$this->add_filter( 'style_manager/localized_js_settings', 'add_to_localized_data', 10, 1 );

		$this->add_filter( 'language_attributes', 'add_dark_mode_data_attribute', 10, 2 );
	}

	/**
	 * Determine if Color Palettes are supported.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		// For now we will only use the fact that Style Manager is supported.
		return apply_filters( 'style_manager/color_palettes_are_supported', is_sm_supported() );
	}

	/**
	 * Get the color palettes configuration.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $skip_cache Optional. Whether to use the cached config or fetch a new one.
	 *
	 * @return array
	 */
	public function get_palettes( $skip_cache = false ): array {
		$config = $this->design_assets->get_entry( 'color_palettes_v2', $skip_cache );
		if ( is_null( $config ) ) {
			$config = $this->get_default_config();
		}

		return apply_filters( 'style_manager/get_color_palettes', $config );
	}

	/**
	 *
	 * @since 2.0.0
	 *
	 * @param array $config
	 *
	 * @return array
	 */
	protected function alter_master_controls_connected_fields( $config ): array {

		$switch_foreground_connected_fields = [];
		$switch_accent_connected_fields     = [];

		if ( ! isset( $config['panels']['theme_options_panel']['sections']['colors_section']['options'] ) ) {
			return $config;
		}

		foreach ( $config['panels']['theme_options_panel']['sections']['colors_section']['options'] as $id => $option_config ) {

			if ( $option_config['type'] === 'sm_toggle' ) {
				if ( $option_config['default'] === 'on' ) {
					$switch_accent_connected_fields[] = $id;
				} else {
					$switch_foreground_connected_fields[] = $id;
				}
			}
		}

		if ( ! isset( $config['panels']['style_manager_panel']['sections']['sm_color_palettes_section']['options'] ) ) {
			return $config;
		}

		$options = $config['panels']['style_manager_panel']['sections']['sm_color_palettes_section']['options'];

		$switch_dark_count = count( $switch_foreground_connected_fields );

		// Avoid division by zero.
		if ( empty( $switch_dark_count ) ) {
			$switch_dark_count = 1;
		}

		$switch_accent_count = count( $switch_accent_connected_fields );

		if ( isset( $options['sm_coloration_level'] ) ) {
			$average                                   = round( $switch_accent_count * 100 / $switch_dark_count );
			$default                                   = $average > 87.5 ? '100' : ( $average > 62.5 ? '75' : ( $average > 25 ? '50' : '0' ) );
			$options['sm_coloration_level']['default'] = $default;
		}

		$config['panels']['style_manager_panel']['sections']['sm_color_palettes_section']['options'] = $options;

		return $config;
	}

	/**
	 *
	 * @since 2.0.0
	 *
	 * @param array $config
	 *
	 * @return array
	 */
	protected function add_color_usage_section( $config ): array {

		$color_usage_fields = [
			'sm_description_color_usage_intro',
			'sm_separator_1_0',
			'sm_text_color_switch_master',
			'sm_accent_color_switch_master',
			'sm_coloration_level',
			'sm_colorize_elements_button',
			'sm_separator_1_1',
			'sm_dark_mode',
			'sm_dark_mode_advanced',
		];

		$color_usage_section = [
			'title'      => esc_html__( 'Color Usage', 'style-manager' ),
			'section_id' => 'sm_color_usage_section',
			'priority'   => 10,
			'options'    => [],
		];

		if ( ! isset( $config['panels']['style_manager_panel']['sections']['sm_color_palettes_section']['options'] ) ) {
			return $config;
		}

		$sm_colors_options = $config['panels']['style_manager_panel']['sections']['sm_color_palettes_section']['options'];

		foreach ( $color_usage_fields as $field_id ) {

			if ( ! isset( $sm_colors_options[ $field_id ] ) ) {
				continue;
			}

			if ( empty( $color_usage_section['options'] ) ) {
				$color_usage_section['options'] = [ $field_id => $sm_colors_options[ $field_id ] ];
			} else {
				$color_usage_section['options'] = array_merge( $color_usage_section['options'], [ $field_id => $sm_colors_options[ $field_id ] ] );
			}
		}

		$config['panels']['theme_options_panel']['sections']['sm_color_usage_section'] = $color_usage_section;

		return $config;
	}

	/**
  * @param mixed[] $config
  */
 protected function add_fine_tune_palette_section( $config ): array {

		$fine_tune_palette_fields = [
			'sm_color_fine_tune_intro',
			'sm_color_fine_tune_presets',
			'sm_separator_2_0',
			'sm_color_grades_number',
			'sm_potential_color_contrast',
			'sm_color_grade_balancer',
			'sm_site_color_variation',
			'sm_separator_2_1',
			'sm_elements_color_contrast',
			'sm_separator_2_2',
			'sm_color_promotion_brand',
			'sm_color_promotion_white',
			'sm_color_promotion_black',
			'sm_separator_2_3',
		];

		$fine_tune_palette_section = [
			'title'      => esc_html__( 'Fine-tune color palette', 'style-manager' ),
			'section_id' => 'sm_fine_tune_color_palette_section',
			'priority'   => 20,
			'options'    => [],
		];

		if ( ! isset( $config['panels']['style_manager_panel']['sections']['sm_color_palettes_section']['options'] ) ) {
			return $config;
		}

		$sm_colors_options = $config['panels']['style_manager_panel']['sections']['sm_color_palettes_section']['options'];

		foreach ( $fine_tune_palette_fields as $field_id ) {

			if ( ! isset( $sm_colors_options[ $field_id ] ) ) {
				continue;
			}

			if ( empty( $fine_tune_palette_section['options'] ) ) {
				$fine_tune_palette_section['options'] = [ $field_id => $sm_colors_options[ $field_id ] ];
			} else {
				$fine_tune_palette_section['options'] = array_merge( $fine_tune_palette_section['options'], [ $field_id => $sm_colors_options[ $field_id ] ] );
			}
		}

		$config['panels']['theme_options_panel']['sections']['sm_fine_tune_color_palette_section'] = $fine_tune_palette_section;

		return $config;
	}

	/**
  * @param mixed[] $settings
  */
 protected function add_color_palettes_to_novablocks_settings( $settings ): array {
		$palette_output_value = \Pixelgrade\StyleManager\get_option( 'sm_advanced_palette_output' );
		$palettes             = [];

		if ( ! empty( $palette_output_value ) ) {
			$palettes = json_decode( $palette_output_value );
		}

		$settings['palettes'] = $palettes;

		return $settings;
	}

	/**
	 *
	 * @since 2.0.0
	 *
	 * @param array $config
	 *
	 * @return array
	 */
	protected function add_style_manager_new_section_master_colors_config( $config ): array {
		// If there is no Color Palettes support, bail early.
		if ( ! $this->is_supported() ) {
			return $config;
		}

		if ( ! isset( $config['sections']['style_manager_section'] ) ) {
			$config['sections']['style_manager_section'] = [];
		}

		$sm_advanced_palette_output_default = file_get_contents( __DIR__  . '/sm_advanced_palette_output.json' );

		// The section might be already defined, thus we merge, not replace the entire section config.
		$config['sections']['style_manager_section']['options'] =
			[
				'sm_advanced_palette_source'                => [
					'type'         => 'text',
					'live'         => true,
					'default'      => json_encode(
						[
							[
								'uid'     => 'color_group_1',
								'sources' => [
									[
										'uid'        => 'color_11',
										'showPicker' => true,
										'label'      => 'Color',
										'value'      => '#ddaa61',
									],
								],
							],
							[
								'uid'     => 'color_group_2',
								'sources' => [
									[
										'uid'        => 'color_21',
										'showPicker' => true,
										'label'      => 'Color',
										'value'      => '#39497C',
									],
								],
							],
							[
								'uid'     => 'color_group_3',
								'sources' => [
									[
										'uid'        => 'color_31',
										'showPicker' => true,
										'label'      => 'Color',
										'value'      => '#B12C4A',
									],
								],
							],
						]
					),
					// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
					'setting_type' => 'option',
					// We will force this setting id preventing prefixing and other regular processing.
					'setting_id'   => 'sm_advanced_palette_source',
					'label'        => esc_html__( 'Palette Source', 'style-manager' ),
				],
				// This is just a setting to hold the currently selected color palette (its hashid).
				self::SM_COLOR_PALETTE_OPTION_KEY           => [
					'type'         => 'hidden_control',
					'live'         => true,
					// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
					'setting_type' => 'option',
					// We will force this setting id preventing prefixing and other regular processing.
					'setting_id'   => self::SM_COLOR_PALETTE_OPTION_KEY,
				],
				// This is just a setting to hold the currently selected color palette (its hashid).
				self::SM_IS_CUSTOM_COLOR_PALETTE_OPTION_KEY => [
					'type'         => 'hidden_control',
					'live'         => true,
					// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
					'setting_type' => 'option',
					// We will force this setting id preventing prefixing and other regular processing.
					'setting_id'   => self::SM_IS_CUSTOM_COLOR_PALETTE_OPTION_KEY,
				],
				'sm_advanced_palette_output'                => [
					'type'         => 'text',
					'live'         => true,
					'default'      => $sm_advanced_palette_output_default,
					// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
					'setting_type' => 'option',
					// We will force this setting id preventing prefixing and other regular processing.
					'setting_id'   => 'sm_advanced_palette_output',
					'label'        => esc_html__( 'Palette Output', 'style-manager' ),
					'css'          => [
						[
							'selector'        => ':root',
							'property'        => 'dummy-property',
							'callback_filter' => 'sm_advanced_palette_output_cb',
						],
					],
				],
				'NOT_sm_description_color_system_intro'     => [
					'type' => 'html',
					'html' => 'Set up the <a href="https://pixelgrade.com/docs/design-and-style/color-system/" target="_blank">Color System of your website.',
				],

				'sm_description_color_usage_intro' => [
					'type' => 'html',
					'html' => 'Manage how to apply <a href="https://pixelgrade.com/docs/rosa2/design-and-style/color-system/#6-manage-how-to-apply-color-to-your-website" target="_blank">the default colors</a> to your website\'s elements.',
				],
				'sm_separator_1_0'                 => [ 'type' => 'html', 'html' => '' ],
				'sm_text_color_switch_master'      => [
					'type'             => 'sm_toggle',
					// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
					'setting_type'     => 'option',
					// We will force this setting id preventing prefixing and other regular processing.
					'setting_id'       => 'sm_text_color_switch_master',
					'label'            => esc_html__( 'Text Master', 'style-manager' ),
					'live'             => true,
					'default'          => false,
					'connected_fields' => [],
					'css'              => [],
				],
				'sm_accent_color_switch_master'    => [
					'type'             => 'sm_toggle',
					// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
					'setting_type'     => 'option',
					// We will force this setting id preventing prefixing and other regular processing.
					'setting_id'       => 'sm_accent_color_switch_master',
					'label'            => esc_html__( 'Accent Master', 'style-manager' ),
					'live'             => true,
					'default'          => true,
					'connected_fields' => [],
					'css'              => [],
				],
				'sm_separator_1_1'                 => [ 'type' => 'html', 'html' => '' ],
				'sm_coloration_level'              => [
					'type'         => 'sm_radio',
					'desc'         => wp_kses( __( 'Adjust <strong>how much color</strong> you want to add to your site. For more control over elements, you can edit them individually.', 'style-manager' ), [ 'strong' => [] ] ),
					'setting_type' => 'option',
					'setting_id'   => 'sm_coloration_level',
					'label'        => esc_html__( 'Coloration Level', 'style-manager' ),
					'default'      => 0,
					'live'         => true,
					'choices'      => [
						'0'   => esc_html__( 'Low', 'style-manager' ),
						'50'  => esc_html__( 'Medium', 'style-manager' ),
						'75'  => esc_html__( 'High', 'style-manager' ),
						'100' => esc_html__( 'Striking', 'style-manager' ),
					],
				],

				'sm_color_fine_tune_intro'    => [
					'type'         => 'html',
					'setting_type' => 'option',
					'setting_id'   => 'sm_color_fine_tune_intro',
					'html'         => '<div class="customize-control-title">' . esc_html__( 'Palette Configuration', 'style-manager' ) . '</div>' .
					                  '<span class="description customize-control-description">' . esc_html__( 'Change the structure of the generated palette while maintaining the underlying principles and guidelines.', 'style-manager' ) . '</span>',
				],
				'sm_color_fine_tune_presets'  => [
					'type'         => 'preset',
					'live'         => true,
					'setting_type' => 'option',
					'setting_id'   => 'sm_color_fine_tune_presets',
					'label'        => esc_html__( 'Presets', 'style-manager' ),
					'default'      => 'normal',
					'choices_type' => 'radio',
					'choices'      => [
						'normal' => [
							'label'   => esc_html__( 'Normal', 'style-manager' ),
							'options' => [
								'sm_color_grades_number'      => 12,
								'sm_potential_color_contrast' => 1,
								'sm_color_grade_balancer'     => 0,
								'sm_color_promotion_brand'    => true,
								'sm_color_promotion_white'    => true,
								'sm_color_promotion_black'    => true,
								'sm_elements_color_contrast'  => 'normal',
							],
						],
						'simple' => [
							'label'   => esc_html__( 'Simple', 'style-manager' ),
							'options' => [
								'sm_color_grades_number'      => 4,
								'sm_potential_color_contrast' => 1,
								'sm_color_grade_balancer'     => 0.5,
								'sm_color_promotion_brand'    => true,
								'sm_color_promotion_white'    => true,
								'sm_color_promotion_black'    => true,
								'sm_elements_color_contrast'  => 'normal',
							],
						],
						'light'  => [
							'label'   => esc_html__( 'Light', 'style-manager' ),
							'options' => [
								'sm_color_grades_number'      => 12,
								'sm_potential_color_contrast' => 1,
								'sm_color_grade_balancer'     => 1,
								'sm_color_promotion_brand'    => true,
								'sm_color_promotion_white'    => true,
								'sm_color_promotion_black'    => false,
								'sm_elements_color_contrast'  => 'normal',
							],
						],
						'dark'   => [
							'label'   => esc_html__( 'Dark', 'style-manager' ),
							'options' => [
								'sm_color_grades_number'      => 12,
								'sm_potential_color_contrast' => 1,
								'sm_color_grade_balancer'     => 1,
								'sm_color_promotion_brand'    => true,
								'sm_color_promotion_white'    => false,
								'sm_color_promotion_black'    => true,
								'sm_elements_color_contrast'  => 'normal',
							],
						],
						'custom' => [
							'label'   => esc_html__( 'Custom', 'style-manager' ),
							'options' => [],
						],
					],
				],
				'sm_separator_2_0'            => [ 'type' => 'html', 'html' => '' ],
				'sm_color_grades_number'      => [
					'type'         => 'range',
					'desc'         => __( 'Adjust the number of color grades available within a palette (Default: 12)' ),
					'live'         => true,
					'setting_type' => 'option',
					'setting_id'   => 'sm_color_grades_number',
					'label'        => esc_html__( 'Number of color grades', 'style-manager' ),
					'default'      => 12,
					'input_attrs'  => [
						'min'  => 1,
						'max'  => 12,
						'step' => 1,
					],
				],
				'sm_potential_color_contrast' => [
					'type'         => 'range',
					'desc'         => __( 'Increase or decrease the contrast between colors within the generated palette.' ),
					'live'         => true,
					'setting_type' => 'option',
					'setting_id'   => 'sm_potential_color_contrast',
					'label'        => esc_html__( 'Potential color contrast', 'style-manager' ),
					'default'      => 1,
					'input_attrs'  => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.01,
					],
				],
				'sm_color_grade_balancer'     => [
					'type'         => 'range',
					'desc'         => __( 'Move the slider towards the left side to promote lighter color grades.' ),
					'live'         => true,
					'setting_type' => 'option',
					'setting_id'   => 'sm_color_grade_balancer',
					'label'        => esc_html__( 'Color grade balancer', 'style-manager' ),
					'default'      => 0,
					'input_attrs'  => [
						'min'  => - 1,
						'max'  => 1,
						'step' => 0.01,
					],
				],
				'sm_site_color_variation'     => [
					'type'         => 'range',
					'desc'         => wp_kses( __( 'Shift the <strong>start position</strong> of the color palette. Use 1 for white, 2-3 for subtle shades, 4-7 for colorful, above 8 for darker shades.', 'style-manager' ), [ 'strong' => [] ] ),
					'live'         => true,
					'setting_type' => 'option',
					'setting_id'   => 'sm_site_color_variation',
					'label'        => esc_html__( 'Palette basis offset', 'style-manager' ),
					'default'      => 1,
					'input_attrs'  => [
						'min'  => 1,
						'max'  => 12,
						'step' => 1,
					],
					'css'          => [
						[
							'selector'        => ':root',
							'property'        => 'dummy-property',
							'callback_filter' => 'sm_site_color_variation_cb',
						],
					],
				],
				'sm_separator_2_1'            => [ 'type' => 'html', 'html' => '' ],
				'sm_elements_color_contrast'  => [
					'type'         => 'radio',
					'desc'         => __( 'Increase or decrease the contrast between the background colors and the colors of elements on top.' ),
					'live'         => true,
					'setting_type' => 'option',
					'setting_id'   => 'sm_elements_color_contrast',
					'label'        => esc_html__( 'Elements color contrast', 'style-manager' ),
					'default'      => 'normal',
					'choices'      => [
						'normal'  => esc_html__( 'Normal', 'style-manager' ),
						'average' => esc_html__( 'Average', 'style-manager' ),
						'maximum' => esc_html__( 'Maximum', 'style-manager' ),
					],
				],
				'sm_separator_2_2'            => [ 'type' => 'html', 'html' => '' ],
				'sm_color_promotion_brand'    => [
					'type'         => 'sm_toggle',
					'setting_type' => 'option',
					'setting_id'   => 'sm_color_promotion_brand',
					'label'        => esc_html__( 'Brand colors', 'style-manager' ),
					'live'         => true,
					'default'      => true,
				],
				'sm_color_promotion_white'    => [
					'type'         => 'sm_toggle',
					'setting_type' => 'option',
					'setting_id'   => 'sm_color_promotion_white',
					'label'        => esc_html__( 'Pure white', 'style-manager' ),
					'live'         => true,
					'default'      => true,
				],
				'sm_color_promotion_black'    => [
					'type'         => 'sm_toggle',
					'setting_type' => 'option',
					'setting_id'   => 'sm_color_promotion_black',
					'label'        => esc_html__( 'Pure black', 'style-manager' ),
					'live'         => true,
					'default'      => true,
				],
				'sm_separator_2_3'            => [ 'type' => 'html', 'html' => '' ],
			] + $config['sections']['style_manager_section']['options'];

		return $config;
	}

	/**
	 * Reorganize the Customizer controls.
	 *
	 * @since 2.0.0
	 *
	 * @param array $sm_panel_config
	 * @param array $sm_section_config
	 *
	 * @return array
	 */
	protected function reorganize_customizer_controls( $sm_panel_config, $sm_section_config ): array {
		// We need to split the fields in the Style Manager section into two: color palettes and fonts.
		$color_palettes_fields = [
			'sm_advanced_palette_source',
			self::SM_COLOR_PALETTE_OPTION_KEY,
			self::SM_IS_CUSTOM_COLOR_PALETTE_OPTION_KEY,
			'sm_advanced_palette_output',

			'sm_description_color_usage_intro',
			'sm_separator_1_0',
			'sm_text_color_switch_master',
			'sm_accent_color_switch_master',
			'sm_coloration_level',
			'sm_colorize_elements_button',
			'sm_separator_1_1',
			'sm_dark_mode',
			'sm_dark_mode_advanced',

			// fine tune palette fields
			'sm_color_fine_tune_intro',
			'sm_separator_2_0',
			'sm_color_fine_tune_presets',
			'sm_color_grades_number',
			'sm_potential_color_contrast',
			'sm_color_grade_balancer',
			'sm_site_color_variation',
			'sm_separator_2_1',
			'sm_elements_color_contrast',
			'sm_separator_2_2',
			'sm_color_promotion_brand',
			'sm_color_promotion_white',
			'sm_color_promotion_black',
			'sm_separator_2_3',
		];

		$color_palettes_section_config = [
			'title'       => esc_html__( 'Color System', 'style-manager' ),
			'section_id'  => 'sm_color_palettes_section',
			'description' => wp_kses( __( 'Set up the <a href="https://pixelgrade.com/docs/design-and-style/color-system/" target="_blank">Color System</a> for your website using the tools below.', 'style-manager' ), wp_kses_allowed_html( 'post' ) ),
			'priority'    => 10,
			'options'     => [],
		];

		foreach ( $color_palettes_fields as $field_id ) {
			if ( ! isset( $sm_section_config['options'][ $field_id ] ) ) {
				continue;
			}

			if ( empty( $color_palettes_section_config['options'] ) ) {
				$color_palettes_section_config['options'] = [ $field_id => $sm_section_config['options'][ $field_id ] ];
			} else {
				$color_palettes_section_config['options'] = array_merge( $color_palettes_section_config['options'], [ $field_id => $sm_section_config['options'][ $field_id ] ] );
			}
		}

		$sm_panel_config['sections']['sm_color_palettes_section'] = $color_palettes_section_config;

		return $sm_panel_config;
	}

	/**
	 *
	 * @since 2.0.0
	 *
	 * @param array $config
	 *
	 * @return array
	 */
	protected function maybe_enhance_dark_mode_control( $config ): array {

		if ( ! current_theme_supports( 'style_manager_advanced_dark_mode' )
		     || ! isset( $config['sections']['style_manager_section'] ) ) {

			return $config;
		}

		unset( $config['sections']['style_manager_section']['options']['sm_dark_mode'] );

		$config['sections']['style_manager_section'] = ArrayHelpers::array_merge_recursive_distinct( $config['sections']['style_manager_section'], [
			'options' => [
				'sm_dark_mode_advanced' => [
					'type'         => 'sm_radio',
					'setting_id'   => 'sm_dark_mode_advanced',
					'setting_type' => 'option',
					'label'        => esc_html__( 'Appearance', 'style-manager' ),
					'live'         => true,
					'default'      => 'off',
					'desc'         => wp_kses( __( 'Choose between light or dark color schemes or set it to <strong>"Auto"</strong> to activate dark mode automatically, according to your visitors\' system-wide setting.', 'style-manager' ), [ 'strong' => [] ] ),
					'choices'      => [
						'off'  => esc_html__( 'Light', 'style-manager' ),
						'on'   => esc_html__( 'Dark', 'style-manager' ),
						'auto' => esc_html__( 'Auto', 'style-manager' ),
					],
				],
			],
		] );

		return $config;
	}

	/**
	 * Get the current font palette ID or false if none is selected.
	 *
	 * @since 2.0.0
	 *
	 * @return string|false
	 */
	protected function get_current_palette() {
		return get_option( self::SM_COLOR_PALETTE_OPTION_KEY, false );
	}

	/**
	 * Get the current font palette variation ID or false if none is selected.
	 *
	 * @since 2.0.0
	 *
	 * @return string|false
	 */
	protected function get_current_palette_variation() {
		return get_option( self::SM_COLOR_PALETTE_VARIATION_OPTION_KEY, false );
	}

	/**
	 * Determine if a custom font palette is in use.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	protected function is_using_custom_palette(): bool {
		return (bool) get_option( self::SM_IS_CUSTOM_COLOR_PALETTE_OPTION_KEY, false );
	}

	/**
	 * Get the default (hard-coded) color palettes configuration.
	 *
	 * This is only a fallback config in case we can't communicate with the cloud, the first time.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_default_config(): array {
		return apply_filters( 'style_manager/default_color_palettes', [] );
	}

	/**
	 * Add color palettes usage data to the site data sent to the cloud.
	 *
	 * @since 2.0.0
	 *
	 * @param array $site_data
	 *
	 * @return array
	 */
	protected function add_palettes_to_site_data( $site_data ): array {

		if ( empty( $site_data['color_palettes_v2'] ) ) {
			$site_data['color_palettes_v2'] = [];
		}

		// If others have added data before us, we will merge with it.
		$site_data['color_palettes_v2'] = array_merge( $site_data['color_palettes_v2'], [
			'current'   => $this->get_current_palette(),
			'variation' => $this->get_current_palette_variation(),
			'custom'    => $this->is_using_custom_palette(),
		] );

		return $site_data;
	}

	/**
	 * Add data to be available to JS.
	 *
	 * @since 2.0.0
	 *
	 * @param array $localized
	 *
	 * @return array
	 */
	protected function add_to_localized_data( $localized ): array {
		if ( empty( $localized['colorPalettes'] ) ) {
			$localized['colorPalettes'] = [];
		}

		$localized['colorPalettes']['palettes'] = $this->get_palettes();

		if ( empty( $localized['l10n'] ) ) {
			$localized['l10n'] = [];
		}
		$localized['l10n']['colorPalettes'] = [
			'colorizeElementsPanelLabel'         => esc_html__( 'Colorize elements one by one', 'style-manager' ),
			'builderColorUsagePanelLabel'        => esc_html__( 'Customize colors usage', 'style-manager' ),
			'builderFineTuneColorsLabel'         => esc_html__( 'Fine-tune generated palette', 'style-manager' ),
			'builderBrandColorsLabel'            => esc_html__( 'Brand Colors', 'style-manager' ),
			'builderBrandColorsDesc'             => wp_kses( __( 'Adjust the main brand colors you want to use on your site. Provide <strong>only the main colors</strong> since the Color System will <strong>generate an entire palette</strong> from these. For advanced controls of generated color palette, see the section below.', 'style-manager' ), [ 'strong' => [] ] ),
			'builderColorPresetsTitle'           => esc_html__( 'Explore colors', 'style-manager' ),
			'builderColorPresetsDesc'            => esc_html__( 'Curated color presets to help you lay the foundations of the color system and make it easy to get started.', 'style-manager' ),
			'builderImageExtractTitle'           => esc_html__( 'Extract from Image', 'style-manager' ),
			'dropzoneInterpolatedColorLabel'     => esc_html__( 'Interpolated Color', 'style-manager' ),
			'dropzoneDesc'                       => esc_html__( 'Extract colors from an image and generate a color palette for your design system.', 'style-manager' ),
			'dropzoneTitle'                      => esc_html__( 'Drag and drop your image', 'style-manager' ),
			'dropzoneSubtitle'                   => sprintf(
				wp_kses(
				/* translators 1: open span, 2: close span */
					__( 'or %1$s select a file %2$s from your computer', 'style-manager' ),
					wp_kses_allowed_html()
				),
				'<span class="dropzone-info-anchor">',
				'</span>'
			),
			'previewTabTypographyLabel'          => esc_html__( 'Typography', 'style-manager' ),
			'previewTabColorSystemLabel'         => esc_html__( 'Colors', 'style-manager' ),
			'previewTabLiveSiteLabel'            => esc_html__( 'Live site', 'style-manager' ),
			'palettePreviewTitle'                => esc_html__( 'The color system', 'style-manager' ),
			'palettePreviewDesc'                 => esc_html__( 'The color system presented below is designed based on your brand colors. Hover over a color grade to see a preview of how you will be able to use colors with your content blocks.', 'style-manager' ),
			'palettePreviewListDesc'             => esc_html__( 'Each column from the color palette below represent a state where a component could be. The first row is the main surface or background color, while the other two rows are for the content.', 'style-manager' ),
			'palettePreviewSwatchSurfaceText'    => esc_html__( 'Surface', 'style-manager' ),
			'palettePreviewSwatchAccentText'     => esc_html__( 'Accent', 'style-manager' ),
			'palettePreviewSwatchForegroundText' => esc_html__( 'Text', 'style-manager' ),
			'sourceColorsDefaultLabel'           => esc_html__( 'Color', 'style-manager' ),
			'sourceColorsInterpolatedLabel'      => esc_html__( 'Interpolated Color', 'style-manager' ),

			'typographyPreviewPrimaryShortLabel'   => esc_html__( 'Primary', 'style-manager' ),
			'typographyPreviewSecondaryShortLabel' => esc_html__( 'Secondary', 'style-manager' ),
			'typographyPreviewBodyShortLabel'      => esc_html__( 'Body', 'style-manager' ),
			'typographyPreviewAccentShortLabel'    => esc_html__( 'Accent', 'style-manager' ),

			'typographyPreviewHeadCategoryLabel' => esc_html__( 'Category', 'style-manager' ),
			'typographyPreviewHeadPreviewLabel'  => esc_html__( 'Preview', 'style-manager' ),
			'typographyPreviewHeadSizeLabel'     => esc_html__( 'Size', 'style-manager' ),

			'builderFineTuneTypographyLabel' => esc_html__( 'Fine-tune the type system', 'style-manager' ),

		];

		return $localized;
	}

	/**
	 * Add Color Scheme attribute to <html> tag.
	 *
	 * @since 2.0.0
	 *
	 * @param string $output  A space-separated list of language attributes.
	 * @param string $doctype The type of html document (xhtml|html).
	 *
	 * @return string $output A space-separated list of language attributes.
	 */
	protected function add_dark_mode_data_attribute( $output, $doctype ): string {

		if ( is_admin() || 'html' !== $doctype ) {
			return $output;
		}

		$output .= ' data-dark-mode-advanced=' . \Pixelgrade\StyleManager\get_option( 'sm_dark_mode_advanced', 'off' );

		return $output;
	}
}
