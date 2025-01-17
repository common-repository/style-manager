<?php
/**
 * Frontend output class.
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Provider;

use Pixelgrade\StyleManager\Vendor\Cedaro\WP\Plugin\AbstractHookProvider;
use Pixelgrade\StyleManager\Vendor\Psr\Log\LoggerInterface;

/**
 * Provides the frontend output logic.
 *
 * @since 2.0.0
 */
class FrontendOutput extends AbstractHookProvider {

	/**
	 * CSS properties that will get 'px' as a default unit.
	 */
	const PIXEL_DEPENDENT_CSS_PROPS = [
		'width',
		'max-width',
		'min-width',

		'height',
		'max-height',
		'min-height',

		'padding',
		'padding-left',
		'padding-right',
		'padding-top',
		'padding-bottom',

		'margin',
		'margin-right',
		'margin-left',
		'margin-top',
		'margin-bottom',

		'right',
		'left',
		'top',
		'bottom',

		'font-size',
		'letter-spacing',

		'border-size',
		'border-width',
		'border-bottom-width',
		'border-left-width',
		'border-right-width',
		'border-top-width',
	];

	/**
	 * Media queries cache.
	 *
	 * @var array
	 */
	protected $media_queries = [];

	/**
	 * Options.
	 *
	 * @var Options
	 */
	protected $options;

	/**
	 * Plugin settings.
	 *
	 * @var PluginSettings
	 */
	protected $plugin_settings;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Create the frontend output.
	 *
	 * @since 2.0.0
	 *
	 * @param Options         $options         Options.
	 * @param PluginSettings  $plugin_settings Plugin settings.
	 * @param LoggerInterface $logger          Logger.
	 */
	public function __construct(
		Options $options,
		PluginSettings $plugin_settings,
		LoggerInterface $logger
	) {
		$this->options         = $options;
		$this->plugin_settings = $plugin_settings;
		$this->logger          = $logger;
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function register_hooks() {
		// We will initialize the Customizer logic after the plugin has finished with it's configuration (at priority 15).
		$this->add_action( 'init', 'setup', 15 );
	}

	/**
	 * Setup the frontend output logic.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	protected function setup() {
		// Hook up.
		$this->add_hooks();
	}

	/**
	 * Hook up.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function add_hooks() {

		$this->add_action(
			$this->plugin_settings->get( 'style_resources_location', 'wp_head' ),
			'output_dynamic_style',
			99
		);

		$this->add_filter( 'style_manager/localized_js_settings', 'localize_pixel_dependent_css_props' );

		$this->add_action( 'wp_enqueue_scripts', 'enqueue_assets', 15 );

	}

	/**
	 * Output the CSS style to be used on the frontend.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function output_dynamic_style() { ?>
		<style id="style-manager_output_style">
			<?php echo $this->get_dynamic_style(); ?>
		</style>
		<?php

		/**
		 * From now on we output HTML style tags only for the Customizer preview.
		 * Don't cry if you see 30+ style tags for each section :)
		 */
		if ( ! isset( $GLOBALS['wp_customize'] ) ) {
			return;
		}

		foreach ( $this->options->get_details_all( false, true ) as $option_id => $option_details ) {
			if ( isset( $option_details['type'] ) && $option_details['type'] === 'custom_background' ) {
				$custom_background_output = $this->process_custom_background_field_output( $option_details ); ?>

				<style id="custom_background_output_for_<?php echo \sanitize_html_class( $option_id ); ?>">
					<?php
					if ( ! empty( $custom_background_output )) {
						echo $custom_background_output;
					} ?>
				</style>
			<?php }
		}
	}

	/**
	 *  Get the dynamic style to be used on the frontend of the site.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_dynamic_style(): string {
		$custom_css = '';

		foreach ( $this->options->get_details_all( true ) as $option_id => $option_details ) {
			if ( isset( $option_details['css'] ) && ! empty( $option_details['css'] ) ) {
				$custom_css .= $this->convert_setting_to_css( $option_id, $option_details );
			}

			if ( isset( $option_details['type'] ) && $option_details['type'] === 'custom_background' ) {
				$custom_css .= $this->process_custom_background_field_output( $option_details ) . "\n";
			}
		}

		if ( ! empty( $this->media_queries ) ) {
			foreach ( $this->media_queries as $media_query => $properties ) {
				if ( empty( $properties ) ) {
					continue;
				}

				$media_query_custom_css = '';
				foreach ( $properties as $key => $property ) {
					$property_settings = $property['property'];
					$property_value    = $property['value'];
					$css_output        = $this->process_css_property( $property_settings, $property_value );
					if ( ! empty( $css_output ) ) {
						$media_query_custom_css .= "\t" . $css_output . "\n";
					}
				}

				if ( ! empty( $media_query_custom_css ) ) {
					$media_query_custom_css = "\n" . '@media ' . $media_query . ' { ' . "\n" . "\n" . $media_query_custom_css . '}' . "\n";
				}

				if ( ! empty( $media_query_custom_css ) ) {
					$custom_css .= $media_query_custom_css;
				}

			}
		}

		return apply_filters( 'style_manager_dynamic_style', $custom_css );
	}

	/**
	 * Turn css options into a valid CSS output.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option_id
	 * @param array  $option_details
	 *
	 * @return string
	 */
	protected function convert_setting_to_css( $option_id, $option_details ): string {
		$output = '';

		if ( empty( $option_details['css'] ) ) {
			return $output;
		}

		foreach ( $option_details['css'] as $css_property ) {

			if ( isset( $css_property['media'] ) && ! empty( $css_property['media'] ) ) {
				$this->media_queries[ $css_property['media'] ][ $option_id ] = [
					'property' => $css_property,
					'value'    => $option_details['value'],
				];
				continue;
			}

			if ( isset( $css_property['selector'] ) && isset( $css_property['property'] ) ) {
				$output .= $this->process_css_property( $css_property, $option_details['value'] );
			}
		}

		return $output;
	}

	/**
	 *
	 * @since 2.0.0
	 *
	 * @param array $css_property
	 * @param       $value
	 *
	 * @return string
	 */
	protected function process_css_property( $css_property, $value ): string {
		$unit = $css_property['unit'] ?? '';
		// If the unit is empty (string, not boolean false) but the property should have a unit force 'px' as it
		if ( '' === $unit && in_array( $css_property['property'], self::PIXEL_DEPENDENT_CSS_PROPS ) ) {
			$unit = 'px';
		}

		$css_property['selector'] = apply_filters( 'style_manager/css_selector', $this->css_cleanup_whitespace( $css_property['selector'] ), $css_property );
		if ( empty( $css_property['selector'] ) ) {
			return '';
		}

		$property_output = $css_property['selector'] . ' { ' . $css_property['property'] . ': ' . $value . $unit . '; }' . "\n";

		// Handle the value filter callback.
		if ( isset( $css_property['filter_value_cb'] ) ) {
			$value = $this->maybe_apply_filter( $css_property['filter_value_cb'], $value );
		}

		// Handle output callback.
		if ( isset( $css_property['callback_filter'] ) && is_callable( $css_property['callback_filter'] ) ) {
			$property_output = call_user_func( $css_property['callback_filter'], $value, $css_property['selector'], $css_property['property'], $unit );
		}

		return $property_output;
	}

	/**
	 * Apply a filter (config) to a value.
	 *
	 * We currently handle filters like these:
	 *  // Elaborate filter config
	 *  array(
	 *      'callback' => 'is_post_type_archive',
	 *      // The arguments we should pass to the check function.
	 *      // Think post types, taxonomies, or nothing if that is the case.
	 *      // It can be an array of values or a single value.
	 *      'args' => array(
	 *          'jetpack-portfolio',
	 *      ),
	 *  ),
	 *  // Simple filter - just the function name
	 *  'is_404',
	 *
	 * @since 2.0.0
	 *
	 * @param array|string $filter
	 * @param mixed        $value The value to apply the filter to.
	 *
	 * @return mixed The filtered value.
	 */
	protected function maybe_apply_filter( $filter, $value ) {
		// Let's get some obvious things off the table.
		// On invalid data, we just return what we've received.
		if ( empty( $filter ) ) {
			return $value;
		}

		// First, we handle the shorthand version: just a function name
		if ( is_string( $filter ) && is_callable( $filter ) ) {
			$value = call_user_func( $filter );
		} elseif ( is_array( $filter ) && ! empty( $filter['callback'] ) && is_callable( $filter['callback'] ) ) {
			if ( empty( $filter['args'] ) ) {
				$filter['args'] = [];
			}
			// The value is always the first argument.
			$filter['args'] = [ $value ] + $filter['args'];

			$value = call_user_func_array( $filter['callback'], $filter['args'] );
		}

		return $value;
	}

	/**
	 * @param array $option_details
	 *
	 * @return false|string
	 */
	protected function process_custom_background_field_output( $option_details ) {
		$selector = $output = '';

		if ( ! isset( $option_details['value'] ) ) {
			return false;
		}
		$value = $option_details['value'];

		if ( ! isset( $option_details['output'] ) ) {
			return $selector;
		} elseif ( is_string( $option_details['output'] ) ) {
			$selector = $option_details['output'];
		} elseif ( is_array( $option_details['output'] ) ) {
			$selector = implode( ' ', $option_details['output'] );
		}

		// Loose the ton of tabs.
		$selector = trim( preg_replace( '/\t+/', '', $selector ) );

		$output .= $selector . ' {';
		if ( isset( $value['background-image'] ) && ! empty( $value['background-image'] ) ) {
			$output .= 'background-image: url( ' . $value['background-image'] . ');';
		} else {
			$output .= 'background-image: none;';
		}

		if ( isset( $value['background-repeat'] ) && ! empty( $value['background-repeat'] ) ) {
			$output .= 'background-repeat:' . $value['background-repeat'] . ';';
		}

		if ( isset( $value['background-position'] ) && ! empty( $value['background-position'] ) ) {
			$output .= 'background-position:' . $value['background-position'] . ';';
		}

		if ( isset( $value['background-size'] ) && ! empty( $value['background-size'] ) ) {
			$output .= 'background-size:' . $value['background-size'] . ';';
		}

		if ( isset( $value['background-attachment'] ) && ! empty( $value['background-attachment'] ) ) {
			$output .= 'background-attachment:' . $value['background-attachment'] . ';';
		}
		$output .= "}\n";

		return $output;
	}

	/**
	 * @param array $localized
	 *
	 * @return array
	 */
	protected function localize_pixel_dependent_css_props( $localized ): array {
		if ( ! isset( $localized['config']['px_dependent_css_props'] ) ) {
			$localized['config']['px_dependent_css_props'] = self::PIXEL_DEPENDENT_CSS_PROPS;
		}

		return $localized;
	}

	/* HELPERS */

	/**
	 * Cleanup stuff like tab characters.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	protected function css_cleanup_whitespace( $string ): string {
		return normalize_whitespace( $string );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	protected function enqueue_assets() {
		wp_enqueue_style( 'pixelgrade_style_manager-sm-colors-custom-properties' );
		wp_enqueue_script( 'pixelgrade_style_manager-dark-mode' );
	}
}
