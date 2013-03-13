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
class APC implements \Bacon\iCache
{
	/**
	 * Initialize the cache directories and check request cache
	 */
	public function __construct ()
	{
		// check for APC
		if (!extension_loaded('apc')) {
			Log::fatal('Failed to initialize Cache_APC: Missing APC module');
		}
	}
	
	public function get (
		$key )
	{
		return apc_fetch($key);
	}
	
	public function write (
		$content,
		$key,
   		$timeout )
	{
		if ( !apc_store($key, $content, $timeout) ) {
			Log::error('Couldn\'t write cache. Unknown error.');
		}
		
		return true;
	}
	
	public function purge (
		$key )
	{
		return apc_delete($key);
	}
}

?>
