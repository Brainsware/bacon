Got bacon?
==========

The bacon PHP framework is a lean, clean, simple and fast MVC framework without too much fuzz about it.

Server Requirements:
* A webserver
* PHP 5.4.0 or greater ([php.net](http://php.net/))
* Composer ([getcomposer.org](http://getcomposer.org/))

# Installation:

Once you have PHP and composer set up, you can create a skeleton project with the following:

```
% composer create-project brainsware/bacon-dist project-name
```

This will download all the necessary software, and create all important directories and sample configuration files for your new project:

```
Installing brainsware/bacon-dist (0.1.0)
  - Installing brainsware/bacon-dist (0.1.0)
    Loading from cache

Created project in project-name
Loading composer repositories with package information
Installing dependencies
  - Installing brainsware/php-markdown-extra-extended (dev-master 0.1.0)
    Cloning 0.1.0

  - Installing brainsware/sauce (0.1.0)
    Loading from cache

  - Installing minmb/phpmailer (dev-master df44323)
    Cloning df443234ad0ca10cbf91a0c0a728b256afcab1d1

  - Installing twig/twig (dev-master ba67e2c)
    Cloning ba67e2cf8e2ca6cada1de5a316a724df648c52ac

  - Installing brainsware/bacon (0.1.0)
    Loading from cache

Writing lock file
Generating autoload files
```

# Configuration:

Bacon uses PHP files for storing all of its configurations.

The skeleton project we provide comes with an Intro controller, which is set as the default fallback controller in `Config/Base.php`

The second config file you will want to look at is `Config/Database.php`
Here are the basic options you will want to set for your database:

```
'server'   => 'db.dbznet',  # Enter your server host here
'name'     => 'blogDB',     # The name of your database
'type'     => 'mysql',      # Anything your PDO Installation supports. (http://www.php.net/manual/en/pdo.drivers.php)
'username' => 'blogDBuser', # The username you want to connect to your database with
'password' => 'VryScrPswd', # The password.
```

Bacon does not provide default values for these options. If your application needs a database, you will have to create it and connect Bacon to it via `Config/Database.php`.
