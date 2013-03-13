<?php

/* Hash
 */
namespace Bacon;

/*
 */
class Hash implements \ArrayAccess, \Countable
{
	/* Internal storage */
	protected $storage = array();

	/* Allow sub-classes to access original storage
	 * 
	 * Example:
	 * 
	 *  	class MyHash
	 *  	{
	 *  		protected function __get ($key)
	 *  		{
	 *  			$storage = &$this->getStorageReference();
	 *  			
	 *  			if (array_key_exists($key, $storage)) {
	 *  				return $storage[$key];
	 *  			}
	 * 
	 *  			return false;
	 *  		}
	 *  	}
	 */
	protected function &getStorageReference ()
	{
		return $this->storage;
	}

	/* Construct a new Hash object.
	 * 
	 * `Arrays`, `ArrayObjects` and `Hash` objects will be cloned,
	 * all other types will be put into the storage as first element.
	 */
	public function __construct($input = array())
	{
		if (empty($input)) {
			$this->storage = array();
		} elseif (is_array($input)) {
			$this->storage = $input;
		} elseif (is_a($input, 'ArrayObject')) {
			$this->storage = $input->getArrayCopy();
		} elseif (is_a($input, 'Hash')) {
			$this->storage = $input->clone();
		} else {
			$this->storage = array($input);
		}
	}

	/* Return value for given key */
	public function __get ($key)
	{
		if ($this->includes($key)) {
			return $this->storage[$key];
		}

		return false;
	}

	/* Alias for `__get` and implementation for `ArrayAccess` interface */
	public function offsetGet($key)
	{
		return __get($key);
	}

	/* Store given key value pair */
	public function __set ($key, $value)
	{
		return $this->storage[$key] = $value;
	}

	/* Alias for `__set` and implementation for `ArrayAccess` interface */
	public function offsetSet($key, $value)
	{
		return $this->__set($key, $value);
	}

	/* Remove given key from array */
	public function __unset($key)
	{
		if ($this->has_key($key)) {
			unset($this->storage[$key]);
		}
	}

	/* Alias for `__unset` and implementation for `ArrayAccess` interface */
	public function offsetUnset($key)
	{
		$this->__unset($key);
	}

	/* Return whether given key is present */
	public function __isset ($key)
	{
		return array_key_exists($key, $this->storage);
	}

	/* Alias for `__isset` and implementation for `ArrayAccess` interface */
	public function offsetExists($key)
	{
		return $this->__isset($key);
	}

	/* Returns the amount of stored elements */
	public function count ()
	{
		return count($this->storage);
	}

	/* Invoke callback on every element of the array, building a new
	 * array from the results of that callback */
	public function map ($options = array())
	{
		$options = self::convert($options);

		$callback = self::get_callback($options);

		$result = new Hash();

		foreach ($this->storage as $key => $value) {
			$result->append(call_user_func($callback, $key, $value));
		}

		return $result;
	}

	/* Add given value to the end of the array and return `$this`.
	 *
	 * Note: `append` alters the current object.
	 **/
	public function append ($value)
	{
		$this->storage []= $value;

		return $this;
	}

	/* Build a new `Hash` object by iteratively excluding elements
	 * based on a given callback, one or multiple keys.
	 */
	public function exclude ($options)
	{
		$options = self::convert($options);

		$callback = self::get_callback($options);

		return $this->walk_unless($callback, $options);
	}

	/* Returns true if given value is present */
	public function includes ($value)
	{
		return in_array($value, $this->storage);
	}

	/* Alias for `includes` */
	public function has_value ($value)
	{
		return $this->includes($value);
	}

	/* Return all values as Hash object */
	public function values ()
	{
		return new Hash(array_values($this->storage));
	}

	/* Return all keys as Hash object */
	public function keys ()
	{
		return new Hash(array_keys($this->storage));
	}

	/* Returns true if given key is present */
	public function has_key ($key)
	{
		return $this->__isset($key);
	}

	/* Determine whether the given `options` hold a callable element,
	 * otherwise supply a default callback.
	 * 
	 * Valid callable elements are:
	 * * callback function
	 * * Hash object with a callback function in key 'callback'
	 */
	protected function get_callback(&$options)
	{
		if (is_callable($options)) {
			return $options;
		} elseif (is_a($options, 'Hash') &&
			      $options->has_key('callback') &&
			      is_callable($options['callback'])) {
			return $options['callback'];
		}

		return function($key, $value) use ($options) {
			if ($options->includes($key)) {
				return true;
			}

			return false;
		};
	}

	/* Convert options to a `Hash` or return it unchanged.
	 *
	 * If `throw_on_wrong_type = true` is passed, types other than
	 * `string` and `BaconObject` will raise an Exception.
	 */
	protected function convert($options, $throw_on_wrong_type = false)
	{
		if (is_string($options)) {
			$options = new Hash(array($options));
		} elseif (is_array($options)) {
			$options = new Hash($options);
		} elseif (!is_a($options, 'Hash') && $throw_on_wrong_type) {
			$type = get_class($options);

			throw new InvalidArgumentException("Invalid argument type: {$type}");
		}

		return $options;
	}

	protected function walk_if ($callback, $args = array())
	{
		$result = new Hash();

		foreach ($this->storage as $key => $value) {
			if (call_user_func($callback, $key, $value, $args)) {
				$result[$key] = $value;
			}
		}

		return $result;
	}

	protected function walk_unless ($callback, $args = array())
	{
		$result = new Hash();

		foreach ($this->storage as $key => $value) {
			if (call_user_func($callback, $key, $value, $args) == false) {
				$result[$key] = $value;
			}
		}

		return $result;
	}
}

?>
