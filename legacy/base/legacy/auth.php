<?php
/**
 * @package System
 */

namespace Bacon;
use \Config\Auth as CfgAuth;

/**
 * @package System
 */
class Auth
{
	private static $drivers = array();
	private static $authinstances = array();
	
	/**
	 * Initialize the authentication system
	 */
	public static function init ()
	{
		// Get timeout from config.ini
		if (!class_exists('\Config\Auth')) {
			Log::error('No auth config found.');
			return false;
		}
		
		foreach (CfgAuth::$auth_driver as $inst) {
			self::$userinstances[$inst] = NULL;
			$driver = CfgAuth::$auth_driver;
			$class = 'Bacon\\Drivers\\Auth\\' . $driver;
			if (!class_exists($class)) {
				Log::error('Auth driver ' . $driver . ' not found.');
				continue;
			}
			if ($authinst = $class::init($config)) {
				self::$authinstances[$inst] = $class;
			}
		}
	}
	
	/**
	 * Login. Check against backend, writ to session.
	 * 
	 * @param string $username Username.
	 * @param array $tokens Authentication tokens, such as a password or a cert.
	 * @param string $instance Get user from specific instance?
	 * 
	 * @return bool
	 */
	public static function login (
		$username,
		array $tokens = array(),
   		$instance = NULL )
	{
		$id = NULL;
		if (!empty($instance)) {
			if (empty(self::$authinstances[$instance])) {
				Log::debug('Login called with ' . $instance . ' instance though no user system driver is selected.');
				return false;
			}
			$class = self::$authinstances[$instance];
			$id = $class::authenticate($username, $tokens);
		}
		foreach (self::$authinstances as $instance) {
			if (empty($instance)) {
				continue;
			}
			if ($id = $instance::authenticate($username, $tokens)) {
				break;
			}
		}
		if (!empty($id)) {
			$_SESSION['user'] = array (
				'loggedin' => true,
				'username' => $username);
			$_SESSION['user']['userid'] = $id;
			$_SESSION['user']['userkey'] = sha1($username . implode($tokens));
			return true;
		} else {
			return false;
		}
	}
	
	public static function logout ()
	{
		unset($_SESSION['user']);
	}
	
	/**
	 * Returns whether or not a user is logged in.
	 * 
	 * @return bool
	 */
	public static function loggedIn ()
	{
		return !empty($_SESSION['user']['loggedin']);
	}
	
	
	public static function getId ()
	{
		return isset($_SESSION['user']['userid']) ? $_SESSION['user']['userid'] : 0;
	}
	public static function getKey ()
	{
		return isset($_SESSION['user']['userkey']) ? $_SESSION['user']['userkey'] : '';
	}
}

/**
 * @package System
 */
interface iAuth {
	public static function init (
		array $options = array() );
	
	/**
	 * Check if supplied user-data links to an existing user.
	 * 
	 * @param string $userid User identification string - whatever that might be.
	 * @param array $tokens Potential extra data, such as domain or realm.
	 * 
	 * @return bool
	 */
	public static function authenticate (
		$userid,
		array $tokens = array() );
}

?>
