<?php

namespace Config;

class Base
{
	public static $style = 'default';
	public static $time_zone = 'UTC';
	public static $support_mail = 'support@domain.tld';
	public static $session_key = 'massive-bacon';
	public static $uri = '';
	# Generate URIs with or without index.php (use true when you don't have or 
	# want FallbackResource)

	public static $uri_use_index = false;
	# default module which will be loaded if none's set (name of module)
	public static $default_module = 'about';
	public static $allowed_mime = "text/[^/]+|image/[^/]+";
	
	public static $assets = array (
		# general is the fall back server, but you can define one for kind of asset
		# You can also define your own kind of assets here and reuse it in your templates
		'general' => 'http://example.tld/',
		#'styles' => 'http://example.tld/',
		#'scripts' => 'http://example.tld/',
		#'media' => 'http://example.tld/',
	);
	
	public static $session = array (
		'timeout' => '900',
		'regeneration_time' => '600',
		# whichever modules you have installed for PHP session handling
		# http://php.net/manual/en/session.configuration.php#ini.session.save-handler
		'session_handler' => 'files'
	);
	
	public static $logs = array (
    # Possible values: DEBUG, INFO, ERROR, ... ?!
		'log_level' => 'info',
		# Possible values here: SCREEN (Will only output to screen), FILE (Only to file), BOTH, OFF
		'debug_output' => 'SCREEN',
		# Possible values: SYSLOG (default), FS, SPREAD
		'log_driver' => 'FS',
		'log_dir_base' => __DIR__,
		'log_dir' => '/../logs'
	);
		
	### Settings used in autoConstructSettings
	static public $styles_path;
	static public $nostyle_path;
	static public $scripts_path;
	static public $images_path;
	static public $templates_path = 'tests/data/templates';
	
	### Automatically construct settings which depend on other settings. Called from autoloader.
	public static function autoConstructSettings()
	{
		// Check for trailing slashes
		if ( substr(self::$uri, -1) !== '/' ) {
			self::$uri .= '/';
		}

		foreach ( self::$assets as $asset => $uri ) {
			if ( substr(self::$assets[$asset], -1) !== '/' ) {
				self::$assets[$asset] .= '/';
			}
		}
		
		// Styles
		self::$styles_path = !empty(self::$assets['styles']) ? self::$assets['styles'] . self::$style . '/' : self::$uri . 'styles/' . self::$style . '/';
		self::$nostyle_path = !empty(self::$assets['styles']) ? self::$assets['styles']  : self::$uri;
		self::$scripts_path = !empty(self::$assets['scripts']) ? self::$assets['scripts'] : self::$uri . 'scripts/';
		self::$images_path = self::$styles_path . 'images/';
		
		// Templates
		self::$templates_path = !empty(self::$assets['templates']) ? self::$assets['templates'] . self::$style . '/'  : self::$uri . 'templates/' . self::$style . '/';
	}
}

?>
