<?php

namespace Config;

class Cache
{
	# Timeout in seconds, 0 = infinite
	public static $timeout = '6000';
	# Possible values are the cache drivers installed (default: APC, MEMCACHED) or NOCACHE for no caching.
	public static $driver = 'APC';
	# Site key for the case that you have multiple sites using the same caching facility
	public static $site_key = 'SITEKEY';
	
	# Cachable types, if you want to cache only certain parts of your page, with separate timeout
	public static $cachables = array (
		'Request' => '60000',
		'Config' => '0',
		);
	
	# public static $memcached = array (
	#	'host' => 'memcachedhost',
	#	'port' => '11211',
	#	);
}

?>
