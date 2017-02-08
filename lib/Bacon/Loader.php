<?php

/**
   Copyright Brainsware

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

namespace Bacon;

/**
 * Template loader for Twig so the layout is automatically extended by the
 * main template. This makes '{% extend layout %}' unnecessary.
 *
 * @package System
 */
class Loader extends \Twig_Loader_Filesystem
{
	protected $default_template;

	protected $base_template;

	public function __construct($paths, $default_template, $base_template = true)
	{
		parent::__construct($paths);

		$this->default_template = $default_template;

		$this->base_template = $base_template;
	}

	public function getSourceContext($name)
	{
		$source = file_get_contents($this->findTemplate($name));

		if ($name == $this->default_template && $this->base_template) {
			return new \Twig_Source('{% extends layout %}' . $source, $name);
		}

		return new \Twig_Source($source, $name);
	}
}

?>
