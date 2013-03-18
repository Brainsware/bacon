<?php

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

			$path = \Sauce\Path::join(APP_ROOT, 'Controllers', $this->route, $current);

			if (is_dir($path) && is_file(\Sauce\Path::join($path, $next . '.php'))) {
				// Current part is a namespace
				$this->route->push($current);

			} elseif (is_file($path . '.php')) {
				// Current part is a resource
				$this->route->push($current);

				if (empty($next)) {
					// No next part -> call current#index or #create
					switch ($this->http_method) {
						case 'get':  $this->action = 'index';   break;
						case 'post': $this->action = 'create';  break;
					}

				} elseif ($next == 'new') {
					// Next part is new -> call current#new
					$this->action = 'new';

					return;

				} else {
					// Next part is a resource id

					// Check whether second next part is the last part and 'edit'
					// for URIs like /namespace/resource/:id/edit
					if ($i + 3 == $count && $splitted_uri[$i + 2] == 'edit' && $this->http_method == 'get') {
						$this->params->id = $next;
						$this->action     = 'edit';

						return;

					} elseif ($i + 3 == $count) {
						// Second next part is the last one, so store id in
						// params as resource_id => id, find HTTP method and
						// call resource as given in last part.
						$this->params[$current . '_id'] = $next;

						$this->route->push($splitted_uri[$i + 2]);

						switch($this->http_method) {
							case 'get':  $this->action = 'index';  break;
							case 'post': $this->action = 'create'; break;
						}

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

					switch ($this->http_method) {
						case 'get':    $this->action = 'show';    break;
						case 'put':    $this->action = 'update';  break;
						case 'delete': $this->action = 'destroy'; break;
					}

					return;
				}
			} else {
				throw new Exceptions\RouterException('No route given.');
			}
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
