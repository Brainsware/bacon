<?php

/**
 * @package System
 */

namespace Bacon;

/**
 * Bacon request handler
 *
 * @package System
 */
class Request extends Immutable
{
	public $base_uri;

	private $base_controller;
	private $session;
	private $_request;

	public function __construct ($session, $config, $log)
	{
		if (!($config instanceof \Sauce\Object)) {
			$config = Ar($config);
		}

		$this->session         = $session;
		$this->base_uri        = $config->base_uri;
		$this->base_controller = $config->base_controller;
		$this->paths           = $config->paths;
		$this->log             = $log;

		$this->_request = Ar($_REQUEST);
		$this->_request->merge($_SERVER);
	}

	public function __get ($key)
	{
		return $this->_request[$key];
	}

	public function offsetGet ($key)
	{
		return $this->_request[$key];
	}

	public function offsetExists ($key)
	{
		return $this->_request->offsetExists($key);
	}

	public function call()
	{
		if (empty($this->route)) {
			array_push($this->route, $this->base_controller);

			$this->action = 'index';
		}

		$controller_name = join('\\', $this->route);

		$this->controller = new $controller_name($this->session, $this->log, $this->paths);

		if ($this->request_type) {
			$this->controller->request_type = $this->request_type;
		}

		$this->controller->call($this);
	}

	public function parse ()
	{
		$path_info = self::path_info();

		// In some cases path info does include the GET parameters
		// (passed in the URI), so we need to remove those.
		$question_mark = strpos($path_info, '?');

		if ($question_mark !== false) {
			$path_info = substr($path_info, 0, $question_mark);
		}

		$splitted_uri = $this->split_uri($path_info);

		$count = count($splitted_uri);

		$this->route = array();
		$this->_params = array();
		$this->action = '';
		$this->request_type = 'html';

		if ($count >= 1) {
			// URIs like /foo/bar.csv or boo.json will interpret the extension as request type
			if (strrpos($splitted_uri[$count - 1], '.')) {
				$this->request_type = substr($splitted_uri[$count - 1], strrpos($splitted_uri[$count - 1], '.') + 1);

				$splitted_uri[$count - 1] = substr($splitted_uri[$count - 1], 0, strrpos($splitted_uri[$count - 1], '.'));
			}
		}

		for ($i = 0; $i + 1 <= $count; $i++) {
			$current_part = $splitted_uri[$i];
			$next_part    = $i + 1 < $count ? $splitted_uri[$i + 1] : '';

			$path = APP_ROOT . 'controllers/' . join('/', $this->route) . '/' . $current_part;

			if (is_dir($path) && is_file($path . '/' . $next_part . '.php')) {
				// Current part is a namespace

				array_push($this->route, $current_part);

			} elseif (is_file($path . '.php')) {
				// Current part is a resource

				array_push($this->route, $current_part);

				if (empty($next_part)) {
					// No next part -> call current#index or #create

					switch (self::http_method()) {
						case 'get':    $this->action = 'index';   break;
						case 'post':   $this->action = 'create';  break;
					}

				} elseif ($next_part == 'new') {
					// Next part is new -> call current#new

					$this->action = 'new';

					return;

				} else {
					// Next part is a resource id

					// Check whether second next part is the last part and 'edit'
					// for URIs like /namespace/resource/:id/edit
					if ($i + 3 == $count && $splitted_uri[$i + 2] == 'edit' && self::http_method() == 'get') {
						$this->_params['id'] = $next_part;
						$this->action = 'edit';

						return;

					} elseif ($i + 3 == $count) {
						// Second next part is the last one, so store id in
						// params as resource_id => id, find HTTP method and
						// call resource as given in last part.
						$this->_params[$current_part . '_id'] = $next_part;

						array_push($this->route, $splitted_uri[$i + 2]);

						switch(self::http_method()) {
							case 'get':  $this->action = 'index';  break;
							case 'post': $this->action = 'create'; break;
						}

						return;

					} elseif ($i + 3 < $count) {
						// Second next part is not the last one, so store id in
						// params as resource_id => id and continue iteration
						// at second next index.
						$this->_params[$current_part . '_id'] = $next_part;

						$i++;
						continue;

					} else {
						$this->_params['id'] = $next_part;

						switch (self::http_method()) {
							case 'get':    $this->action = 'show';    break;
							case 'put':    $this->action = 'update';  break;
							case 'delete': $this->action = 'destroy'; break;
						}

						return;
					}
				}
			}
		}
	}

	public static function path_info () {
		if (array_key_exists('PATH_INFO', $_SERVER)) {
			$path_info = $_SERVER['PATH_INFO'];
		} else {
			$path_info = '';
		}

		if (empty($path_info)) {
			$path_info = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
			$path_info = str_replace($path_info, '', $_SERVER['REQUEST_URI']);
		}

		return $path_info;
	}

	public static function http_method () {
		$method = $_SERVER['REQUEST_METHOD'];

		if (array_key_exists('_method', $_REQUEST)) {
			$method = $_REQUEST['_method'];
		}

		return strtolower($method);
	}

	private function split_uri ($uri) {
		$splitted_uri = explode('/', $uri);

		# Remove empty strings from beginning and end
		if (count($splitted_uri) > 1) {
			$last = end($splitted_uri);
			$first = reset($splitted_uri);

			if (empty($last)) {
				array_pop($splitted_uri);
			}

			if (empty($first)) {
				array_shift($splitted_uri);
			}
		}

		return $splitted_uri;
	}

	/**
	 * Redirects to another module with an HTTP redirect
	 *
	 * @param array $params Module parameters
	 *
	 * @return bool False on redirect failure (module not found)
	 */
	public function redirect ($to)
	{
		if (is_array($to)) {
			$to = join('/', $to);
		}

		$uri = $this->base_uri;

		if ('http://'  != substr($to, 0, 7) &&
			'https://' != substr($to, 0, 8)) {
			if ($to[0] != '/' && $uri[strlen($uri) - 1] != '/') {
				$to = '/' . $to;
			}

			$to = join('', array($this->base_uri, $to));
		}

		header('Location: ' . $to);
		exit;
	}
}

?>
