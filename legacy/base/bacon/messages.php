<?php
/**
 * @package System
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
