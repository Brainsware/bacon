<?php

namespace Bacon\Presenter;

class Http extends \Bacon\Presenter
{
	public function __construct ($data, $context)
	{
		parent::__construct($data, $context);
	}

	public function render ($route, $log = null)
	{
		http_response_code($this->data);
		exit;
	}
}

?>
