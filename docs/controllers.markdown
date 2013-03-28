# Controllers

Controllers are the very center of a Bacon application. They function as entry points to the application, use models to retrieve data and presenter to deliver that data in the requested form.

## Configuration {#config}

There is not much configuration needed. Currently the only configurable part is the default fallback controller, defined in `\Config\Base`:

```
namespace Config;

class Base
{
	public static $app = [
		'timezone'        => 'UTC',
		'base_uri'        => '',
		'fallback'        => 'News' # <- Default fallback controller
	];
}
```

The default fallback controller is the one called if no other route was found.

> **Note:** It is planned to split that up into a root controller and fallback (search/404) controller

### Pretty URLs {#front-controller}

Bacon relies on the [Front Controller Pattern](https://en.wikipedia.org/wiki/Front_Controller_pattern) and as such all requests should be handled by Bacon's `boot.php` included in `htdocs/index.php` of your skeleton project. Many modern Web Application Servers like [Nginx](http://wiki.nginx.org/Pitfalls#Front_Controller_Pattern_based_packages) and [Apache HTTPD](http://httpd.apache.org/docs/current/mod/mod_dir.html#fallbackresource) have a very simple way of implementing this.

Supposing you use Apache, add this to your virtual host definition:

```
# httpd.conf, in the VirtualHost:
FallbackResource /index.php
```

This means: send all requests that do not point to a specific file to `index.php`.

## Routing {#routing}

URLs map to controllers and their methods in a very specific way. There is no configuration for routing. We prefer the principle of *convention over configuration*. The base of this convention is the [REST principle](http://en.wikipedia.org/wiki/Representational_state_transfer). A resource maps to a controller and its actions with the [HTTP vocabulary](http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html). The only thing needed for introducing a new URL is dropping in a new controller with the same name and implement its actions.

### Callable controller actions

| Action   | URL                | HTTP Method                 |
|:---------|:-------------------|:----------------------------|
| #index   | /resource          | GET                         |
| #new     | /resource/new      | GET                         |
| #show    | /resource/:id      | GET                         |
| #create  | /resource/         | POST                        |
| #edit    | /resource/:id/edit | GET                         |
| #update  | /resource/:id      | PUT (POST) [^put-delete]    |
| #destroy | /resource/:id      | DELETE (POST) [^put-delete] |

`:id` is an arbitrary identifier for a specific resource you wish to access.

[^put-delete]: Since browsers only allow `GET` and `POST` requests, `PUT` and `DELETE` are distinguished from a normal `POST` request by a parameter called `_method`. It may be embedded in a hidden form field or in the URL as `GET` parameter.


### Namespaces {#namespaces}

Bacon also sports namespaces
