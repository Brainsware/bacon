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

namespace Bacon\Exceptions;

class ValidationError extends \ErrorException
{
	public $column;

	public function __construct ($message = '', $code = 0, $previous = null)
	{
		if (is_an_array($message)) {
			$error = A($message);

			$this->column  = $error->column;
			$this->message = $error->message;
		} else {
			$this->column = '(column not set)';
		}

		return parent::__construct('[ValidationError: ' . $this->column . ']' . $this->message, $code, $previous);
	}
}

?>
