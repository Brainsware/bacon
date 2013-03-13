<?php

namespace Config;

class Database
{
	# This is the main database configuration
	public static $main = array (
		# optional, defaults to localhost; NOTE: localhost != 127.0.0.1 [Difference: Access-method TCP/Sockets]
		'server' => 'localhost',
		'name' => 'bacon.sql',
		# Anything YOUR PDO installation supports.
		'type' => 'sqlite',
		# Don't use root. Please. (no, a user named urmom with all rights is not better.)
		'username' => 'dbusername',
		'password' => 'dbpassword',
		'prefix' => 'bacon',
		'persist' => false,
		# only for mysql as of now
		'encoding' => 'utf8',
		);
	
	# This is an example for an additional db connection.
	public static $replicated_database_for_reading = array (
		'server' => 'baconbase',
		'name' => 'logsnbacon',
		# etc.
		);
}

?>
