<?php
/**
 * @package System
 */

namespace Bacon;

use \Bacon\Log as Log;

/**
 * @package System
 */
abstract class Map
{
	public static $cachable = false;
	
	protected $path = array();
	
	public function __construct ()
	{
		# what to do here?
	}
	
	/**
	 * Redirect action to submodule
	 * 
	 * @param string $name
	 * @param array  $arguments
	 */
	public function __call (
		$name,
		$arguments )
	{
		if (!empty($name) && $name != 'index') {
			$submodule = $name;
		} elseif (!empty($this->path['default'])) {
			$submodule = $this->path['default'];
		} else {
			Log::fatal('You are using the mapper wrong.');

		}
		Request::rewrite(array(
					'module' => strtolower(get_class($this)),
					'submodule' => $submodule
				));
	}
}

?>
