<?php

/**
   Copyright 2012-2013 Brainsware

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/

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
	const SYSLOG     = 0; // Log by writing to syslog
	const FILESYSTEM = 1; // Log by writing into a file
	const STDERR     = 2; // Log using error_log

	const ERROR   = 0;
	const WARNING = 1;
	const INFO    = 2;
	const DEBUG   = 3;

	private $levels = [
		'error'   => 0,
		'warning' => 1,
		'info'    => 2,
		'debug'   => 3,
	];

	protected $file;
	protected $driver;
	
	public function __construct ($level = self::INFO, $driver = self::FILESYSTEM, $output_file = null)
	{
		$this->levels = A($this->levels);

		if (is_an_array($level)) {
			$options     = $level;
			$level       = $options->level;
			$driver      = $options->driver;
			$output_file = $options->file;
		}

		if (defined('self::' . strtoupper($driver))) {
			$this->driver = constant('self::' . strtoupper($driver));
		} else {
			$this->driver = self::STDERR;
		}

		if (defined('self::' . strtoupper($level))) {
			$this->level = constant('self::' . strtoupper($level));
		} else {
			$this->level = self::INFO;
		}

		if ($this->driver == self::FILESYSTEM) {
			if ($output_file === null) {
				$this->filename = \Sauce\Path::join(APP_ROOT, 'logs', 'application.log');
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

	public function __call ($level, $messages)
	{
		$messages = V($messages);

		if (!$this->levels->keys()->includes(strtolower($level))) {
			throw new \BadMethodCallException("Method not found: {$level} (" . $messages->join(', ') . ")");
		}

		$level = $this->levels[strtolower($level)];

		if ($level > $this->level) { return; }

		foreach ($messages as $message) {
			if (is_an_array($message)) {
				$message = var_export($message, true);
			}

			$this->_write($level, $message);
		}
	}

	protected function _write ($level, $message)
	{
		$caller = self::caller();
		$level = strtoupper($this->levels->keys()[$level]);

		$str = sprintf("[%s %s#%s] %s", $level, $caller->class, $caller->method, $message);

		switch ($this->driver) {
			case self::SYSLOG:
				syslog($this->levels[strtolower($level)], sprintf("%s %s", date(DATE_ATOM), $str));
				break;

			case self::FILESYSTEM:
				if (!is_resource($this->file)) { $this->open(); }

				fwrite($this->file, sprintf("%s\n", $str));
				break;

			default:
			case self::STDERR:
				error_log($str);
				break;
		}
	}

	/**
	 * Backtracing
	 *
	 * Gets caller for logging info
	 *
	 * @return string Either the classname or filename of the caller.
	 */
	public static function caller ( )
	{
		$backtrace = V(debug_backtrace());

		foreach ($backtrace as $line) {
			if ($line['class'] === 'Bacon\Log') continue;

			return A([
				'class'  => $line['class'],
				'file'   => $line['file'],
				'method' => $line['function'],
				'object' => $line['object']
			]);
		}

		/*
		if (isset($backtrace[2]['class'])) {
			return $backtrace[2]['class'];
		} elseif (isset($backtrace[2]['file'])) {
			return $backtrace[2]['file'];
		} else {
			return $backtrace[0]['file'];
		}
		 */
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
