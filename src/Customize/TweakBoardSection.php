<?php
/**
 * This is the class that handles the logic for Tweak Board.
 *
 * @since   2.0.8
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Customize;

use Pixelgrade\StyleManager\Utils\ArrayHelpers;
use Pixelgrade\StyleManager\Vendor\Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Provides the Tweak Board logic.
 *
 * @since 2.0.8
 */

class TweakBoardSection extends AbstractHookProvider {

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
	 * @since 2.0.8
	 */
	public function register_hooks() {
		/*
		 * Handle the Customizer Style Manager section config.
		 */
		$this->add_filter( 'style_manager/filter_fields', 'add_style_manager_section_master_tweak_board_config', 13, 1 );
		$this->add_filter( 'style_manager/sm_panel_config', 'reorganize_customizer_controls', 20, 2 );
	}

	/**
	 * Determine if Tweak Board is supported.
	 *
	 * @since 2.0.8
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		return apply_filters( 'style_manager/tweak_board_is_supported', true );
	}

	/**
	 * Set up the Style Manager Customizer section master Tweak Board config.
	 *
	 * This handles the base configuration for the controls in the Style Manager section. We expect other parties (e.g. the theme),
	 * to come and fill up the missing details (e.g. connected fields).
	 *
	 * @since 2.0.8
	 *
	 * @param array $config This holds required keys for the plugin config like 'opt-name', 'panels', 'settings'.
	 *
	 * @return array
	 */

	protected function add_style_manager_section_master_tweak_board_config ( $config ): array {
		// If there is no Tweak Board support, bail early.
		if ( ! $this->is_supported() ) {
			return $config;
		}

		if ( ! isset( $config['sections']['style_manager_section'] ) ) {
			$config['sections']['style_manager_section'] = [];
		}

		// The section might be already defined, thus we merge, not replace the entire section config.
		$config['sections']['style_manager_section'] = ArrayHelpers::array_merge_recursive_distinct(
			$config['sections']['style_manager_section'], [
				'options'   => [
					'sm_collection_title_position' => [
						'type'         => 'radio',
						// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
						'setting_type' => 'option',
						// We will force this setting id preventing prefixing and other regular processing.
						'setting_id'   => 'sm_collection_title_position',
						'label'        => esc_html__( 'Collections titles position', 'style-manager' ),
						'desc'         => esc_html__( 'Choose "Sideways" to rotate the Collection block titles 90-degrees and align them along the left side of the content rather than at the top of it.', 'style-manager' ),
						'default'      => 'above',
						'choices'      => [
							'above'    => esc_html__( 'Above', 'style-manager' ),
							'sideways' => esc_html__( 'Sideways', 'style-manager' ),
						],
					],
					'sm_collection_hover_effect' => [
						'type'         => 'radio',
						// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
						'setting_type' => 'option',
						// We will force this setting id preventing prefixing and other regular processing.
						'setting_id'   => 'sm_collection_hover_effect',
						'label'        => esc_html__( 'Collections hover effect', 'style-manager' ),
						'desc'         => esc_html__( "Choose the effect triggered when an user moves the cursor above a post collection card's media.", 'style-manager' ),
						'default'      => 'dropcap',
						'choices'      => [
							'none' => esc_html__( 'None', 'style-manager' ),
							'hive' => esc_html__( 'Hive', 'style-manager' ),
							'felt' => esc_html__( 'Felt', 'style-manager' ),
						],
					],
					'sm_decorative_titles_style' => [
						'type'         => 'radio',
						// We will bypass the plugin setting regarding where to store - we will store it cross-theme in wp_options
						'setting_type' => 'option',
						// We will force this setting id preventing prefixing and other regular processing.
						'setting_id'   => 'sm_decorative_titles_style',
						'label'        => esc_html__( 'Titles Fancy Style (BETA)', 'style-manager' ),
						'desc'         => esc_html__( 'Choose a decoration style for Headings with Fancy style.', 'style-manager' ),
						'default'      => 'underline',
						'choices'      => [
							'underline'    => esc_html__( 'Underline', 'style-manager' ),
							'blocky' => esc_html__( 'Blocky', 'style-manager' ),
						],
					],
				]
			]
		);

		return $config;
	}

	/**
	 * Reorganize the Customizer controls.
	 *
	 * @since 2.0.8
	 *
	 * @param array $sm_panel_config
	 * @param array $sm_section_config
	 *
	 * @return array
	 */
	protected function reorganize_customizer_controls( $sm_panel_config, $sm_section_config ): array {

		$tweak_board_section_fields = [
			'sm_collection_title_position',
			'sm_collection_hover_effect',
			'sm_decorative_titles_style'
		];

		$tweak_board_section_config = [
			'title'      => esc_html__( 'Tweak Board', 'style-manager' ),
			'description'=> esc_html__( 'Control visual effects whose aim is to give your site a bolder voice.', 'style-manager' ),
			'section_id' => 'sm_tweak_board_section',
			'priority'   => 40,
			'options'    => [],
		];

		foreach ( $tweak_board_section_fields as $field_id ) {
			if ( ! isset( $sm_section_config['options'][ $field_id ] ) ) {
				continue;
			}

			if ( empty( $tweak_board_section_config['options'] ) ) {
				$tweak_board_section_config['options'] = [ $field_id => $sm_section_config['options'][ $field_id ] ];
			} else {
				$tweak_board_section_config['options'] = array_merge( $tweak_board_section_config['options'], [ $field_id => $sm_section_config['options'][ $field_id ] ] );
			}
		}

		$sm_panel_config['sections']['sm_tweak_board_section'] = $tweak_board_section_config;

		return $sm_panel_config;
	}
}
