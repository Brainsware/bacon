<?php

namespace Bacon\Traits\Errors;

/* A trait to throw error messages on and collect them in a \Sauce\Vector.
 * 
 * NOTE: #__construct_errors needs to be called before using the trait.
 *
 * NOTE: #error can be overriden to accept more information than just a plain
 *       message string.
 */
trait Errors
{
	/* Internal storage object */
	protected $errors;

	/* Initializes a new error store. */
	protected function __construct_errors ()
	{
		$this->errors = V([]);
	}

	/* Returns the contents of the error store. */
	public function errors ()
	{
		return $this->errors;
	}

	/* Returns whether or not the store contains any errors. */
	public function has_errors ()
	{
		return !$this->errors->is_empty();
	}

	/* Store an arbitrary error message.
	 *
	 * NOTE: This is to be overridden if anything else but error messages
	 *       are to be stored.
	 */
	protected function error ($message)
	{
		$this->errors->push($message);
	}
}
