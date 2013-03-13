<?php
/**
 * @package bacon
 * @subpackage system
 */

namespace Bacon\Drivers\Cache;

use \Bacon\Log;

/**
 * Bacon caching handler
 * 
 * @package bacon
 * @subpackage system
 */
class Memcached implements \Bacon\iCache
{
	/**
	 * @var object Memcache object
	 */
	private $mobj;
	
	/**
	 * Initialize the cache directories and check request cache
	 */
	public function __construct ()
	{
		// check for APC
		if (!extension_loaded('memcached')) {
			Log::fatal('Failed to initialize Cache_MEMCACHED: Missing Memcached module');
		}
		
		if (empty(\Config\Cache::$memcached)) {
			Log::fatal('No memcached server config found. Please check your configs/cache.php');
		}
		
		// get server
		$this->mobj = new \Memcached();
		$this->mobj->addServer(\Config\Cache::$memcached['host'], \Config\Cache::$memcached['port']);
		#$this->mobj->setOption(\Memcached::OPT_USE_UDP, true);
	}
	
	public function get (
		$key )
	{
		return $this->mobj->get($key);
	}
	
	public function write (
		$content,
		$key,
   		$timeout )
	{
		if ( !$this->mobj->set($key, $content, $timeout) ) {
			Log::error('Couldn\'t write item to memcached server. Error code: ' . $this->mobj->getResultCode());
		}
		
		return $this->mobj->getResultCode() == \Memcached::RES_SUCCESS;
	}
	
	public function purge (
		$key )
	{
		return $this->mobj->delete($key);
	}
}

?>
