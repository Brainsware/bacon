<?php

namespace Lib;

require BACON_ROOT . 'lib/recaptcha/recaptchalib.php';

class Recaptcha
{
	private static $config;
	private static $enabled = true;
	private static $public_key;
	private static $private_key;

	public static function isEnabled($config_name = '\Config\Blag')
	{
		if (empty(static::$config)) {
			static::fetchConfig($config_name);
		}

		return static::$enabled;
	}

	public static function html()
	{
		return recaptcha_get_html(static::$public_key);
	}

	public static function verify($remote_address, $challenge, $response)
	{
		$result = recaptcha_check_answer(static::$private_key, $remote_address, $challenge, $response);

		if (!$result->is_valid) {
			throw new \InvalidArgumentException('Invalid arguments passed');
		}
	}

	private static function fetchConfig($config_name)
	{
		static::$config = $config_name;

		if (class_exists($config_name)) {
			static::$enabled = !($config_name::$enable_recaptcha == false);

			if (static::$enabled) {
				static::$public_key = $config_name::$recaptcha_public_key;
				static::$private_key = $config_name::$recaptcha_private_key;
			}
		}
	}
}

?>
