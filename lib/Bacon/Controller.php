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
 * ## Bacon\Controller
 *
 * The controller is the heart of Bacon as a web framework.
 *
 * Given a session and a log instance, params and some other data gathered
 * in htdocs/index.php and Bacon\App, it calls the appropriate action if
 * available.
 *
 * The callable actions are:
 * * #index   - /resource          (GET)
 * * #show    - /resource/:id      (GET)
 * * #new     - /resource/new      (GET)
 * * #create  - /resource/         (POST)
 * * #edit    - /resource/:id/edit (GET)
 * * #update  - /resource/:id      (PUT [POST]) (*)
 * * #destroy - /resource/:id      (DELETE [POST]) (*)
 *
 * (*) Since browsers only allow GET and POST requests, PUT and DELETE are
 * distinguished from a normal POST request by a parameter called "_method".
 * It may be embedded in a hidden form field or in the URL as GET parameter.
 *
 * The hierarchy in which controllers reside in your project is pretty simple:
 * 
 * controllers/application.php: Application extending \Bacon\Controller
 * controllers/resources.php:   Resources extending Application
 *
 * The Application controller is not a callable controller. It's supposed to be
 * used to define global filters and methods usable for all other controllers.
 *
 * To introduce a URI namespace, just introduce a PHP namespace:
 *
 * controllers/namespace/application.php: namespace Namespace; Application extends \Application (optional)
 * controllers/namespace/resources.php:   namespace Namespace; Resources extends Application
 *                                                             (the namespace Application controller)
 *
 * (TODO: more use cases)
 *
 * How a controller actually looks like:
 *
 * class Resources extends Application
 * {
 *   public function index ()
 *   {
 *     $this->text = 'Hello world!';
 *   }
 * }
 *
 * ...
 *
 * */
abstract class Controller
{
	public $session;
	public $log;

	private $context;

	public function __construct ($session, $log)
	{
		$this->session = $session;
		$this->log     = $log;

		$this->context = A([]);
	}

	// Override this method in your controllers for ultimate pleasure.
	public function init () { }

	public function call($options)
	{
		$this->action   = $options->action;
		$this->params   = $options->params;
		$this->type     = $options->type;
		$this->base_uri = $options->base_uri;

		if ($this->is_upload_controller()) {
			$this->handle_upload();
		}

		$this->init();

		$action_name = $options->action == 'new' ? '_new' : $options->action;

		try {
			$result = $this->$action_name();

			return A([
				'data'    => $result,
				'context' => new \Sauce\Object($this->context)
			]);

		} catch (\Exception $e) {
			error_log($e->getMessage());

			return A([ 'data' => $this->http_status(500), 'context' => A($this->context) ]);
		}
	}

	public function redirect ($to)
	{
		return new \Bacon\Presenter\Redirect($to, $this->params);
	}

	public function json ($data)
	{
		return new \Bacon\Presenter\Json($data, $this->params);
	}

	public function http_status ($code)
	{
		return new \Bacon\Presenter\Http($code, $this->params);
	}

	public function __set ($name, $value)
	{
		$this->context[$name] = $value;
	}

	public function __get ($name)
	{
		if (isset($this->context[$name])) {
			return $this->context[$name];
		}

		return false;
	}

	public function __isset ($name)
	{
		if (isset($this->context[$name])) {
			return true;
		}

		return false;
	}

	public function __unset ($name)
	{
		unset($this->context[$name]);
	}

	protected function render ($template)
	{
		$this->template = $template;
	}

	protected function add_filter($name, $function)
	{
		if (!isset($this->context->filters) || !is_a($this->context->filters, '\Sauce\Object')) {
			$this->context->filters = A($this->context->filters || []);
		}

		$this->context->filters->$name = $function;
	}

    protected function respond_to ($function)
    {
        $this->_respond_to = $function;
    }

    private function is_upload_controller()
    {
        $implemented_classes = class_implements(get_class($this));

        return array_key_exists('Bacon\UploadController', $implemented_classes);
    }

    private function handle_upload()
    {
        $this->uploads = V([]);
        $upload_field = $this->upload_field();

		if ($this->params->has_key('files')) {
			foreach ($this->params->files->getArrayCopy() as $file) {
				if (empty($file->tmp_name)) {
					continue;
				}

				// Handle old-school form upload
				if (!is_an_array($file->tmp_name)) {
					$this->uploads->push(new Upload\Form($this->log, $file->tmp_name, $file->size, $file->type, $file->name));

				} else {
					$i = count($file->tmp_name) - 1;

					for (; $i >= 0; $i--) {
						$this->uploads->push(new Upload\Form($this->log, $file->tmp_name[$i], $file->size, $file->type[$i], $file->name[$i]));
					}
				}
			}

        } elseif (isset($this->params->$upload_field)) {
            // Handle XHR upload
            $this->uploads->push(new Upload\XHR($this->log, 'php://input', $this->params->content_length, $this->params->content_type, $this->params->http_x_file_name));
        }
    }

    private function array_keys_to_lower(array $array = [])
    {
        $new_array = [];

        foreach ($array as $key => $value) {
            $new_array[strtolower($key)] = $value;
        }

        return $new_array;
    }
}

?>
