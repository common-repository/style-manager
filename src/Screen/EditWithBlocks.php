<?php
/**
 * Provider for screens when editing posts/pages with the block editor.
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Screen;

use Pixelgrade\StyleManager\Provider\FrontendOutput;
use Pixelgrade\StyleManager\Provider\Options;
use Pixelgrade\StyleManager\Provider\PluginSettings;
use Pixelgrade\StyleManager\Customize\Fonts;
use Pixelgrade\StyleManager\Vendor\Cedaro\WP\Plugin\AbstractHookProvider;
use Pixelgrade\StyleManager\Vendor\Psr\Log\LoggerInterface;

/**
 * Provider class for screens when editing posts/pages with the block editor.
 *
 * This is the class that handles the overall logic for integration with the new Gutenberg Editor (WordPress 5.0+).
 *
 * @since 2.0.0
 */
class EditWithBlocks extends AbstractHookProvider {

	/**
  * Selectors that we will use to constrain CSS rules to certain scopes.
  * @var string
  */
 public static $editor_namespace_selector = '.editor-styles-wrapper';
	/**
  * @var string
  */
 public static $title_namespace_selector = '.editor-styles-wrapper .editor-post-title__block';
	/**
  * @var string
  */
 public static $title_input_namespace_selector = '.editor-styles-wrapper .editor-post-title__block .editor-post-title__input';

	/**
	 * Get the block namespace CSS selector according to the WP version in use.
	 *
	 * @return string
	 * @global string $wp_version
	 *
	 */
	public static function get_block_namespace_selector(): string {
		global $wp_version;

		$is_old_wp_version = version_compare( $wp_version, '5.4', '<' );

		if ( $is_old_wp_version ) {
			return '.editor-styles-wrapper .editor-block-list__block';
		}

		return '.editor-styles-wrapper .block-editor-block-list__block';
	}

	/**
	 * Regexes
	 */
	public static $gutenbergy_selector_regex = '/^(\.edit-post-visual-editor|\.editor-block-list__block).*$/';
	public static $root_regex = '/^(body|html).*$/';
	public static $title_regex = '/^(h1|h1\s+.*|\.single\s*\.entry-title.*|\.entry-title.*|\.page-title.*|\.article__?title.*)$/';
	/* Regexes based on which we will ignore selectors = do not include them in the selector list for a certain rule. */
 /**
  * @var mixed[]
  */
 public static $excluded_selectors_regex = [
		// We don't want to mess with buttons as we have a high likelihood of messing with the Gutenberg toolbar.
		'/^\s*button/',
		'/^\s*\.button/',
		'/^\s*input/',
		'/^\s*select/',
		'/^\s*#/',    // ignore all ids
		'/^\s*div#/', // ignore all ids

		//		'/\.u-/',
		//		'/\.c-/',
		//		'/\.o-/',
		//		'/\.site-/',
		//		'/\.card/',
		//
		//		'/^\s*\.archive/',
		//		'/^\s*\.search/',
		//		'/^\s*\.no-results/',
		//		'/^\s*\.home/',
		//		'/^\s*\.blog/',
		//		'/^\s*\.site-/',
		//		'/\.search/',
		//		'/\.page/',
		//		'/\.mce-content-body/',
		//		'/\.attachment/',
		//		'/\.mobile/',
		//
		//		'/\.sticky/',
		//		'/\.custom-logo-link/',
		//
		//		'/\.entry-meta/',
		//		'/\.entry-footer/',
		//		'/\.header-meta/',
		//		'/\.nav/',
		//		'/\.main-navigation/',
		//		'/navbar/',
		//		'/comment/',
		//		'/\.dummy/',
		//		'/\.back-to-top/',
		//		'/\.page-numbers/',
		//		'/\.featured/',
		//		'/\.widget/',
		//		'/\.edit-link/',
		//		'/\.posted-on/',
		//		'/\.cat-links/',
		//		'/\.posted-by/',
		//		'/\.more-link/',
		//
		//		'/jetpack/',
		//		'/wpforms/',
		//		'/contact-form/',
		//		'/sharedaddy/',
	];

	/**
	 * User messages to display in the WP admin.
	 *
	 * @var array
	 */
	protected $user_messages = [
		'error'   => [],
		'warning' => [],
		'info'    => [],
	];

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
	 * Style Manager Fonts.
	 *
	 * @var Fonts
	 */
	protected $sm_fonts;

	/**
	 * Frontend output provider.
	 *
	 * @var FrontendOutput
	 */
	protected $frontend_output;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Create the edit with blocks screen.
	 *
	 * @since 2.0.0
	 *
	 * @param Options         $options         Options.
	 * @param PluginSettings  $plugin_settings Plugin settings.
	 * @param Fonts           $sm_fonts        Style Manager Fonts.
	 * @param FrontendOutput  $frontend_output Frontend output.
	 * @param LoggerInterface $logger          Logger.
	 */
	public function __construct(
		Options $options,
		PluginSettings $plugin_settings,
		Fonts $sm_fonts,
		FrontendOutput $frontend_output,
		LoggerInterface $logger
	) {
		$this->options         = $options;
		$this->plugin_settings = $plugin_settings;
		$this->sm_fonts        = $sm_fonts;
		$this->frontend_output = $frontend_output;
		$this->logger          = $logger;
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public function register_hooks() {
		// Styles and scripts when editing.
		$this->add_action( 'enqueue_block_editor_assets', 'enqueue_style_manager_scripts', 10 );
		$this->add_action( 'enqueue_block_editor_assets', 'dynamic_styles_scripts', 999 );

		$this->add_filter( 'admin_body_class', 'add_sm_dark_classname_to_body' );
		$this->add_action( 'admin_enqueue_scripts', 'print_script_to_move_dark_classname_to_html' );
		$this->add_filter( 'block_editor_settings_all', 'alter_editor_settings' );
	}

	public function alter_editor_settings( $settings ) {
		ob_start(); ?>
		<style id="style-manager_output_style">
			<?php echo $this->frontend_output->get_dynamic_style(); ?>
			<?php echo $this->sm_fonts->getFontsDynamicStyle(); ?>
		</style>
		<?php $styles = ob_get_clean();
		if ( isset( $settings['__unstableResolvedAssets']['styles'] ) ) {
			$settings['__unstableResolvedAssets']['styles'] .= $styles;
		}
		return $settings;
	}

	/**
	 * Determine if Gutenberg is supported.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		$gutenberg = false;

		// Determine if the block editor is active for the frontend.
		if ( has_action( 'enqueue_block_assets' ) ) {
			// Gutenberg is installed and activated.
			$gutenberg = true;
		}

		// Determine if the block editor is being used in the WP admin.
		$current_screen = get_current_screen();
		if ( is_admin() && method_exists( $current_screen, 'is_block_editor' ) && get_current_screen()->is_block_editor() ) {
			$gutenberg = true;
		}

		return apply_filters( 'style_manager/gutenberg_is_supported', $gutenberg );
	}

	/**
	 * Retrieve the editor CSS file handle.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_editor_style_handle(): string {
		global $wp_styles;
		if ( ! ( $wp_styles instanceof \WP_Styles ) ) {
			return '';
		}

		// We need to look into the registered theme stylesheets and get the one most likely to be used for Gutenberg.
		// Thus, we can attach inline styles to it.
		$theme_dir_uri = get_template_directory_uri();
		$theme_slug    = get_template();

		$handle   = 'wp-edit-post'; // this is better than nothing as it is the main editor style.
		$reversed = array_reverse( $wp_styles->registered );
		/** @var \_WP_Dependency $style */
		foreach ( $reversed as $style ) {
			// This is the most precise.
			if ( is_string( $style->src ) && 0 === strpos( $style->src, $theme_dir_uri ) ) {
				$handle = $style->handle;
				break;
			}

			// If it is prefixed with the theme slug, it is good also.
			if ( is_string( $style->handle )
			     && ( 0 === strpos( $style->handle, $theme_slug . '-' ) || 0 === strpos( $style->handle, $theme_slug . '_' ) ) ) {

				$handle = $style->handle;
				break;
			}
		}

		return $handle;
	}

	/**
	 * Return the frontend CSS file handle.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_frontend_style_handle(): string {
		global $wp_styles;
		if ( ! ( $wp_styles instanceof \WP_Styles ) ) {
			return '';
		}

		// We need to look into the registered theme stylesheets and get the one most likely to be used for Gutenberg.
		// Thus, we can attach inline styles to it.
		$style_css_uri = get_template_directory_uri() . '/style.css';
		$theme_slug    = get_template();

		$handle   = 'wp-block-library'; // this is better than nothing as it is the main editor frontend style.
		$reversed = array_reverse( $wp_styles->registered );
		/** @var \_WP_Dependency $style */
		foreach ( $reversed as $style ) {
			// This is the most precise.
			if ( is_string( $style->src ) && 0 === strpos( $style->src, $style_css_uri ) ) {
				$handle = $style->handle;
				break;
			}

			// If it is prefixed with the theme slug, it is good also.
			if ( is_string( $style->handle )
			     && ( 0 === strpos( $style->handle, $theme_slug . '-' ) || 0 === strpos( $style->handle, $theme_slug . '_' ) )
			     && is_string( $style->src )
			     && false !== strpos( $style->src, '.css' ) ) {

				$handle = $style->handle;
				break;
			}
		}

		return $handle;
	}

	protected function enqueue_style_manager_scripts() {
		wp_enqueue_style( 'pixelgrade_style_manager-sm-colors-custom-properties' );
	}

	public function add_sm_dark_classname_to_body( $classname ) {
		$dark_mode = \Pixelgrade\StyleManager\get_option( 'sm_dark_mode_advanced', 'off' );

		if ( $dark_mode === 'on' ) {
			$classname = $classname . ' dark-mode-advanced';
		}

		return $classname;
	}

	public function print_script_to_move_dark_classname_to_html() {
		$data = 'wp.domReady( function() {
					if ( document.body.classList.contains( "dark-mode-advanced" ) ) {
						document.documentElement.classList.add( "is-dark" );
					}
				} );';

		wp_enqueue_script( 'wp-dom-ready' );
		wp_add_inline_script( 'wp-dom-ready', $data );
	}

	/**
	 * Output Style Manager's dynamic styles and scripts in the Gutenberg context.
	 *
	 * @since 2.0.0
	 */
	public function dynamic_styles_scripts() {
		if ( ! $this->plugin_settings->get( 'enable_editor_style', true ) ) {
			return;
		}

		$enqueue_parent_handle = $this->get_editor_style_handle();
		if ( empty( $enqueue_parent_handle ) ) {
			return;
		}

		add_filter( 'style_manager/font_css_selector', [ $this, 'gutenbergify_font_css_selectors' ], 10, 2 );
		$this->sm_fonts->enqueue_frontend_scripts_styles();
		wp_add_inline_style( $enqueue_parent_handle, $this->sm_fonts->getFontsDynamicStyle() );
		remove_filter( 'style_manager/font_css_selector', [ $this, 'gutenbergify_font_css_selectors' ], 10 );

		add_filter( 'style_manager/css_selector', [ $this, 'gutenbergify_css_selectors' ], 10, 2 );
		wp_add_inline_style( $enqueue_parent_handle, $this->frontend_output->get_dynamic_style() );
		remove_filter( 'style_manager/css_selector', [ $this, 'gutenbergify_css_selectors' ], 10 );
	}

	/**
	 * Transform a set of selectors to target the Gutenberg editor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $selectors
	 * @param array  $css_property
	 *
	 * @return string
	 */
	public function gutenbergify_css_selectors( $selectors, $css_property ): string {

		// Treat the selector(s) as an array.
		$selectors = $this->maybeExplodeSelectors( $selectors );

		$new_selectors = [];
		foreach ( $selectors as $selector ) {
			// Clean up
			$selector = trim( $selector );

			// If the selector matches the excluded, skip it.
			if ( $this->preg_match_any( self::$excluded_selectors_regex, $selector ) ) {
				continue;
			}

			// If the selector is already Gutenbergy, we will not do anything to it
			if ( preg_match( self::$gutenbergy_selector_regex, $selector ) ) {
				$new_selectors[] = $selector;
				continue;
			}

			// We will let :root selectors be
			if ( ':root' === $selector ) {
				$new_selectors[] = $selector;
				continue;
			}

			// For root html elements, we will not prefix them, but replace them with the block and title namespace.
			if ( preg_match( self::$root_regex, $selector ) ) {
				// We will ignore pseudo-selectors
				if ( preg_match( '/^(body|html)[\:\+]+.*$/', $selector ) ) {
					continue;
				}

				// When it comes to background properties applied at the body level, we need to scope to the editor namespace
				if ( isset( $css_property['property'] ) && 0 === strpos( $css_property['property'], 'background' ) ) {
					$new_selectors[] = preg_replace( '/^(html body|body|html)/', self::$editor_namespace_selector, $selector );
				} else {
					$new_selectors[] = preg_replace( '/^(html body|body|html)/', self::get_block_namespace_selector(), $selector );
					$new_selectors[] = preg_replace( '/^(html body|body|html)/', self::$title_namespace_selector, $selector );
				}
				continue;
			}

			// If we encounter selectors that seem that they could target the post title,
			// we will add selectors for the Gutenberg title also.
			if ( preg_match( self::$title_regex, $selector ) ) {
				$new_selectors[] = preg_replace( self::$title_regex, self::$title_input_namespace_selector, $selector );
			}

			$new_selectors[] = self::get_block_namespace_selector() . ' ' . $selector;
		}

		return implode( ', ', $new_selectors );
	}

	/**
	 * Transform a set of font selectors to target the Gutenberg editor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $selectors An array of standardized, cleaned selectors where the key is the selector and the value is possible details array.
	 *
	 * @return array
	 */
	public function gutenbergify_font_css_selectors( $selectors ): array {

		$new_selectors = [];
		foreach ( $selectors as $selector => $selector_details ) {
			// If the selector matches the excluded, skip it.
			if ( $this->preg_match_any( self::$excluded_selectors_regex, $selector ) ) {
				continue;
			}

			// If the selector is already Gutenbergy, we will not do anything to it
			if ( preg_match( self::$gutenbergy_selector_regex, $selector ) ) {
				$new_selectors[ $selector ] = $selector_details;
				continue;
			}

			// We will let :root selectors be
			if ( ':root' === $selector ) {
				$new_selectors[ $selector ] = $selector_details;
				continue;
			}

			// For root html elements, we will not prefix them, but replace them with the block and title namespace.
			if ( preg_match( self::$root_regex, $selector ) ) {
				$new_selector                   = preg_replace( '/^(html body|body|html|)/', self::get_block_namespace_selector(), $selector );
				$new_selectors[ $new_selector ] = $selector_details;
				$new_selector                   = preg_replace( '/^(html body|body|html)/', self::$title_namespace_selector, $selector );
				$new_selectors[ $new_selector ] = $selector_details;
				continue;
			}

			// If we encounter selectors that seem that they could target the post title,
			// we will add selectors for the Gutenberg title also.
			if ( preg_match( self::$title_regex, $selector ) ) {
				$new_selector                   = preg_replace( self::$title_regex, self::$title_input_namespace_selector, $selector );
				$new_selectors[ $new_selector ] = $selector_details;
			}

			$selector                   = self::get_block_namespace_selector() . ' ' . $selector;
			$new_selectors[ $selector ] = $selector_details;
		}

		return $new_selectors;
	}

	/**
	 * Preg_match a series of regex against a subject.
	 *
	 * @since 2.0.0
	 *
	 * @param string|array $regexes
	 * @param string       $subject
	 *
	 * @return bool Returns true if at least one of the regex matches, false otherwise.
	 */
	public function preg_match_any( $regexes, $subject ): bool {
		if ( is_string( $regexes ) ) {
			$regexes = [ $regexes ];
		}

		if ( ! is_array( $regexes ) ) {
			return false;
		}

		foreach ( $regexes as $regex ) {
			if ( preg_match( $regex, $subject ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Attempt to split a string with selectors and return the parts as an array.
	 * If not a string or no comma present, just returns the value.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value
	 *
	 * @return array|false|string[]
	 */
	public function maybeExplodeSelectors( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		return preg_split( '#[\s]*,[\s]*#', $value, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
	}
}
