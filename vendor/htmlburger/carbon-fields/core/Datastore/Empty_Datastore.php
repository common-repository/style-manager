<?php

namespace Carbon_Fields\Datastore;

use Carbon_Fields\Field\Field;

/**
 * Empty datastore class.
 */
class Empty_Datastore extends Datastore {
	/**
	 * {@inheritDoc}
	 */
	public function init() {}

	/**
  * {@inheritDoc}
  * @param \Carbon_Fields\Field\Field $field
  */
 public function load( $field ) {}

	/**
  * {@inheritDoc}
  * @param \Carbon_Fields\Field\Field $field
  */
 public function save( $field ) {}

	/**
  * {@inheritDoc}
  * @param \Carbon_Fields\Field\Field $field
  */
 public function delete( $field ) {}
}
