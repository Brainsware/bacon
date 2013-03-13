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

class AwareObject extends Object
{
	protected $changed_properties;

	public function __construct ($data = [], $recursive = false)
	{
		$this->changed_properties = new Vector();

		parent::__construct($data, $recursive);
	}

	public function offsetSet ($key, $value)
	{
		$this->changed_properties->push($key);

		return parent::offsetSet($key, $value);
	}

	public function offsetUnset ($key)
	{
		$this->changed_properties->push($key);

		return parent::offsetUnset($key);
	}

	/**
	 * TODO: document what this method does
	 */
	public function changed ()
	{
		return new Vector($this->changed_properties);
	}
}

?>
