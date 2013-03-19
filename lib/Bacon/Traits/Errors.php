<?php

/**
   Copyright 2012-2013 Brainsware

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/

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
