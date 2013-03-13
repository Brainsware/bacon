<?php

/**
 * This is the include.php.
 * Here all system parts are being loaded with __autoload()
 * Modules too.
 *
 * @copyright (c) Brainsware 2006-2011
 * @author Daniel Shimmy Khalil <d.khalil@brainsware.org>
 *
 * @package bacon
 */

if (!defined('BACON_ROOT')) {
	define('BACON_ROOT', realpath(dirname(__FILE__)));
}

if (!defined('APP_ROOT')) {
	define('APP_ROOT', BACON_ROOT . '/../');
}

if (!defined('HTDOCS')) {
	define('HTDOCS', APP_ROOT . '/htdocs/');
}

/**
 * Our super sexy include function.
 *
 * @param string $classname
 */
function bacon_autoload ($classname)
{
	$filename	= str_replace('\\', '/', strtolower($classname));
	$splitPath	= explode('/', $filename);

	if (count($splitPath) !== 1) {
		switch($splitPath[0]) {
			case 'bacon':
			case 'sauce':
				// Bacon system file
				$path = '';
				$type = 'System';
				break;

			case 'config':
				// Config file
				$path = '';
				$type = 'Config';
				break;

			case 'model':
				// Model
				$path = '/models/';
				$type = 'Model';

				unset($splitPath[0]);
				break;

			case 'lib':
				// Misc. module
				$path = '/lib/';
				$type = 'Lib';
				break;

			default:
				// Everything else is handled as a controller
				$path = '/controllers/';
				$type = 'Controller';
				break;
		}
	} else {
		// Twig template engine
		if (substr($splitPath[0], 0, 4) == 'twig') {
			$splitPath = explode('_', $classname);
			$path = '/lib/';
			$type = 'System';

		} else {
			// Simple class names are handled as modules
			$path = '/controllers/';
			$type = 'Controller';
		}
	}

	$filename = implode('/', $splitPath);

	$included = test_and_include($path, $type, $filename, $classname);

	if (!$included && $type == 'System') {
		debug_print_backtrace();

		die('System file "' . $filename . '" could not be found or is not readable.');
	}

	if ($type == 'Config' && method_exists($classname, 'autoConstructSettings')) {
		$classname::autoConstructSettings();
	}
}

/**
 * Tests the inclusion.
 *
 * @param string $path Path to file to include.
 * @param string $type Either 'System' or 'Module'.
 * @param string $filename File to be included.
 * @param string $classname Name of class - needs to be the same as filename (without prefix).
 *
 * @return void
 */
function test_and_include (
	$path,
	$type,
	$filename,
	$classname )
{
	if ($type == 'System') {
		$path_to_file = BACON_ROOT . '/' . $path . $filename . '.php';

	} else {
		$path_to_file = APP_ROOT . $path . $filename . '.php';

	}

	if (file_exists($path_to_file)) {
		if (include $path_to_file) {
			return
				class_exists($classname, false) ||
				interface_exists($classname, false) ||
				trait_exists($classname, false) ||
				empty($classname);
		}
	}

	return false;
}

/**
 * Default exception handler, this function is called, when all else fails.
 *
 * @param exception The uncaught exception cast down the the base class
 */
function default_exception_handler ($e)
{
	// Yes, this is a hack. We need 
	####
	static $log = null;

	if (is_a($e, 'Bacon\Log')) {
		$log = $e;
		return;
	}
	####

	try {
		if (is_a($log, 'Bacon\Log')) {
			error_log('Uncaught exception: '
				. $e->getMessage()
				. ' File ' . $e->getFile()
				. ':' . $e->getLine()
				. $e->getTraceAsString()
			);
		} elseif (class_exists('Bacon\Log', false)) {
			error_log('Uncaught exception: '
				. $e->getMessage()
				. ' File ' . $e->getFile()
				. ':' . $e->getLine()
				. $e->getTraceAsString()
			);
		} else {
			// No BaconLog? Stop execution.
			die ('Uncaught exception: '
				. $e->getMessage()
				. 'File ' . $e->getFile()
				. ':' . $e->getLine());
		}
	} catch (Exception $e) {
		echo get_class($e) . ' thrown within the exception handler. Message: ' . $e->getMessage() .
			' File: ' . $e->getFile() . ':' . $e->getLine();
	}
}

set_exception_handler('default_exception_handler');
spl_autoload_register('bacon_autoload');

include BACON_ROOT . '/sauce/functions.php';

?>
