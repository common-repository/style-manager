<?php
/**
 * Customizer screen search functionality provider.
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Screen\Customizer;

use Pixelgrade\StyleManager\Customize\FontPalettes;
use Pixelgrade\StyleManager\Vendor\Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Customizer screen search provider class.
 *
 * @since 2.0.0
 */
class Search extends AbstractHookProvider {

	/**
	 * Create the Customizer screen search.
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
		$this->add_action( 'customize_controls_enqueue_scripts', 'enqueue_assets' );

		$this->add_action( 'customize_controls_print_footer_scripts', 'print_js_templates' );

		// Add configuration data to be passed to JS.
		$this->add_filter( 'style_manager/localized_js_settings', 'add_to_localized_data' );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 2.0.0
	 */
	protected function enqueue_assets() {
		// If there is no customizer search support, bail early.
		if ( ! $this->is_supported() ) {
			return;
		}

		wp_enqueue_script( 'pixelgrade_style_manager-customizer-search' );
	}

	/**
	 * Print the JavaScript templates.
	 *
	 * @since 2.0.0
	 */
	protected function print_js_templates() {
		// If there is no Customizer Search support, bail early.
		if ( ! $this->is_supported() ) {
			return;
		}
		?>
		<script type="text/html" id="tmpl-style-manager-search-button">
			<button type="button" class="customize-search-toggle dashicons dashicons-search" aria-expanded="false"><span
					class="screen-reader-text"><?php esc_html_e( 'Search', 'style-manager' ); ?></span></button>
		</script>

		<script type="text/html" id="tmpl-style-manager-search-form">
			<div id="accordion-section-style-manager-customizer-search" style="display: none;">
				<h4 class="style-manager-customizer-search-section accordion-section-title">
					<span
						class="search-input-label"><?php esc_html_e( 'Search through all controls, menus, and widgets.', 'style-manager' ); ?></span>
					<span class="search-field-wrapper">
						<input type="text" placeholder="<?php esc_html_e( 'Start typing...', 'style-manager' ); ?>"
						       name="style-manager-customizer-search-input" autofocus="autofocus"
						       id="style-manager-customizer-search-input" class="customizer-search-input"/>
						<span class="search-field-button-wrapper">
							<button type="button" class="clear-search button button-primary has-next-sibling"
							        tabindex="0"
							        aria-label="<?php esc_html_e( 'Clear current search', 'style-manager' ); ?>"><?php esc_html_e( 'Clear', 'style-manager' ); ?></button>
							<button type="button" class="close-search button-primary button dashicons dashicons-no"
							        aria-label="<?php esc_html_e( 'Close search', 'style-manager' ); ?>"></button>
						</span>
					</span>
				</h4>
			</div>
		</script>
	<?php }

	/**
	 * Add data to be available in JS.
	 *
	 * @since 2.0.0
	 *
	 * @param array $localized
	 *
	 * @return array
	 */
	protected function add_to_localized_data( $localized ): array {
		if ( empty( $localized['search'] ) ) {
			$localized['search'] = [];
		}

		$localized['search']['excludedControls'] = [
			// Color Palettes Controls
			'sm_dark_color_master_slider',
			'sm_dark_color_primary_slider',
			'sm_dark_color_secondary_slider',
			'sm_dark_color_tertiary_slider',
			'sm_colors_dispersion',
			'sm_colors_focus_point',
			'sm_color_palette',
			'sm_color_palette_variation',
			'sm_color_primary',
			'sm_color_primary_final',
			'sm_color_secondary',
			'sm_color_secondary_final',
			'sm_color_tertiary',
			'sm_color_tertiary_final',
			'sm_dark_primary',
			'sm_dark_primary_final',
			'sm_dark_secondary',
			'sm_dark_secondary_final',
			'sm_dark_tertiary',
			'sm_dark_tertiary_final',
			'sm_light_primary',
			'sm_light_primary_final',
			'sm_light_secondary',
			'sm_light_secondary_final',
			'sm_light_tertiary',
			'sm_light_tertiary_final',
			'sm_swap_colors',
			'sm_swap_dark_light',
			'sm_swap_colors_dark',
			'sm_swap_secondary_colors_dark',
			'sm_advanced_toggle',
			'sm_spacing_bottom',
			// Font Palettes Controls
			FontPalettes::SM_FONT_PALETTE_OPTION_KEY,
			FontPalettes::SM_FONT_PALETTE_VARIATION_OPTION_KEY,
			'sm_font_primary',
			'sm_font_secondary',
			'sm_font_body',
			'sm_font_accent',
			'sm_fonts_connected_fields_preset',
		];

		if ( empty( $localized['l10n'] ) ) {
			$localized['l10n'] = [];
		}
		$localized['l10n']['search'] = [
			'resultsSectionScreenReaderText' => esc_html__( 'Press return or enter to open this section', 'style-manager' ),
		];

		return $localized;
	}

	/**
	 * Check if the Customizer search functionality is supported.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		// Determine if the controls search functionality is supported.
		return apply_filters( 'style_manager/customizer_search_is_supported', true );
	}
}
