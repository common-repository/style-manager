<?php
/**
 * WP-Rocket plugin integration.
 *
 * @link    https://wp-rocket.me/
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Integration;

use Pixelgrade\StyleManager\Vendor\Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * WP-Rocket plugin integration provider class.
 *
 * @since 2.0.0
 */
class WPRocket extends AbstractHookProvider {
	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public function register_hooks() {
		$this->add_filter( 'rocket_exclude_js', 'exclude_webfontloader_script', 10, 1 );
		$this->add_filter( 'rocket_exclude_defer_js', 'exclude_webfontloader_script', 10, 1 );
		$this->add_filter( 'rocket_exclude_cache_busting', 'exclude_webfontloader_script', 10, 1 );

		$this->add_filter( 'rocket_excluded_inline_js_content', 'exclude_inline_script', 10, 1 );
	}

	/**
	 * Exclude the webfontloader script from all operations.
	 *
	 * @since 2.0.0
	 *
	 * @param array $list
	 *
	 * @return array
	 */
	protected function exclude_webfontloader_script( $list ): array {
		if ( \function_exists( 'rocket_clean_exclude_file' ) ) {
			$list[] = \rocket_clean_exclude_file( $this->plugin->get_url( 'vendor_js/webfontloader-1-6-28.min.js' ) );
		}

		return $list;
	}

	/**
  * Exclude the inline script from all operations.
  *
  * @since 2.0.0
  *
  * @param $inline_js
  *
  * @return mixed
  * @param mixed[] $inline_js
  */
 protected function exclude_inline_script( $inline_js ): array {
		$webfontloader_inline_script = 'styleManagerFontLoader = function()';
		$inline_js[] = $webfontloader_inline_script;

		return $inline_js;
	}
}
