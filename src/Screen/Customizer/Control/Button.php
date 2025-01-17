<?php
/**
 * Customizer button control.
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Screen\Customizer\Control;

/**
 * Customizer button control class.
 *
 * This handles the 'button' control type.
 *
 * @since 2.0.0
 */
class Button extends BaseControl {
	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'button';

	/**
  * @var string
  */
 public $action = '';

	/**
	 * Render the control's content.
	 */
	public function render_content() { ?>
		<button type="button" class="style-manager_button button" <?php $this->input_attrs(); ?>
		        data-action="<?php echo esc_html( $this->action ); ?>"><?php echo esc_html( $this->label ); ?></button>
		<?php

	}
}
