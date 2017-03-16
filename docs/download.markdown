## Requirements {#requirements}

* PHP (>= 7.0)
* Git [git-scm.com](http://git-scm.com/)
* Composer [getcomposer.org](http://getcomposer.org/)

## Creating a new project with Bacon {#create}

The recommended way to download Bacon and start a project with is by using the `composer create-project` command:

```
% composer create-project brainsware/bacon-dist ProjectName
```

You can find more info [here](/articles/getting-started#installation).

## Add Bacon to an existing project {#add}

If you however want to add Bacon as a dependency to your existing project, you need to add it to your `composer.json` file. Since Bacon is released on [packagist.org](https://packagist.org/packages/brainsware/bacon), all you need to do is add the following to your `require` section:

```
{
	"require": {
		"brainsware/bacon": "1.*"
	}
}
```

This will declare the newest version of Bacon as your dependency and add all the classes to your autoloader as well.

## Development Checkout {#checkout}

You can find a detailed description on how to set up a development environment for Bacon in the [Contribute & Support section](/articles/contribute-support).
