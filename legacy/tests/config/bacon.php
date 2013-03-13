<?php

Namespace Config;

class Bacon
{
	### Configuration variables. Change as pleased.
	# Specifies where the bacon library lies
	public static $approotdirectory = '..';  # for configs in tests/
	# Absolute, or relative to the above.
	public static $bacondirectory = 'base/';
	
	public static function getAppRoot()
	{
		if(!defined('APP_ROOT')) {
			if (self::$approotdirectory[0] != '/') {
				define('APP_ROOT', realpath (self::$approotdirectory) . '/');
			} else {
				define('APP_ROOT', realpath ( __DIR__ . '/' . self::$approotdirectory ) . '/' );
			}
		}
		return APP_ROOT;
	}
	
	public static function getBaconRoot()
	{
		if(!defined('BACON_ROOT')) {
			if (self::$bacondirectory[0] != '/') {
				define('BACON_ROOT', realpath (self::getAppRoot() . '/' . self::$bacondirectory) . '/');
			} else {
				define('BACON_ROOT', self::$bacondirectory );
			}
		}
		return BACON_ROOT;
	}
}

?>
