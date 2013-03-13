<?php

namespace Config;

class Database
{
	# This is the main database configuration
	public static $main = array (
		'name' => 'data/blag.db',
		# Anything YOUR PDO installation supports.
		'type' => 'sqlite',
		'prefix' => 'bacon',
		'persist' => false,
		);
}

?>
