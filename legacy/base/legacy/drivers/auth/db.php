<?php
/**
 * @package bacon
 * @subpackage system
 */

namespace Bacon\Drivers\Auth;

use \Bacon\Log;
use \Config\Auth as AuthCfg;

/**
 * @package bacon
 * @subpackage system
 */
class DB extends \Bacon\ORM implements \Bacon\iAuth
{
	protected static $db_table = 'user';
	protected static $db_multilang = false;
	
	public $id;
	
	protected $db_username;
	protected $db_password;
	protected $db_created;
	protected $db_modified;
	
	public static function init (
		array $options = array() )
	{
		return true;
	}
	
	public static function authenticate (
		$username,
		array $tokens = array() )
	{
		$db = \Bacon\DB::__getInstance();
		$password = sha1($tokens['password']);
		
		$statement = '
			SELECT id FROM $$PRE_user
			WHERE
				username = ? AND
				password = ?';
		
		if ($result = $db->query($statement, array($username, $password), 'one')) {
			return $result;
		} else {
			return false;
		}
	}
	
}

?>
