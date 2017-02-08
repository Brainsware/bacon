<?php

/**
   Copyright Brainsware

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

use \Defuse\Crypto\Crypto;
use \Defuse\Crypto\Key;

/**
 * Bacon secure auth cookie
 *
 * @package System
 */
class AuthCookie
{
	public $timeout = 86400;

	private $key;
	private $log;

	public function __construct ($config, $log)
	{
		if (!is_a($config, '\Sauce\Object', true)) {
			$config = Ar($config);
		}

		if (!isset($config['key'])){
			throw new \Exception('No authcookie key set, please place one in your config.');
		}

		$this->log = $log;
		$this->key = $config->key;

		if (isset($config->timeout)) {
			$this->timeout = intval($config->timeout);
		}
	}

	public function read ()
	{
		if (empty($_COOKIE['auth'])) {
			return false;
		}
		try {
			$key = Key::loadFromAsciiSafeString($this->key);
			$cookie = Crypto::Decrypt($_COOKIE['auth'], $key);

			$data = json_decode($cookie);
		} catch (\Exception $e) {
			$this->log->error($e->getMessage());

			return false;
		}

		return $data;
	}

	public function write ($data)
	{
		$key = Key::loadFromAsciiSafeString($this->key);
		$cipher = Crypto::Encrypt(json_encode($data), $key);

		$cookie_domain = '';
		if (!empty(\Config\Base::$auth['cookie_domain'])) {
			$cookie_domain = \Config\Base::$auth['cookie_domain'];
		}

		setcookie('auth', $cipher, time() + $this->timeout, '/', $cookie_domain);
	}

	public function destroy ()
	{
		setcookie('auth', '');
	}
}

?>
