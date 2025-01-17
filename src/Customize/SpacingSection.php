<?php
/**
 * This is the class that handles the logic for Spacing.
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Customize;

use Pixelgrade\StyleManager\Utils\ArrayHelpers;
use Pixelgrade\StyleManager\Vendor\Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Provides the Spacing logic.
 *
 * @since 2.0.0
 */
class SpacingSection extends AbstractHookProvider {

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
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
		$this->add_filter( 'style_manager/filter_fields', 'add_style_manager_section_spacing_config', 12, 1 );
		$this->add_filter( 'style_manager/sm_panel_config', 'reorganize_customizer_controls', 20, 2 );
	}

	/**
	 * Determine if the Spacing Section is supported.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		return apply_filters( 'style_manager/spacing_is_supported', true );
	}

	/**
	 * Set up the Style Manager Customizer section spacing config.
	 *
	 * This handles the base configuration for the controls in the Style Manager section. We expect other parties (e.g. the theme),
	 * to come and fill up the missing details (e.g. connected fields).
	 *
	 * @since 2.0.0
	 *
	 * @param array $config This holds required keys for the plugin config like 'opt-name', 'panels', 'settings'.
	 *
	 * @return array
	 */
	protected function add_style_manager_section_spacing_config( $config ): array {
		// If there is no Spacing support, bail early.
		if ( ! $this->is_supported() ) {
			return $config;
		}

		if ( ! isset( $config['sections']['style_manager_section'] ) ) {
			$config['sections']['style_manager_section'] = [];
		}

		// The section might be already defined, thus we merge, not replace the entire section config.
		$config['sections']['style_manager_section'] = ArrayHelpers::array_merge_recursive_distinct( $config['sections']['style_manager_section'], [
			'options' => [
				'sm_site_container_width' => [
					'type'         => 'range',
					// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
					'setting_type' => 'option',
					// We will force this setting id preventing prefixing and other regular processing.
					'setting_id'   => 'sm_site_container_width',
					'live'         => true,
					'label'        => esc_html__( 'Site Container', 'style-manager' ),
					'desc'         => esc_html__( 'Adjust the maximum amount of width your site content extends to.', 'style-manager' ),
					'default'      => 75,
					'input_attrs'  => [
						'min'          => 60,
						'max'          => 100,
						'step'         => 1,
						'data-preview' => true,
					],
					'css'          => [
						[
							'property' => '--sm-site-container-width',
							'selector' => ':root',
							'unit'     => '',
						],
					],
				],
				'sm_content_inset'        => [
					'type'         => 'range',
					// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
					'setting_type' => 'option',
					// We will force this setting id preventing prefixing and other regular processing.
					'setting_id'   => 'sm_content_inset',
					'live'         => true,
					'label'        => esc_html__( 'Content Inset', 'style-manager' ),
					'desc'         => esc_html__( 'Adjust how much the content is visually inset within the Site Container.', 'style-manager' ),
					'default'      => 230,
					'input_attrs'  => [
						'min'          => 100,
						'max'          => 300,
						'step'         => 10,
						'data-preview' => true,
					],
					'css'          => [
						[
							'property' => '--sm-content-inset',
							'selector' => ':root',
							'unit'     => '',
						],
					],
				],
				'sm_spacing_level'        => [
					'type'         => 'range',
					// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
					'setting_type' => 'option',
					// We will force this setting id preventing prefixing and other regular processing.
					'setting_id'   => 'sm_spacing_level',
					'live'         => true,
					'label'        => esc_html__( 'Spacing Level', 'style-manager' ),
					'desc'         => esc_html__( 'Adjust the multiplication factor of the distance between elements.', 'style-manager' ),
					'default'      => 1,
					'input_attrs'  => [
						'min'          => 0,
						'max'          => 2,
						'step'         => 0.1,
						'data-preview' => true,
					],
					'css'          => [
						[
							'property' => '--sm-spacing-level',
							'selector' => ':root',
							'unit'     => '',
						],
					],
				],
			],
		] );

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

		$spacing_section_fields = [
			'sm_site_container_width',
			'sm_content_inset',
			'sm_spacing_level'
		];

		$spacing_section_config = [
			'title'      => esc_html__( 'Spacing', 'style-manager' ),
			'section_id' => 'sm_spacing_section',
			'priority'   => 30,
			'options'    => [],
		];

		foreach ( $spacing_section_fields as $field_id ) {
			if ( ! isset( $sm_section_config['options'][ $field_id ] ) ) {
				continue;
			}

			if ( empty( $spacing_section_config['options'] ) ) {
				$spacing_section_config['options'] = [ $field_id => $sm_section_config['options'][ $field_id ] ];
			} else {
				$spacing_section_config['options'] = array_merge( $spacing_section_config['options'], [ $field_id => $sm_section_config['options'][ $field_id ] ] );
			}
		}

		$sm_panel_config['sections']['sm_spacing_section'] = $spacing_section_config;

		return $sm_panel_config;
	}
}
