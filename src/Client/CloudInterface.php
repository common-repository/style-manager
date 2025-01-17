<?php
/**
 * Cloud interface
 *
 * @package Style Manager
 * @license GPL-2.0-or-later
 * @since 2.0.0
 */

declare ( strict_types = 1 );

namespace Pixelgrade\StyleManager\Client;

/**
 * Segregated interface of something that should communicate with a cloud to provide design assets and send stats.
 */
interface CloudInterface {
	/**
	 * Fetch the design assets data.
	 *
	 * @since 2.0.0
	 *
	 * @return array|null
	 */
	public function fetch_design_assets(): ?array;

	/**
	 * Send stats.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data     The data to be sent.
	 * @param bool  $blocking Optional. Whether this should be a blocking request. Defaults to false.
	 *
	 * @return array|\WP_Error
	 */
	public function send_stats( $data = [], $blocking = false );
}
