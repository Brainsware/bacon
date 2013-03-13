<?php
/**
 * @package System
 */

namespace Bacon;

/**
 * Bacon caching handler
 *
 * @package System
 */
class Cache
{
	public static $cacheinst;

	private static $driver;
	private static $timeout;
	private static $types = array();

	/**
	 * @var array actions which prevent request from beeing cached
	 */
	private static $forbidden_actions = array('edit', 'preview', 'create');

	/**
	 * Initialize the cache drivers
	 *
	 * @return mixed
	 */
	public static function init ()
	{
		if (!class_exists('\\Config\\Cache') || empty(\Config\Cache::$driver)) {
			# fallback
			self::$driver = 'NOCACHE';
		} else {
			self::$driver = \Config\Cache::$driver;
		}

		/* Only init, if caching not disabled! */
		if (self::$driver == 'NOCACHE') {
			return false;
		}

		$class = 'Bacon\\Drivers\\Cache\\' . self::$driver;
		self::$cacheinst = new $class;
		self::$timeout = \Config\Cache::$timeout;
		self::$types = \Config\Cache::$cachables;
	}

	/**
	 * Check and output cached request
	 *
	 * @param bool $render
	 *
	 * @return bool
	 */
	public static function checkCache (
		$type = 'Request',
   		array $params = array() )
	{
		if (self::$driver == 'NOCACHE' || empty(self::$driver) || !in_array($type, self::$types)) {
			return false;
		}

		if (empty($params) && $type == 'Request') {
			$params = Request::$request;
		}

		// Check if request is already cached...
		$key = self::generateCacheKey($type, $params);

		if (!$cached_file = self::$cacheinst->get($key) ) {
			return false;
		}

		if ($type == 'Request') {
			echo Template::renderCached($cached_file);
		}

		return $cached_file;
	}

	/**
	 * Writes content to cache if allowed
	 *
	 * @param string $type
	 * @param string $content
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function writeCache (
		$type,
		$content,
		array $params = array() )
	{
		if (self::$driver == 'NOCACHE' || empty(self::$driver) || !in_array($type, self::$types)) {
			return false;
		}

		if ($type != 'Request' || ($type == 'Request' && self::allowedToCache($params) && is_object(self::$cacheinst))) {
			if (!empty(\Config\Cache::$cachables[$type])) {
				$timeout = \Config\Cache::$cachables[$type];
			}

			if (!isset($timeout)) {
				$timeout = self::$timeout;
			}

			$key = self::generateCacheKey($type, $params);

			return self::$cacheinst->write($content, $key, $timeout);
		}

		return false;
	}

	/**
	 * Checks if request is allowed to be cached
	 *
	 * @param string $parameter request parameter
	 *
	 * @return bool True if caching is allowed
	 */
	private static function allowedToCache ($params = NULL)
	{
		if ($params == NULL) {
			$params = Request::$request;
		}

		if ( in_array($params['action'], self::$forbidden_actions) ||
				$params['module']::$cachable == false ) {
			return false;
		}

		return true;
	}

	/**
	 * Generates caching path from given parameter
	 *
	 * @param string $type Caching type
	 * @param array $params Parameter used for generation
	 *
	 * @return string Path
	 */
	private static function generateCacheKey ($type, array $params = array())
	{
		// Generate caching path for requests
		if (empty($params)) {
			$params = Request::$request;
		}

		$cachekey = \Config\Cache::$site_key;

		if ($type == 'Request') {
			$cachekey = $params['language'] . $params['module'] . $params['action'];
			unset($params['language'], $params['module'], $params['action']);
		}

		foreach ( $params as $parameter ) {
			$cachekey .= $parameter;
		}

		return $type . hash('sha1', $cachekey);
	}
}

/**
 * @package System
 */
interface iCache {
	/**
	 * Checks if requested page is cached
	 * Returns content on success and false on failure
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed Cached content or false on failure
	 */
	public function get ($key);

	/**
	 * Saves a content of specific type to the cache
	 *
	 * @param string $content Value to be cached
	 * @param string $key Cache key
	 *
	 * @return bool true on success, false on failure
	 */
	public function write (
		$content,
		$key,
   		$timeout );

	/**
	 * Purges possibly cached page
	 *
	 * @param string $key Request parameter
	 *
	 * @return bool true on success, false on failure
	 */
	public function purge ($key);
}

?>
