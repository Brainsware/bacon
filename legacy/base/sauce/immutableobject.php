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

/**
 * TODO: Document what this class does.
 */
class ImmutableObject extends Object 
{
	public function offsetSet   ($key, $value) { $this->deny_access(); }
	public function offsetUnset ($key)         { $this->deny_access(); }
	public function __set       ($key, $value) { $this->deny_access(); }
	public function mergeF      ()             { $this->deny_access(); }

	protected function deny_access ()
	{
		throw new \LogicException('This object is immutable.');
	}
}

?>
