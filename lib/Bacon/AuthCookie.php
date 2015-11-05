<?php

/**
   Copyright 2012-2015 Brainsware

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

/**
 * Bacon secure auth cookie
 *
 * @package System
 */
class AuthCookie
{
	public $timeout = 86400;
	public $refresh_timeout = 3600;

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
		$this->key = base64_decode($config->key);

		/*if (isset($config->timeout)) {
			$this->timeout = intval($config->timeout);
		}

		if (isset($config->refresh_timeout)) {
			$this->refresh_timeout = intval($config->refresh_timeout);
		}

		if (!$this->created_at) {
			$this->start();
		}

		$this->regenerate();
		$this->refresh();

		$this->last_used_at = time();*/
	}

	public function read ()
	{
		if (empty($_COOKIE['auth'])) {
			return false;
		}
		try {
			$cookie = Crypto::Decrypt($_COOKIE['auth'], $this->key);

			$data = json_decode($cookie);
		} catch (\Exception $e) {
			$this->log->error($e->getMessage());

			return false;
		}

		return $data;
	}

	public function write ($data)
	{
		$cipher = Crypto::Encrypt(json_encode($data), $this->key);

		setcookie('auth', $cipher, time() + $this->timeout, '/', '');
	}

	public function destroy ()
	{
		setcookie('auth', '');
	}
}

?>
