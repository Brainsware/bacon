<?php
/**
 * @package System
 */

namespace Bacon;

/**
 * Template loader for Twig so the layout is automatically extended by the 
 * main template. This makes '{% extend layout %}' unnecessary.
 *
 * @package System
 */ 
class Loader extends \Twig_Loader_Filesystem
{
	protected $default_template;

	public function __construct($paths, $default_template)
	{
		parent::__construct($paths);

		$this->default_template = $default_template;
	}

	public function getSource($name)
	{
		$source = file_get_contents($this->findTemplate($name));

		if ($name == $this->default_template) {
			return '{% extends layout %}' . $source;
		}

		return $source;
	}
}

?>
