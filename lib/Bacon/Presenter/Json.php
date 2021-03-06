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

class Json extends \Bacon\Presenter
{
	public function __construct ($data, $context)
	{
		parent::__construct($data, $context);
	}

	public function render ($route, $log = null)
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-Type: text/plain');

		if ($this->data instanceof \ArrayObject) {
			$this->data = $this->data->getArrayCopy();
		} elseif (empty($this->data)) {
			$this->data = [];
		}

		echo json_encode($this->data);
		exit;
	}
}

?>
