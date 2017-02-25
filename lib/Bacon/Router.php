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

namespace Bacon;

class Router
{
	public function __construct ($uri, $http_method, $params)
	{
		$this->uri         = $uri;
		$this->http_method = $http_method;
		$this->params      = $params;

		if (!$this->params) {
			$this->params = new \Sauce\Object();
		}
	}

	public function parse ()
	{
		$splitted_uri = split_uri($this->uri);
		$count        = count($splitted_uri);
		$this->route  = new \Sauce\Vector();
		$this->action = '';

		$this->type = ($count > 0) ? $this->deduct_type($splitted_uri[$count - 1]) : null;

		if (empty($this->type) || $this->type === 'php') {
			$this->type = 'html';
		}

		if ($count >= 1) {
			// Remove type from URI
			$splitted_uri[$count - 1] = str_ireplace('.' . $this->type, '', $splitted_uri[$count - 1]);
		}

		for ($i = 0; $i < $count; $i++) {
			$current = $splitted_uri[$i];
			$next    = $i + 1 < $count ? $splitted_uri[$i + 1] : '';

			$namespace = '\\Controllers\\'
				. (empty($this->route->to_array()) ? '' : implode('\\', $this->route->to_array()) . '\\')
				. ucfirst($current);

			$namespace_next = $namespace . '\\' . ucfirst($next);

			$endpoint_check = false;
			if (class_exists($namespace, false)) {
				$endpoint_check = class_implements($namespace);
			}

			if ($endpoint_check != false && in_array('Bacon\Controllers\Endpoint', $endpoint_check)) {
				$this->route->push(ucfirst($current));
				$this->set_action();

				if (!empty($next)) {
					$this->params->id = $next;
				}

				return;
			}

			if (class_exists($namespace_next)) {
				// Current part is a namespace
				$this->route->push(ucfirst($current));

			} elseif (class_exists($namespace)) {
				// Current part is a resource
				$this->route->push(ucfirst($current));

				if (empty($next)) {
					// No next part -> call current#index or #create
					$this->set_action();

				} elseif ($next == 'new') {
					// Next part is new -> call current#new
					$this->action = 'new';

					return;

				} else {
					// Next part is a resource id

					// Check whether second next part is the last part and 'edit'
					// for URIs like /namespace/resource/:id/edit
					if ($i + 3 == $count && $splitted_uri[$i + 2] === 'edit' && $this->http_method == 'get') {
						$this->params->id = $next;
						$this->action     = 'edit';

						return;

					} elseif ($i + 3 == $count) {
						// Second next part is the last one, so store id in
						// params as resource_id => id, find HTTP method and
						// call resource as given in last part.
						$this->params[$current . '_id'] = $next;

						$this->route->push(ucfirst($splitted_uri[$i + 2]));

						$this->set_action();

						return;

					} elseif ($i + 3 < $count) {
						// Second next part is not the last one, so store id in
						// params as resource_id => id and continue iteration
						// at second next index.
						$this->params[$current . '_id'] = $next;

						$i++;
						continue;
					}

					$this->params->id = $next;

					$this->set_action(true);

					return;
				}
			} else {
				throw new Exceptions\RouterException('No route given.');
			}
		}
	}

	private function set_action ($show = false)
	{
		switch ($this->http_method) {
			case 'get':    $this->action = $show ? 'show' : 'index';  break;
			case 'put':    $this->action = 'update';  break;
			case 'post':   $this->action = 'create'; break;
			case 'delete':
			case 'destroy': $this->action = 'destroy'; break;
		}
	}

	public function clear ()
	{
		$this->route = new \Sauce\Vector();
	}

	private function deduct_type ($uri)
	{
		// With URIs like /foo/bar.csv or boo.json, interpret the extension as request type
		if (strrpos($uri, '.')) {
			return strtolower(substr($uri, strrpos($uri, '.') + 1));
		}
	}
}

?>
