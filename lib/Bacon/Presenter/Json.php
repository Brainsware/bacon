<?php

namespace Bacon\Presenter;

class Json extends \Bacon\Presenter
{
	public function __construct ($data, $context)
	{
		parent::__construct($data, $context);
	}

	public function render ($route, $log = null)
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-Type: text/plain');

		echo json_encode($this->data);
		exit;
	}
}

?>
