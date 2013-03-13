<?php

namespace Bacon\Presenter;

class Redirect extends \Bacon\Presenter
{
	public function __construct ($data, $context)
	{
		parent::__construct($data, $context);
	}

	public function render ($route, $log = null)
	{
		$from = $this->context->request_uri;
		$to = implode('/', $this->data);

		if ($to[0] != '/') {
			$to = '/' . $to;
		}

		if ($log) {
			$log->info("Redirecting {$from} to {$to}");
		}

		header('Location: ' . $to);
		exit;
	}
}

?>
