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

namespace Sauce;

class Vector implements \ArrayAccess, \Countable, \JsonSerializable
{
	protected $storage;

	public function __construct ($data = [])
	{
		$this->storage = [];

		if (is_a($data, 'Sauce\Vector')) {
			$data = $data->to_array();
		}

		foreach ($data as $value) {
			$this->storage []= $value;
		}
	}

	public function to_array ()
	{
		return $this->storage;
	}
	public function getArrayCopy() { return $this->to_array(); }

	public function slice ($start, $end)
	{
		return array_slice($this->storage, $start, ($end - $start));
	}

	public function join ($delimiter)
	{
		$strings = $this->map(function ($v) {
			return strval($v);
		});

		return join($delimiter, $strings->to_array());
	}

	public function map ($callback)
	{
		$result = new self();

		for ($i = 0; $i < $this->count(); $i++) {
			$value = $callback($this->storage[$i]);

			if ($value) {
				$result->push($value);
			}
		}

		return $result;
	}

	public function select ($callback)
	{
		return $this->map(function ($v) use ($callback) {
			if ($callback($v)) {
				return $v;
			}
		});
	}

	public function exclude ($callback)
	{
		return $this->map(function ($v) use ($callback) {
			if (!$callback($v)) {
				return $v;
			}
		});
	}

	/* Takes any type of data and pushes it onto the vector. Given an array,
	 * (PHP built-in or Vector), it will push its contents. Any object or
	 * Object instance is pushed as-is.
	 */
	public function push ($value)
	{
		if (is_an_array($value) && !is_a($value, '\Sauce\Object')) {
			foreach ($value as $v) {
				$this->push($v);
			}

			return;
		}

		$this->storage []= $value;
	}

	public function pop ()
	{
		return array_pop($this->storage);
	}

	public function shift ()
	{
		return array_shift($this->storage);
	}

	/**
	 * TODO: document what this does and how to use it.
	 */
	public function includes ($value)
	{
		for ($i = 0; $i < $this->count(); $i++) {
			if ($value === $this->storage[$i]) {
				return true;
			}
		}

		return false;
	}

	public function offsetGet ($index)
	{
		if (!is_numeric($index)) {
			throw new \OutOfBoundsException('You are trying to access a non-numeric index.');
		}
		
		if ($index >= $this->count()) {
			throw new \OutOfBoundsException("Invalid index {$index}");
		}

		return $this->storage[$index];
	}

	public function offsetSet ($index, $value)
	{
		if (!is_numeric($index)) {
			throw new \OutOfBoundsException('Out of bounds: you are trying to set a non-numeric index.');
		}

		if ($index > $this->count()) {
			throw new \OutOfBoundsException("Invalid index {$index}");
		}

		$this->storage[$index] = $value;
	}

	public function offsetUnset ($index)
	{
		if ($index == 0) {
			return array_shift($this->storage);
		}

		if ($index == $this->count() - 1) {
			return array_pop($this->storage);
		}

		$left = array_slice($this->storage, 0, $index);

		for ($i = $index; $i++; ($i + 1) < $this->count()) {
			$left []= $this->storage[$i + 1];
		}

		$this->storage = $left;
	}

	public function offsetExists ($index)
	{
		return is_numeric($index) && $index < $this->count();
	}

	public function count ()
	{
		return count($this->storage);
	}

	public function is_empty ()
	{
		return empty($this->storage);
	}

	public function jsonSerialize ()
	{
		return $this->storage;
	}
}

?>
