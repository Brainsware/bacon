# Getting Started

This article gets you started on working with Bacon as quickly as possible and gives you an overview on how to handle common use cases.

## Installation {#installation}

The bare minimum to get started with Bacon is PHP (>= 5.4.0), composer and git. You can either install those from [php.net](http://php.net/), [getcomposer.org](http://getcomposer.org/) and [git-scm.com](http://git-scm.com/) respectively, or via your distribution.
This tutorial will not go into details of how to do that.

Once you have PHP, composer and git set up, you can create a skeleton project with the following composer command:

```
% composer create-project brainsware/bacon-dist CatBlog
```

> **Note:** `brainsware/bacon-dist` is used here as `brainsware/bacon` itself is only published as a library and would require you to set up a project on your own. 

This will download all the necessary packages, and create all important directories and sample configuration files for your new cat blog.

You should see the following output:
```
Installing brainsware/bacon-dist (0.1.0)
  - Installing brainsware/bacon-dist (0.1.0)
    Loading from cache

Created project in CatBlog
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

We will describe each of these pieces of software and how they fit into the overall architecture of Bacon later on. For now let's consider them as opaque building blocks.

## Configuration {#configuration}

Bacon uses PHP files for storing all of its configurations.

The skeleton project comes with an Intro controller, which is set as the default fallback controller in `Config/Base.php`

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

## How to create a Blog {#blog}

The classic project to demonstrate working with a web framework is a blog. We'll stick with our CatBlog example from above.

### Models {#models}

Before anything else, we need a means of retrieving data from the database. In Bacon this is done with models.

The simplest form of model is a class inheriting from `\Bacon\ORM\Model` in the namespace `\Models` holding a static variable `$table_name` with the table name:

```
# Models/Post.php:

namespace Model;

class Post extends \Bacon\ORM\Model
{
	public static $table_name = 'post';
}
```

This model will provide you with basic functionality for adding, editing, deleting and retrieving entries from the table "post". We'll get back to more indepth discussion of the ORM in its [own chapter](/articles/orm).

### Controllers {#controllers}

An `Application` controller is already present in the skeleton project. It is supposed to hold any global methods that are needed in all controllers, e.g. authentication code or template filter methods. All controllers must derive from that global `Application` controller.

```
# Controllers/Application.php:

namespace Controllers;

class Application extends \Bacon\Controller
{
	public function init ()
	{
		# This method gets called before any other.
		# Useful for initiating things like authentication, session checks,
		# adding hooks to Twig, etc.
		# For now we'll leave it empty.
	}
}
```

Now let's create a basic controller that shows us a list of all entries.

```
# Controllers/Blag.php:

namespace Controllers;

class Blag extends Application
{
	// Blag#index is called when /blag (GET) is requested.
	public function index ()
	{
		# ::all() retrieves a collection of all entries in the post table
		$this->posts = \Models\Post::all();
	}
}
```

### Views {#views}

Bacon uses [Twig](http://twig.sensiolabs.org/) as its templating engine.

Views follow the same structure as controllers do; for each controller there is a folder with the same name. Additionally, a default layout in the `Views/` directory is mandatory. The names of the templates are the same as the [controller actions](#routing)

```
Views/layout.tpl
Views/Blag/index.tpl
```

### Routing {#routing}

URLs map to controllers and their methods in a very specific way. There is no configuration for routing. We prefer the principle of convention over configuration. The base of this convention is the REST principle. A resource maps to a controller and its actions with the HTTP vocabulary. The only thing needed for introducing a new URL is dropping in a new controller with the same name and implement its actions.

The callable controller actions are:

| Action   | URL                | HTTP Method          |
|:---------|:-------------------|:---------------------|
| #index   | /resource          | GET                  |
| #new     | /resource/new      | GET                  |
| #show    | /resource/:id      | GET                  |
| #create  | /resource/         | POST                 |
| #edit    | /resource/:id/edit | GET                  |
| #update  | /resource/:id      | PUT [^put-delete]    |
| #destroy | /resource/:id      | DELETE [^put-delete] |

`:id` is an arbitrary identifier for a specific resource you wish to access. In our example this could be the cat's name: By calling `/catcontent/new` we can create a new cat profile for a cat named PuffyPaws and `#show` that profile with `/catcontent/PuffyPaws`

[^put-delete]: Since browsers only allow `GET` and `POST` requests, `PUT` and `DELETE` are distinguished from a normal `POST` request by a parameter called `_method`. It may be embedded in a hidden form field or in the URL as `GET` parameter.

### Pretty URLs {#front-controller}

Bacon relies on the [Front Controller Pattern](https://en.wikipedia.org/wiki/Front_Controller_pattern) and as such all requests should be handled by Bacon's `boot.php` included in `htdocs/index.php` of your skeleton project. Many modern Web Application Servers like [Nginx](http://wiki.nginx.org/Pitfalls#Front_Controller_Pattern_based_packages) and [Apache HTTPD](http://httpd.apache.org/docs/current/mod/mod_dir.html#fallbackresource) have a very simple way of implementing this.

Supposing you use Apache, add this to your virtual host definition:

```
# httpd.conf, in the VirtualHost:
FallbackResource /index.php
```

This means: send all requests that do not point to a specific file to `index.php`.
