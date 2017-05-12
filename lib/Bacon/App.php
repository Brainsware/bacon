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
	public $authcookie;
	public $controller;

	protected $params;
	protected $config;
	protected $router;
	protected $environment;

	private $controller_name;

	public function __construct($config, $session, $log, $params, $environment, $authcookie)
	{
		$this->config = $config;
		$this->params = $params;
		$this->environment = $environment;

		$this->log        = $log;
		$this->session    = $session;
		$this->authcookie = $authcookie;

		if (empty($this->config->root_controller)) {
			throw new Exceptions\RouterException('No root controller defined in Config\Base');
		}

		// Remove trailing Controllers\ namespace if present in configured root_controller
		if (false !== strpos($this->config->root_controller, 'Controllers\\')) {
			$this->config->root_controller = str_replace('Controllers\\', '', $this->config->root_controller);
		}

		// Set base_uri to be '/' if it is not configured
		if (empty($this->config->base_uri)) {
			$this->config->base_uri = '/';

		} else {
			// Trim whitespaces
			$this->config->base_uri = trim($this->config->base_uri);

			// Trim trailing slashes (as long as it's not only a slash)
			if (strlen($this->config->base_uri) > 1) {
				$this->config->base_uri = rtrim($this->config->base_uri, '/');
			}
		}
	}

	/* Parse route/params to find out which controller/action to call - falls
	 * back to (configured) base controller. */
	public function prepare ($uri, $method)
	{
		try {
			$this->router = new Router($uri, $method, $this->params);

			if ($uri === $this->config->base_uri) {
				return $this->use_root_controller();
			}

			$this->router->parse();

			if ($this->router->route->is_empty()) {
				return $this->use_root_controller();
			} else {
				$this->controller_name = 'Controllers\\' . $this->router->route->join('\\');
			}

		} catch (Exceptions\RouterException $e) {
			$this->log->debug($e->getMessage());
			
			if (!empty($this->config->spa)) {
				# Route all not found controllers to root, if single page application
				return $this->use_root_controller();
			}

			$this->use_not_found_controller();
		}
	}

	protected function use_root_controller ()
	{
		$this->router->clear();

		$this->router->route->push($this->config->root_controller);
		$this->router->action = 'index';
		$this->router->type = 'html';

		$this->controller_name = 'Controllers\\' . $this->config->root_controller;
	}

	protected function use_not_found_controller ()
	{
		$this->router->clear();

		$this->controller_name = 'Controllers\\NotFound';

		// Check whether a 404 controller is available in the app, if not use Bacon's
		if (!class_exists($this->controller_name)) {
			$this->controller_name = 'Bacon\\' . $this->controller_name;
		}

		$this->router->route->push('NotFound');
		$this->router->action = 'index';
		$this->router->type = 'html';

		/* Take the original URI and make it available as space-delimited string
		 * so it can be put into a search box or similar */
		$this->router->params->not_found = str_replace([ '/', '-' ], ' ', $this->environment->request_uri);
	}

	/* Run determined route and process the results; either create an HTML
	 * presenter and render or just render the returned presenter instance. */
	public function run ()
	{
		$name = $this->controller_name;

		$this->controller = new $name($this->session, $this->log, $this->authcookie);

		$options = A([
			'action'      => $this->router->action,
			'type'        => $this->router->type,
			'base_uri'    => $this->config->base_uri,
			'params'      => $this->router->params,
			'environment' => $this->environment
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
