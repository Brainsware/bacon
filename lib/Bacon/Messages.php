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
 * This class handles display of info and error messages
 *
 * @package System
 */
class Messages
{
	protected $session;
	protected $levels;

	public function __construct($session = null, $ttl = 1, $levels = array())
	{
		$this->session = $session;

		// Mimic session store via stdClass object if no
		// session handler was given
		if ($this->session === null) {
			$this->session = new \Sauce\Object();
		}

		if (empty($levels)) {
			$this->levels = array('error', 'warning', 'notice', 'debug');
		}

		// Check whether messages store is available already -
		// if not create one
		if (!isset($this->session->messages)) {
			$this->session->messages = new \Sauce\Object();
		}

		foreach ($this->levels as $l) {
			if (!isset($this->session->messages->$l)) {
				$this->session->messages->$l = new \Sauce\Object();
			}
		}

		// Check whether a TTL property is set
		if (isset($this->session->messages->ttl)) {
			// TTL < 1 => flush messages
			if ($this->session->messages->ttl < 1) {
				$this->session->messages = new \Sauce\Object();
				$this->session->messages->ttl = $ttl;
			} else {
				$this->session->messages->ttl--;
			}
		} else {
			$this->session->messages = new \Sauce\Object();
			$this->session->messages->ttl = $ttl;
		}

		foreach ($this->levels as $level) {
			if (!isset($this->session->messages->$level)) {
				$this->session->messages->$level = array();
			}
		}
	}

	public function __call ($level, $args)
	{
		if (!in_array($level, $this->levels)) {
			throw new \Exception('Undefined method/message level: ' + $level);
		}

		$l = $this->session->messages->$level;
		$l []= $args;
		$this->session->messages->$level = $l;
	}

	public function __get ($level) {
		return $this->session->messages->$level;
	}
}

?>
