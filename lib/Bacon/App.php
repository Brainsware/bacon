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

/**
 * ## Bacon\App
 *
 * App is the main entry point for Bacon. It takes configuration, params,
 * a session and a log instance. With all these in place, it calls the router
 * to identify what controller/action to call. If no matching route was found,
 * the configured default controller's index action is called.
 *
 * A controller should return any instance of \Bacon\Presenter, unless it just
 * renders HTML. For convenience App will take care of creating the HTML
 * presenter. The presenter then renders the result.
 */
class App
{
	public $log;
	public $session;
	public $controller;

	protected $params;
	protected $config;
	protected $router;
	protected $environment;

	private $controller_name;

	public function __construct($config, $session, $log, $params)
	{
		$this->config = $config;
		$this->params = $params;

		$this->log     = $log;
		$this->session = $session;
	}

	/* Parse route/params to find out which controller/action to call - falls
	 * back to (configured) base controller. */
	public function prepare ($uri, $method)
	{
		try {
			$this->router = new Router($uri, $method, $this->params);
			$this->router->parse();

			if ($this->router->route->is_empty()) {
				$this->use_default_route();
			} else {
				$this->controller_name = 'Controllers\\' . $this->router->route->join('\\');
			}

		} catch (Exceptions\RouterException $e) {
			$this->log->error($e);

			$this->use_default_route();
		}
	}

	public function use_default_route ()
	{
		$this->router->clear();
		$this->router->route->push((false === strpos($this->config->fallback, 'Controllers\\')) ? $this->config->fallback : str_replace('Controllers\\', '', $this->config->fallback));
		$this->router->action = 'index';

		$this->controller_name = (false !== strpos($this->config->fallback, 'Controllers\\')) ? $this->config->fallback : 'Controllers\\' . $this->config->fallback;
	}

	/* Run determined route and process the results; either create an HTML
	 * presenter and render or just render the returned presenter instance. */
	public function run ()
	{
		$name = $this->controller_name;

		$this->controller = new $name($this->session, $this->log);

		$options = A([
			'action'   => $this->router->action,
			'type'     => $this->router->type,
			'base_uri' => $this->config->base_uri,
			'params'   => $this->router->params
		]);

		$result = $this->controller->call($options);

		$this->render($result);
	}

	/* Result might be one of the following options:
	 * 1) nothing; continue rendering the default (Twig + context or nothing)
	 * 2) an array; feed default presenter (depending on type) with array (and context if type is html)
	 * 3) a presenter - return the presenter and let App render with it
	 */
	private function render ($result)
	{
		$presenter = null;

		try {
			if (is_subclass_of($result->data, '\Bacon\Presenter', true)) {
				$presenter = $result->data;
			} else {
				switch ($this->router->type) {
					default:
					case 'html':
						$presenter = new \Bacon\Presenter\Html($result->data, $result->context);
						break;
				}
			}

			if (!$presenter) {
				// Render 404! Obviously someting has not been found.
				$presenter = new \Bacon\Presenter\Http(404, $this->params);
			}

			$presenter->render($this->router->route, $this->log);

		} catch (Exception $e) {
			$this->log->info(var_export($this->params, true));

			throw $e;
		}
	}
}

?>
