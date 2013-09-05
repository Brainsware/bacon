<?php

/**
   Copyright 2012-2013 Brainsware

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

namespace Bacon\Presenter;

class Atom extends \Bacon\Presenter
{
	public function __construct ($data, $context)
	{
		/*if ($context->layout) {
			$context->layout .= '.atom';
		} else {
		    $context->layout =  $context->action . '.atom';
		}*/

		if (!$context->template) {
			$context->template = $context->action;
		}

		parent::__construct($data, $context);
	}

	public function render ($route, $log = null)
	{
		$template_path = $route->join('/') . '/' . $this->context->template . '.atom';

		$cache = false;

		if (isset(\Config\Base::$caching) && \Config\Base::$caching == true) {

			$cache = (\Config\Base::$caching['twig'] ? APP_ROOT . '/cache' : false);
		}
		$cache = false;

		$loader = new \Bacon\Loader(APP_ROOT . '/Views', $template_path, false);
		$twig = new \Twig_Environment($loader, ['cache' => $cache, 'auto_reload' => true]);

		if (!empty($this->context->filters)) {
			foreach ($this->context->filters->getArrayCopy() as $name => $function) {
				$filter_function = new \Twig_Filter_Function($function, ['is_safe' => ['html']]);
				$twig->addFilter($name, $filter_function);
			}
		}

		echo $twig->render($template_path, $this->context->getArrayCopy());
	}
}

?>
