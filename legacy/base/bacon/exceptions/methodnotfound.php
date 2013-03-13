<?php

namespace Bacon\Exceptions;

class MethodNotFound extends \Exception
{
	public function __construct ($message = '', $code = 0, $previous = null)
	{
		return parent::__construct('[Method not found]' . $message, $code, $previous);
	}
}

?>
