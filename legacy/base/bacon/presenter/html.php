<?php

namespace Bacon\Presenter;

class Html extends \Bacon\Presenter
{
	public function __construct ($data, $context)
	{
		if ($context->layout) {
			$context->layout .= '.tpl';
		} else {
		    $context->layout = 'layout.tpl';
		}

		if (!$context->template) {
			$context->template = $context->action;
		}

		parent::__construct($data, $context);
	}

	public function render ($route, $log = null)
	{
		$template_path = $route->join('/') . '/' . $this->context->template . '.tpl';

		$loader = new \Bacon\Loader(APP_ROOT . '/templates', $template_path);
		$twig = new \Twig_Environment($loader);

		if (!empty($this->context->filters)) {
			foreach ($this->context->filters->getArrayCopy() as $name => $function) {
				$filter_function = new \Twig_Filter_Function($function, array('is_safe' => array('html')));
				$twig->addFilter($name, $filter_function);
			}
		}

		try {
			echo $twig->render($template_path, $this->context->getArrayCopy());
		} catch (\Twig_Error_Loader $e) {
			echo $e->getMessage();
		}
	}
}

?>
