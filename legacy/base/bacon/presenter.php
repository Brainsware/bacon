<?php

namespace Bacon;

abstract class Presenter
{
	protected $data;
	protected $context;

	public function __construct ($data, $context)
	{
		$this->data    = $data;
		$this->context = $context;
	}

	/*
	 * Called by App; is being passed the requested route as well as an instance
	 * of Log to be able to output information about rendering (profiling) or
	 * errors that otherwise might end up as ambiguous messages in the browser.
	 * */
	public function render ($route, $log = null) { }
}

?>
