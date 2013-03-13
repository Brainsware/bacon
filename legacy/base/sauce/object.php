<?php

namespace Sauce;

/**
 * ## Bacon Objects
 *
 * `Object` is a class full of *magic*.
 *
 * For one, it doesn't only just provide standalone objects which you can
 * throw data at in almost any way, you can also access this data in any way.
 *
 * `Object` implements two interfaces: `ArrayAccess` and `Countable`. Those are
 * defined in the PHP standard library:
 *
 * <http://us.php.net/manual/en/class.arrayaccess.php>
 * <http://us.php.net/manual/en/class.countable.php>
 *
 * That means you can use any array functions with an instance of this class,
 * including `count($arr)`.
 *
 * As any other objects, properties can be set arbitrarily. The key difference
 * here is: you can also access them using the index operator.
 *
 * Example:
 *
 * 		> $a = new Object();
 * 		> $a->foo = 'foo';
 * 		> dump($a['foo']);
 * 		# => string(3) "foo"
 *
 * 	Of course this works the other way round, too:
 *
 * 		> $a['bar'] = 'bar'
 * 		> dump($a->bar);
 * 		# => string(3) "bar"
 *
 * 	> **Note:** all keys are automatically converted to lowercase.
 *
 * Additionally, `Object` uses the trait `CallableProperty`. It gives you the
 * power to add a closure/anonymous function as property of an instance and 
 * call it immediately. Without this base class, you would have to store the
 * function in a seperate variable or use call_user_func. `CallableProperty`
 * also binds the function to the `Object` instance.
 */
class Object implements \ArrayAccess, \Countable, \JsonSerializable
{
	use CallableProperty;

	protected $storage;

	public function __construct ($data = [], $recursive = false)
	{
		$this->storage = [];

		if (!is_an_array($data)) {
			$this->storage[0] = $data;
			return;
		}
		
		if (is_a($data, '\Sauce\Object')) {
		    $data = $data->storage;
		}

		foreach ($data as $key => $value) {
			if (is_numeric($key)) {
				$key = strval($key);
			}

			if (is_string($key)) {
				$key = strtolower($key);
			}

			if ($recursive && is_an_array($value)) {
				$this->storage[$key] = new Object($value, true);
			} else {
				$this->storage[$key] = $value;
			}
		}
	}

	public function offsetExists ($key)
	{
		return array_key_exists($key, $this->storage);
	}
	public function __isset ($key) { return $this->offsetExists($key); }
	public function has_key ($key) { return $this->offsetExists($key); }

	public function offsetGet ($key)
	{
		if ($this->offsetExists($key)) {
			return $this->storage[$key];
		}

		return null;
	}
	public function __get ($key) { return $this->offsetGet($key); }

	public function offsetSet ($key, $value)
	{
		$this->storage[$key] = $value;
	}
	public function __set ($key, $value) { return $this->offsetSet($key, $value); }

	public function offsetUnset ($key)
	{
		if ($this->offsetExists($key)) {
			unset($this->storage[$key]);
		}
	}
	public function __unset ($key) { return $this->offsetUnset($key); }

	/* Returns how many key-value pairs this object holds. */
	public function count ()
	{
		return count($this->storage);
	}

	/* Returns whether this object holds any data. */
	public function is_empty ()
	{
		return empty($this->storage);
	}

	/* Takes a function, iterates over all keys, calling the given function on
	 * each item. The function may return false or true to indicate whether to
	 * include the given key in the result. Returns a \Sauce\Vector object.
	 *
	 * If no function is given, all values are returned.
	 */
	public function keys ($fn = null)
	{
		if ($fn === null) {
			$fn = function ($key) { return true; };
		}

		$keys = V([]);

		foreach($this->storage as $key => $value) {
			if ($fn($key)) {
				$keys->push($key);
			}
		}

		return $keys;
	}

	/* Takes a function, iterates over all values, calling the given function on
	 * each item. The function may return false or true to indicate whether to
	 * include the given value in the result. Returns a \Sauce\Vector object.
	 *
	 * If no function is given, all values are returned.
	 */
	public function values ($fn = null)
	{
		if ($fn === null) {
			$fn = function ($key, $value) { return true; };
		}

		$values = V([]);

		foreach($this->storage as $key => $value) {
			if ($fn($key, $value)) {
				$values->push($value);
			}
		}

		return $values;
	}

	/* Takes a function, iterates over all items, calling the given function on
	 * each item. The individual results are collected and returned in a
	 * \Sauce\Vector object.
	 *
	 * If no function is given, it returns all values in a \Sauce\Vector object.
	 */
	public function collect ($fn = null)
	{
		if ($fn === null) {
			$fn = function ($key, $value) { return $value; };
		}

		$values = V([]);

		foreach($this->storage as $key => $value) {
			$values->push($fn($value));
		}

		return $values;
	}

	/**
	 * TODO: document parameters
	 */
	public function merge ()
	{
		$args = func_get_args();
		$a    = new Object();

		// TODO: fix for actual Object

		foreach ($args as $arg) {
			if (is_an_array($arg)) {
				if (is_a($arg, '\Sauce\Object')) {
					$arg = $arg->to_array();
				}

				foreach ($arg as $key => $value) {
					$key = strtolower($key);

					if (!$a->offsetExists($key)) {
						$a->offsetSet($key, $value);
					}
				}
			} else {
				$a->storage []= $arg;
			}
		}

		return $a;
	}

	/**
	 * TODO: document parameters
	 */
	public function mergeF ()
	{
		$args = func_get_args();

		foreach ($args as $arg) {
			if (is_an_array($arg)) {
				if (is_a($arg, '\Sauce\Object')) {
					$arg = $arg->to_array();
				}

				foreach ($arg as $key => $value) {
					$this->offsetSet(strtolower($key), $value);
				}
			} else {
				$this->storage []= $arg;
			}
		}

		return $this;
	}
	
	public function getArrayCopy ()
	{
		return $this->storage;
	}

	public function jsonSerialize ()
	{
		return $this->storage;
	}
}

?>
