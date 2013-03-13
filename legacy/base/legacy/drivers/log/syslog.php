<?php
/**
 * @package bacon
 * @subpackage system
 */

namespace Bacon\Drivers\Log;

/**
 * Bacon caching handler
 * 
 * @package bacon
 * @subpackage system
 */
class Syslog implements \Bacon\iLog
{
	/**
	 * Initialize the logging class
	 *
	 * Check if $logdir exists, is writeable and a directory.
	 * 
	 * @param string $logdir The log directory
	 */
	public function __construct ()
	{
		openlog(APP_ROOT, LOG_ODELAY, LOG_USER);
	}
	
	/**
	 * Log writing
	 *
	 * This function actually writes the logfile itself.
	 * 
	 * @param int $loglevel The loglevel
	 * @param string $system Caller
	 * @param string $msg Log message
	 */
	public function writeLog (
		$loglevel,
		$system,
		$msg )
	{
		syslog($loglevel, $system . ' | ' . $msg);
	}
}

?>
