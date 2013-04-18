# Object Relational Mapper (ORM)

## Configuration {#config}

Bacon automatically looks for the database configuration in `Config/Database.php`. It must contain a class `Config\Database` with a public static variable called `$main`:

```
namespace Config;

class Database
{
	public static $main = [
		'server'   => '', # Enter your server host here
		'name'     => '', # The name of your database
		'type'     => '', # Anything your PDO Installation supports. (http://www.php.net/manual/en/pdo.drivers.php)
		'username' => '', # The username you want to connect to your database with
		'password' => '', # The password.
	];
}
```

> **Note:** If no config is found and you try query the database an exception is thrown.

## Models {#models}

Say you want to create a model for handling posts in your blog. The simplest form of a model is a class inheriting `\Bacon\ORM\Model` in the namespace `\Models` holding a static variable `$table_name` with the table name.

```
# Models/Post.php
namespace Models;

class Post extends \Bacon\ORM\Model
{
	public static $table_name = 'post';
}
```

### Timestamp columns {#timestamp-columns}

Further, the ORM assumes you have certain columns in place, namely:

* `table_name.id`
* `table_name.created_at`
* `table_name.updated_at`

The two columns `created_at` and `updated_at` are automatically filled in on creation and update of a row. If you do not want this, e.g. when you have a simple model for a join table, just add the static variable `$timestamps` with the value `false`:

```
# Models/Post.php

namespace Models;

class Post extends \Bacon\ORM\Model
{
	public static $table_name = 'post';
	public static $timestamps = false;  # Turn off created_at and updated_at support
}
```

### Altering the primary key {#primary-key}

In case you have a different primary key than `id`, you can alter this as well:

```
# Models/Post.php

namespace Models;

class Post extends \Bacon\ORM\Model
{
	public static $table_name  = 'post';
	public static $primary_key = 'post_id';  # Change the name of the primary key
}
```

> **Note:** In some cases you might have two or more columns defined as primary key. Although this is possible to do (`$primary_key = 'id, other_id'`), there is no actual support for this right now.

## Query Interface {#query-interface}

Bacon tries to provide an intuitive query interface. The base of this interface is the class `Collection`. When you call any of the model's [query methods](#query-interface-methods), a new `Collection` instance is created. This instance allows you to form a simple query without having to write SQL. Once that query is sent and the result is retrieved, `Collection` instantiates one object per row and stores it.

Almost all of the available methods return the `Collection` object itself, so one may chain calls to it.

### Available methods {#query-interface-methods}

| Method   | Parameters                                | Return Value        | Causes SQL to be sent |
|:---------|:------------------------------------------|:--------------------|:----------------------|
| `find`   | primary key value                         | Model instance      | Yes                   |
| `first`  | *none* (implies `LIMIT 1`)                | Model instance      | Yes                   |
| `last`   | *none* (implies `LIMIT 1`)                | Model instance      | Yes                   |
| `all`    | *none*                                    | Collection instance | Yes                   |
| `where`  | string or array                           | Collection instance | No                    |
| `limit`  | integer                                   | Collection instance | No                    |
| `offset` | integer                                   | Collection instance | No                    |
| `order`  | string, string (column name, asc\|desc)   | Collection instance | No                    |
| `group`  | string (column name)                      | Collection instance | No                    |

### Lazy-loading {#lazy-loading}

The ORM also supports *lazy-loading*. Until you call `find`, `all`, `first` or `last`, no query is sent to the database.

```
# Construct a collection, but do not load anything yet:
$posts = \Models\Post::where([ 'user_id' => '1' ]);

# Add something to the query:
if (isset($limit)) {
  $posts->limit($limit);
}

# Actually send the query to the database:
$posts->all();
```

### Examples {#query-interface-examples}

Retrieve a single row with a given primary key value from the database:

```
$post = \Models\Post::find($id);
```

Retrieve a certain amount of rows from the database:

```
$posts = \Models\Post::limit(10)->all();
```

Retrieve a subset based on a condition:

```
$posts = \Models\Post::where('published_at != NULL')->all();
```

Combining the last two:

```
$posts = \Models\Post::where('published_at != NULL')
                     ->limit(10)
					 ->all();
```

Retrieve the first published post:

```
$post = \Models\Post::where('published_at != NULL')->first();
```

Retrieve the last published post:

```
$post = \Models\Post::where('published_at != NULL')->last();
```

## Storing Data {#storing-data}

Storing data is as simple as creating a model instance, filling it with data and calling `save()` in order to send data to the database.

```
$new_post = new \Models\Post();

$new_post->title = 'Blog title';
$new_post->content = '...';

$new_post->save();

```

There is no need to make one statement per column, one can pass an associative array of column data (`[ 'column name' => $data ]`)to the constructor as well:

```
$post = [
  'title'   => 'Blog title',
  'content' => '...'
];

$new_post = new \Models\Post($post);
$new_post->save();
```

> **Note:** You can only set the column data of the model at hand. Passing cascaded arrays with data of other models included is not supported and thus causing "undefined behaviour".

## Validation & Error Handling {#validation-error-handling}

To validate data of a model before storing it in database, `\Bacon\ORM\Model` provides a method called `validate()`. To add validations, just override this method.

```
namespace Models;

class Post extends \Bacon\ORM\Model
{
	public static $table_name = 'post';

	public function validate ()
	{
		# Check whether the title is set
		if (empty($this->title)) {
			throw new \Exception('No title set!');
		}
	}
}
```

To actually be able to distinguish validation errors from one another and display them accordingly, `Model` also provides an internal error storage including the following methods:

| Name         | Parameters                    | Return value             |
|:-------------|:------------------------------|:-------------------------|
| `error`      | string, string (key, message) | *none*                   |
| `errors`     | *none*                        | `\Sauce\Vector` instance |
| `has_errors` | *none*                        | `true` or `false`        |

If any errors are present after `validate()` has been called, no data will be written back to the database.

The above example can also be written using the error methods as follows:

```
namespace Models;

class Post extends \Bacon\ORM\Model
{
	public static $table_name = 'post';

	protected function validate ()
	{
		# Check whether the title is set
		if (empty($this->title)) {
			$this->error('title', 'Column `title` may not be empty.');
		}
	}
}

```
