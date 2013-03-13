<?php

/**
   Copyright 2012 Brainsware

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/

/* This file is a collection of rather specialized functions that are useful
 * enough to be available everywhere. They try not to infer with any standard
 * library or basic extension functions.
 */

function V()
{
	$data = func_get_args();

	if (count($data) == 1) {
		$data = $data[0];
	}

	return new \Sauce\Vector($data);
}

/* ## A(...)
 *
 * `A` takes all given arguments, no matter what type they are, and stuffs them
 * into a new `Sauce\Object`.
 *
 * This is basically a shortcut function for `new \Sauce\Object(array $args)`.
 *
 * Examples:
 *
 *     > A(1, 2, 3, 4);
 *     # => object(Sauce\Object)#1 (4) {
 *              ["0"]=> int(1)
 *              ["1"]=> int(2)
 *              ["2"]=> int(3)
 *              ["3"]=> int(4)
 *     }
 *
 *     > A(1, array('Hello!' => 'World?'), 'foo')
 *     # => object(Sauce\Object)#1 (3) {
 *           ["0"]=> int(1)
 *           ["1"]=> array(1) {
 *               ["Hello!"]=> string(6) "World?"
 *           }
 *           ["2"]=> string(3) "foo"
 *     }
 *
 *     > A(array(1 => 2, 2 => 3));
 *     # => object(Sauce\Object)#1 (2) {
 *           ["1"]=> int(2)
 *           ["3"]=> int(4)
 *     }
 */
function A ()
{
	$data = func_get_args();

	if (count($data) == 1) {
		$data = $data[0];
	}

	return new \Sauce\Object($data);
}

/* ## Ar(...)
 *
 * `Ar` works exactly the same as `A` but lets the `Sauce\Object` constructor
 * build its data structure in a recursive way. This means every nested array
 * is converted to a `Sauce\Object`.
 *
 * Examples:
 *
 *     > Ar(1, array('Hello' => 'world!'), 'foo');
 *     # => object(Sauce\Object)#2 (3) {
 *         ["0"]=> int(1)
 *         ["1"]=> object(Sauce\Object)#1 (1) {
 *             ["Hello"]=> string(6) "world!"
 *         }
 *         ["2"]=> string(3) "foo"
 *     }
 */
function Ar ()
{
	$data = func_get_args();

	if (count($data) == 1) {
		$data = $data[0];
	}

	return new \Sauce\Object($data, true);
}

/* ## is\_an\_array($var)
 *
 * Check whether a given variable either really is an array, a `Sauce\Object`
 * instance, or any other object that implements `ArrayAccess`.
 *
 * Examples:
 *
 *     > is_an_array(array());
 *     # => bool(true)
 *
 *     > is_an_array('foo');
 *     # => bool(false)
 *
 *     > is_an_array(A('a', 'b', 'c'));
 *     # => bool(true)
 *
 *     > is_an_array(1);
 *     # => bool(false)
 *
 *     > is_an_array(new ArrayObject());
 *     # => bool(true)
 *
 */
function is_an_array ($var)
{
	return is_array($var) || $var instanceof \Sauce\Object || $var instanceof \ArrayAccess;
}

/* Check whether given variable is set and not null; basically mimicking Ruby's
 * 'or equals' operator (||=).
 */
function or_equals ($var, $value)
{
	return (isset($var) && $var !== null) ? $var : $value;
}

/* ## dump(...)
 *
 * Takes arbitrary arguments and outputs them via `var_dump()`, wrapped inside
 * a `<pre></pre>` tag.
 */
function dump ()
{
	$args = func_get_args();

	if (!is_cli()) echo '<pre>';

	foreach ($args as $arg) {
		var_dump($arg);
	}

	if (!is_cli()) echo '</pre>';
}

function is_cli()
{
	return php_sapi_name() == 'cli';
}

function is_cli_server () {
	return (php_sapi_name() == 'cli-server');
}

/* ## split\_uri($uri)
 * TODO
 * @return string
 */
function split_uri ($uri) {
	$splitted_uri = explode('/', $uri);

	# Remove empty strings from beginning and end
	if (count($splitted_uri) > 1) {
		$last = end($splitted_uri);
		$first = reset($splitted_uri);

		if (empty($last)) {
			array_pop($splitted_uri);
		}

		if (empty($first)) {
			array_shift($splitted_uri);
		}
	}

	return $splitted_uri;
}

/* ## path\_info()
 *
 * `path_info()` returns the canonalized path info of the request.
 *
 * If the path info contains /index.php this will be left out.
 * Get parameters are not returned.
 */
function path_info () {
	if (array_key_exists('PATH_INFO', $_SERVER)) {
		$path_info = $_SERVER['PATH_INFO'];
	} else {
		$path_info = $_SERVER['REQUEST_URI'];

		/* Find out whether the script is located in some other directory than / and
		 * replace the prefix in the request URI.
		 */
		$prefix = join('/', array_filter(explode('/', $_SERVER['SCRIPT_NAME']), function ($item) {
			return !empty($item) && 'index.php' != $item;
		}));

		if (!empty($prefix)) {
			$path_info = str_replace($prefix, '', $path_info);
		}
	}

	// In some cases path info does include the GET parameters
	// (passed in the URI), so we need to remove those.
	$question_mark = strpos($path_info, '?');

	if ($question_mark !== false) {
		$path_info = substr($path_info, 0, $question_mark);
	}

	return $path_info;
}

/* ## http\_method()
 *
 * `http_method()` returns the lowercased Request Method. This can be either
 * the HTTP verb itself, or otherwise, if a POST variable named `_method` exists
 * this will be returned.
 *
 * @return string
 * 
 */
function http_method () {
	$method = $_SERVER['REQUEST_METHOD'];

	if (array_key_exists('_method', $_REQUEST)) {
		$method = $_REQUEST['_method'];
	}

	return strtolower($method);
}

?>
