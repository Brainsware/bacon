<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'stdout');

$config_dir = realpath (__DIR__ . '/config/' ); 
require_once  $config_dir . '/bacon.php';

# Start execution timer.
$baconStart = microtime();
$baconStart = explode(' ', $baconStart);
$baconStart = $baconStart[0] + $baconStart[1];

# Root/Front directory.
Config\Bacon::getBaconRoot();

/**
 * This will take care of all including.
 * So basically, this is the only include/require you will ever see in this code (well, almost).
 */
$config_dir_base = realpath (__DIR__); 
require Config\Bacon::getBaconRoot() . 'include.php';
Bacon\Log::init();

require_once BACON_ROOT . 'lib/test-more-php/Test-More-OO.php' ;

require_once $argv[1];

?>
