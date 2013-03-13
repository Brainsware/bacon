<?php
/**
 * @package bacon
 * @subpackage system
 * @category handler
 */

namespace Bacon\Drivers\Log;

/**
 * Bacon caching handler
 * 
 * @package bacon
 * @subpackage system
 */
class FS implements \Bacon\iLog
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
		global $baconfrontinroot;
		if (!isset(\Config\Base::$logs['log_dir'])) {
			$logdir = ($baconfrontinroot ? BACON_ROOT : APP_ROOT) . 'logs';
		} else {
			$logdir = \Config\Base::$logs['log_dir_base'] . \Config\Base::$logs['log_dir'];
		}
		if ( is_writable($logdir)) {
			$this->logdir = $logdir;
		} else {
			throw new \RuntimeException ('Logdirectory: ' . $logdir . ' is not writable.');
		}
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
		$filename = date('Y-m-d') . '.log';
		$timestamp = date('H:i:s');
		
		$handle = $this->openLogFile($filename);
		
		$logline = "$timestamp | $system | $loglevel | $msg\n";
		
		if ( fwrite($handle, $logline) == false) {
			die ('Unable to write to logfile: ' . $filename);
		}
		fclose($handle);
	}
	
	/**
	 * Opens the log file
	 * 
	 * @param string $filename The filename of the logfile, it's a date (2007-01-01)
	 * @param string $read Optional parameter. If set, the content of a logfile will be returned.\n
	 * 	This parameter specifies the loglevel as well.
	 *
	 * @return Either file pointer or an array with the content of a logfile
	 */
	private function openLogFile (
		$filename)
	{
		if ( $handle = fopen($this->logdir.'/'.$filename, 'a')) {
			return $handle;
		} else {
			 throw new \RuntimeException ('Could not open or create logfile: ' . $filename . ' Check permissions!');
		}
	}
}

?>
