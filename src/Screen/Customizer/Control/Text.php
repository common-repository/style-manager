<?php
/**
 * Customizer text control.
 *
 * @since   2.0.0
 * @license GPL-2.0-or-later
 * @package Style Manager
 */

declare ( strict_types=1 );

namespace Pixelgrade\StyleManager\Screen\Customizer\Control;

/**
 * Customizer text control class.
 *
 * This handles the 'text' control type.
 *
 * @since 2.0.0
 */
class Text extends BaseControl {
	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'text';

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
			<input type="<?php echo esc_attr( $this->type ); ?>" <?php $this->input_attrs(); ?>
			       value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?> />
			<?php if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo $this->description; ?></span>
			<?php endif; ?>
		</label>
		<?php

	}
}
