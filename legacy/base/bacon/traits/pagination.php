<?php

namespace Bacon\Traits;

trait Pagination
{
	protected function paginate ($per_page = 18, $page = 0)
	{
		$this->params->per_page = @or_equals($this->params->per_page, $per_page);
		$this->params->page     = @or_equals($this->params->page,     $page);
	}
}
