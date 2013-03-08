Got bacon?
==========

The bacon PHP framework is a lean, clean, simple and fast MVC framework without too much fuzz about it.

Server Requirements:
* A webserver
* PHP 5.4.0 or greater

Installation:
=============

Bacon PHP depends on PHP Composer (https://getcomposer.org/), it will take care
of all the dependencies. If you don't use it yet, it's about time you check it
out.

First off, you'll need to create a composer.json for your project. The minimum
it has to contain is:

```
{
	"name": "yourappname",
	"minimum-stability": "dev",

	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/Brainsware/sauce"
		},
		{
			"type": "vcs",
			"url": "https://github.com/Brainsware/bacon"
		}
	],

	"require": {
		"php": ">= 5.4.0",
		"brainsware/sauce": "dev-master",
		"brainsware/bacon": "dev-master"
	},

	"autoload": {
		"psr-0": {
			"Models": ".",
			"Controllers": ".",
			"Config": "."
		}
	}
}
```

Once you have that set up, you can call `composer install` and have all the
dependencies installed.

Then it is time to create the necessary directories:

```
mkdir -p Config Controllers Models Views logs session htdocs
touch logs/application.log
touch Config/Base.php
```

In Config/Base.php insert the following and modify as needed:

```
namespace Config;

class Base
{
	public static $app = [
		'timezone'        => 'UTC',
		'base_uri'        => '',
		'fallback'        => 'intro'
	];

	public static $session = [
		'timeout'           => 86400,
		'regeneration_time' => 3600,
		'key'               => 'bacon!',
		'session_handler'   => 'files'
	];

	public static $logging = [
		'level'  => 'info',
		'debug'  => 'screen',
		'driver' => 'syslog'
	];
}
```


Note #1: change permission for logs/application.log if Bacon can not write to
         it by default.

Note #2: the session directory is optional. Just make sure to let PHP know
         where to put the session files. (php.ini -> `session.save_path`)
