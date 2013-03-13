<?php

namespace Test;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1); 

require __DIR__ . '/include.php';

require \Sauce\Path::join(APP_ROOT, 'base/lib/Enhance-PHP/EnhanceTestFramework.php');

class Object extends \Enhance\TestFixture
{
	public function to_array ()
	{
		$nothing = new \Sauce\Object();

		return \Enhance\Assert::isNotObject($nothing->to_array()) && 
		       \Enhance\Assert::isArray($nothing->to_array());
	}
}

\Enhance\Core::runTests();

?>
