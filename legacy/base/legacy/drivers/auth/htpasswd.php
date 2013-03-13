<?php
/**
 * @package Bacon
 * @subpackage system
 */

namespace Bacon\Drivers\User;

use \Bacon\Log;
use \Bacon\Util;

/**
 * @package Bacon
 * @subpackage system
 */
class HTPASSWD implements \Bacon\iAuth
{
	private static $htpasswd;
	private static $userlist = array();

	public static function init (
		array $options = array())
	{
		if (!empty($options['USERS_DB'])) {
			self::$htpasswd = $options['USERS_DB'];
		} else {
			self::$htpasswd = BACON_ROOT . '.htpasswd';
		}
		if (!Util::checkPath(self::$htpasswd, 'f', 'r')) {
			Log::fatal('htpasswd file "' . self::$htpasswd . '" not readable');
		}

		$userlist = file(self::$htpasswd);
		foreach ($userlist as $key => $userpass) {
			$user = explode(':', $userpass);
			if(count($user) == 2) {
				self::$userlist[trim($user[0])] = trim($user[1]);
			}
		}
		return true;
	}

	public static function authenticate (
		$userid,
		array $tokens = array() )
	{
		if (isset(self::$userlist[$userid]) && self::$userlist[$userid] == Util::passgen($tokens['password'])) {
			return true;
		} else {
			return false;
		}
	}

	public static function getById (
		$userid )
	{
	}

	public static function getList ()
	{
	}

	public function __construct (
		$userid = NULL )
	{
		if (!empty($userid) && empty(self::$userlist[$userid])) {
			Log::error('User "' . $userid . '" does not exist.');
			return false;
		}
		$this->userid = $userid;
	}

}

?>
