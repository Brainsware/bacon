<?php

namespace Config;

class Auth
{

	# logins per session; 0 = unlimited (not recommended)
	public static $max_invalid_logins = 5;
	# possible values: one or more of: DB, LDAP, HTPASSWD, OAUTH, etc..
	public static $auth_driver = array (
		'HTPASSWD',
	);
	public static $htpasswd_db = '/srv/web/esotericsystems.at/pheme/.htpasswd';

	public static $db = array (
		# As per configs/catabase.php
		'instance' => 'main',
		# Same as a model:
		'db_table' => 'users',
		'db_multilang' => false,
		'db_langfields' => array (
			'username',
			'password',
		),
	);


	public static $ldap = array (
		# For defaults, please check http://pear.php.net/manual/en/package.networking.net-ldap2.connecting.php
		'binddn' => 'uid=UID,cn=CN,cn=CN2',
		'bindpw' => 'bindPW',
		'basedn' => 'ou=OU,dc=DC,dc=DC2',
		'host' => 'host.example.tld',
		# use @LOGIN@ as replace variable for the login
		'filter' => '(&(uid=@LOGIN@)(postOfficeBox=POBox))',
		# bool
		'starttls' => true,
		# Can be of type: base, one, sub (default)
		'scope' => 'sub',
		'version' => 3,
	);
}

?>
