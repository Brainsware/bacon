<?php

namespace Config;

$base = array(
	'timezone'        => 'UTC',
	'base_uri'        => '',
	'base_controller' => 'blag',

	'session' => array(
		'timeout'           => 900,
		'regeneration_time' => 600,
		'key'               => 'bacon!',
		'session_handler'   => 'files'
	),

	'logs' => array(
		'level'  => 'info',
		'debug'  => 'screen',
		'driver' => 'syslog'
	)
);

?>
