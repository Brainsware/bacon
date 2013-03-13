<?php

namespace Bacon;

/**
 * Bacon messages and log handler
 *
 * This class handles the logging (and display) of info and error messages
 *
 * @package System
 */
class Log
{
	const SYSLOG     = 0;
	const FILESYSTEM = 1;

	const DEBUG   = 3;
	const INFO    = 2;
	const WARNING = 1;
	const ERROR   = 0;

	private $levels = array(
		'DEBUG'   => 3,
		'INFO'    => 2,
		'WARNING' => 1,
		'ERROR'   => 0
	);

	protected $file;
	protected $driver;
	
	public function __construct ($level = self::INFO, $driver = self::FILESYSTEM, $output_file = null)
	{
		$this->driver = $driver;
		$this->level  = $level;

		if ($this->driver == self::FILESYSTEM) {
			if ($output_file === null) {
				$this->filename = \Sauce\Path::join(APP_ROOT,  'logs', 'application.log');
			} else {
				$this->filename = $output_file;
			}

			$this->open();
		}
	}

	public function __destruct ()
	{
		if (isset($this->file)) {
			fclose($this->file);
		}
	}

	public function __call ($level, $message)
	{
		if (!in_array($level, $this->levels)) {
			return false;
		} else {
			$level = $this->levels[strtoupper($level)];
		}

		if ($level > $this->level) { return; }

		if (is_array($message)) {
			$message = var_export($message, true);
		}

		if ($this->driver == self::FILESYSTEM) {
			$this->_write($level, self::backtrace(), $message);
		}
	}

	protected function _write ($level, $system, $message)
	{
		if ($this->driver == self::FILESYSTEM) {
			// Whaaaat.
			if (!is_resource($this->file)) {
				$this->open();
			}

			fwrite($this->file, date('d.m.Y H:i:s') . ' ' . $system . ': ' . $message . "\n");
		} else {
			syslog($level, date('d.m.Y H:i:s') . "$system | $loglevel | $message");
		}
	}

	/**
	 * Backtracing
	 *
	 * Gets caller for logging info
	 *
	 * @return string Either the classname or filename of the caller.
	 */
	public static function backtrace ( )
	{
		$backtrace = debug_backtrace();

		if (isset($backtrace[2]['class'])) {
			return $backtrace[2]['class'];
		} elseif (isset($backtrace[2]['file'])) {
			return $backtrace[2]['file'];
		} else {
			return $backtrace[0]['file'];
		}
	}

	private function open ()
	{
		if (!\Sauce\Path::check($this->filename, 'f', 'w')) {
		    if (!($this->file = fopen($this->filename, 'a'))) {
				throw new \Exception('Log file is not writable (' . $this->filename . ').');
			}
		} else {
			$this->file = fopen($this->filename, 'a');
		}

		if (!is_resource($this->file)) {
			throw new \Exception('Log file resource is not valid anymore');
		}
	}
}

?>
