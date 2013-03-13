<?php
/**
 * @package Bacon
 * @subpackage system
 */

namespace Bacon\Drivers\Auth;

use \Bacon\Log;

/**
 * @package Bacon
 * @subpackage system
 */
class LDAP implements \Bacon\iAuth
{
	const LOGIN_REPLACE = '@LOGIN@';
	private $options = array();
	private static $ldap;

	public static function init (
		array $options = array() )
	{
		$this->options = $options;
		if (empty($this->options['BINDDN'])) {
			Log::error('BINDDN must not be empty.');
			return false;
		}
		
		if (!isset($this->options['BINDPW'])) {
			$this->options['BINDPW'] = '';
		}
		
		if (!self::$ldap = ldap_connect($this->options['HOST'])) {
			Log::error('Could not connect to LDAP Server');
			return false;
		}
		if (isset($this->options['VERSION'])) {
			if (!ldap_set_option(self::$ldap, LDAP_OPT_PROTOCOL_VERSION, $this->options['VERSION'])) {
				Log::error('Could not set Protocol Version.');
				return false;
			}
		}
		if ($this->options['STARTTLS'] == true) {
			if (!ldap_start_tls(self::$ldap)) {
				Log::error('Could not establish StartTLS connection to LDAP Server');
				return false;
			}
		}
	}

	/**
	 * check for User from ldap
	 * 
	 * @param string $userid Username (sAMAccountname, uid) or User's DN
	 * @param array $tokens Tokens such as: password, binduser => true, $userid will be used for bind operation
	 * 
	 * @return bool
	 */
	public static function authenticate (
		$userid,
		array $tokens = array() )
	{
		/* This driver needs a password */
		if ( empty($tokens['password'])) {
			return false;
		}
		$pass = $tokens['password'];

		if (!$this->bind($userid, $pass, $tokens['binduser']) ) {
			return false;
		} elseif (!empty($tokens['binduser']) ) {
			return true;
		}
		
		$cmp = ldap_compare(self::$ldap, 'uid=' . $userid . ',' . $this->options['BASEDN'], 'userPassword', $pass);
		if ($cmp == -1 ) {
			Log::error('LDAP Error: ' . ldap_error(self::$ldap));
			return false;
		} elseif ($cmp === false ) {
			Log::error('Could not verify ' . $userid . ' with specified password.');
			return false;
		}
		return true;
	}

	private static function bind (
		$userid,
		$password,
       	$binduser = NULL )
	{
		$dn = $this->options['BINDDN'];
		if ( !empty($binduser) ) {
			$dn = str_replace (LDAP::LOGIN_REPLACE, $userid, $this->options['BINDDN']);
		}
		if (!ldap_bind(self::$ldap, $dn, $password)) {
			Log::error(ldap_error(self::$ldap));
			return false;
		}
		return true;
	
	}

	public static function getById (
		$userid )
	{
		/* XXX This will be used here, and in getList()
		 *  $this->options['FILTER'] = str_replace (LDAP::LOGIN_REPLACE, $userid, $this->options['FILTER']);
		 **/
		return false;
	}

	public static function getList ()
	{
		return false;
	}

}

?>
