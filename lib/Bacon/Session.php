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
 * Bacon session handler
 *
 * @package System
 */
class Session implements \ArrayAccess
{
	public $timeout = 1800;
	public $refresh_timeout = 300;
	public $id;

	private $key;
	private $name = 'bacon';
	private $log;

	public function __construct ($config, $log)
	{
		if (!is_a($config, '\Sauce\SObject', true)) {
			$config = Ar($config);
		}

		// Set session hash function to SHA1
		ini_set('session.hash_function', 1);

		if (!isset($config['key'])){
			throw new \Exception('No session key set, please place one in your config.');
		}

		$this->log = $log;
		$this->key = $config->key;

		if (isset($config->timeout)) {
			$this->timeout = intval($config->timeout);
		}

		if (isset($config->refresh_timeout)) {
			$this->refresh_timeout = intval($config->refresh_timeout);
		}

		if (isset($config->name)) {
			$this->name = $config->name;
		} else {
			$this->name .= $this->key;
		}

		if (!$this->created_at) {
			$this->start();
		}

		$this->regenerate();
		$this->refresh();

		$this->last_used_at = time();
	}

	public function start ()
	{
		session_name(hash('sha1', $this->name));
		session_start();

		if (!isset($this->created_at)) {
			$this->created_at = time();
		}
	}

	public function is_timed_out ()
	{
		return isset($this->last_used_at) && ($this->last_used_at + $this->timeout) <= time();
	}

	public function regenerate ($force = false)
	{
		if (!$this->is_timed_out() && !$force) {

			return;
		}

		$this->destroy();
		$this->start();

		$this->created_at = time();
	}

	public function needs_refresh ()
	{
		return ($this->refreshed_at + $this->refresh_timeout) <= time();
	}

	public function refresh ($force = false)
	{
		if (!$this->needs_refresh() && !$force) {
			return;
		}

		if (!session_regenerate_id(true)) {
			throw new \Exception('New session id could not be generated.');
		}

		$this->refreshed_at = time();
		$this->id = session_id();
	}

	public function destroy ()
	{
		$_SESSION = [];

		session_regenerate_id(true);

		if (!session_destroy()) {
			throw new \Exception('Could not destroy session information.');
		}
	}

	public function __get ($key)
	{
		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
		}

		return null;
	}

	public function offsetGet ($key) { return $this->__get($key); }

	public function __set ($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	public function offsetSet ($key, $value) { $this->__set($key, $value); }

	public function __isset ($key)
	{
		return isset($_SESSION[$key]);
	}

	public function offsetExists ($key) { return $this->__isset($key); }

	public function __unset ($key)
	{
		unset($_SESSION[$key]);
	}

	public function offsetUnset ($key) { $this->__unset($key); }
}

?>
