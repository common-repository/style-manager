<?php
/**
 * Customizer textarea control.
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Screen\Customizer\Control;

/**
 * Customizer textarea control class.
 *
 * This handles the 'textarea' control type.
 *
 * @since 2.0.0
 */
class Textarea extends BaseControl {
	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'textarea';

	/**
  * @var bool
  */
 public $live = false;

	/**
	 * Render the control's content.
	 */
	public function render_content() { ?>
		<label>
			<?php if ( ! empty( $this->label ) ) : ?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php endif; ?>
			<textarea id="<?php echo esc_attr( $this->id ); ?>"
			          rows="5" <?php $this->link(); ?>><?php echo esc_textarea( $this->value() ); ?></textarea>
			<?php if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo $this->description; ?></span>
			<?php endif; ?>
		</label>
		<?php

	}
}
