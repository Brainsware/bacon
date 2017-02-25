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

class NotFoundHtml extends \Bacon\Presenter\Html
{
	public function __construct ($data, $context)
	{
		parent::__construct($data, $context);
	}

	public function render ($route, $log = null)
	{
		$views_path = \Sauce\Path::join(APP_ROOT, 'vendor/brainsware/bacon/Views')->__toString();
		$not_found_view_path = 'NotFound/index.tpl';

		$loader = new \Bacon\Loader($views_path, $not_found_view_path, false);
		$twig = new \Twig_Environment($loader);

		echo $twig->render($not_found_view_path, $this->context->getArrayCopy());
	}
}

?>
