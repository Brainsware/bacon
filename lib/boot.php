<?php

/**
 * The Brainsware bɐcon web framework
 *
 * @package bacon
 *
 * @author Alexander Panek <a.panek@brainsware.org>
 * @author Daniel Shimmy Khalil <d.khalil@brainsware.org>
 * @author Markus Shmafoozius Liebhart <m.liebhart@brainsware.org>
 * @author Igor jMCg Galić <i.galic@brainsware.org>
 * @copyright (c) Brainsware 2006-2013
 *
 * @version 0.10.x
 */

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'stdout');

if (!class_exists('\Config\Base')) {
	throw new \Exception('There was no base configuration found in Config/Base.php! Aborting.');
}

$config = Ar([
	'app'     => \Config\Base::$app,
	'session' => \Config\Base::$session,
	'logging' => \Config\Base::$logging
]);

date_default_timezone_set(!empty($config->timezone) ? $config->timezone : 'UTC');

if (!defined('BACON_ROOT')) {
	define('BACON_ROOT', realpath(dirname(__FILE__)));
}

if (!defined('APP_ROOT')) {
	define('APP_ROOT', realpath(BACON_ROOT . '/../../../../'));
}

if (!defined('HTDOCS')) {
	define('HTDOCS', APP_ROOT . '/htdocs/');
}

$log = new \Bacon\Log($config->logging);

Bacon\DB::setLog($log);
Bacon\ORM\DatabaseSingleton::set_logger($log);

if (is_cli()) {
	$session = new \Sauce\Object();
} else {
	$session = new \Bacon\Session($config->session, $log);
}

$params = Ar($_REQUEST);
$env = Ar($_ENV);
$env->mergeF($_SERVER);

/* PHP's builtin webserver doesn't know FallbackResource. So we have to do it on our
 * own in this case.
 *
 * Check whether given URI maps to a file or is to be handled by us.
 */
if (is_cli_server()) {
	$path = $params->request_uri;

	if (empty($path) || $path === null) {
		$path = $env->request_uri;
	}

	/* Remove any GET parameters (<uri>?foo=bar) if present. */
	$question_mark = strpos($path, '?');

	if ($question_mark !== false) {
		$path = substr($path, 0, $question_mark);
	}
	
	if (is_file(\Sauce\Path::join(HTDOCS, $path))) {
		return false;
	}
}

/* PHP forms the $_FILES array in the following way:
 * $_FILES => {
 * 	name => [ file1, file2, file3, ... ],
 * 	size => [ .... ],
 * 	...
 *
 * So we just transform it to:
 *
 * $files (\Sauce\Vector) => [
 * 	{
 * 		name => file1,
 * 		size => 1234,
 * 		...
 * 	},
 * 	{ ... }
 * ]
 */
if (!empty($_FILES)) {
	$files = new \Sauce\Vector();

	foreach ($_FILES as $field => $uploads) {
		$keys = array_keys($uploads);
		$count = count($uploads[$keys[0]]);

		$obj = new \Sauce\Object();

		if (!is_array($keys[0])) {
			foreach ($keys as $key) {
				$obj->$key = $uploads[$key];
			}
		} else {
			for ($i = 0; $i < $count; $i++) {
				foreach ($keys as $key) {
					$obj->$key = $uploads[$key][$i];
				}

				$obj->field = $field;
			}
		}

		$files->push($obj);
	}

	$params->files = $files;
}

$app = new \Bacon\App($config->app, $session, $log, $params, $env);

$app->prepare(path_info(), http_method());
$app->run();

?>
