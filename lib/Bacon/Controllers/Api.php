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

namespace Bacon\Controllers;

class Api extends \Bacon\Controller
{
	use \Bacon\Traits\Pagination;

	protected $model = '';
	protected $per_page = 100;

	protected $allowed_fields  = [];
	protected $readable_fields = [];

	protected $search_fields   = [];

	protected $sortable        = null;
	protected $belongs_to      = null;
	
	public function init ()
	{
		/* Check whether given request is from within /backend.
		 * Deny (send 404) for all non-GET requests outside of /backend. */
		if ($this->environment->request_method !== 'GET' &&
			!$this->check_key()) {

			return $this->http_status(403);
		}

		if (!empty($this->belongs_to)) {
			try {
				$model = $this->belongs_to['model'];
				$this->parent_model = $model::find($this->params[$this->belongs_to['param']]);

			} catch (\PDOException $e) {
				return $this->http_status(404);
			}
		}

		return parent::init();
	}

	protected function check_key ()
	{
		return true;
	}

	protected function referer_uri ()
	{
		$referer = S($this->environment->http_referer);

		$referer->replaceF('http://', '');
		$referer->replaceF('https://', '');
		$referer->replaceF($this->environment->http_host, '');

		return $referer;
	}

	public function index ()
	{
		$options = A([
			'order_by' => (empty($this->sortable)) ? 'id' : $this->sortable,
			'order'    => 'desc',
			'per_page' => $this->per_page,
			'page'     => 0
		])->mergeF($this->params);

		$this->paginate($options->per_page);

		$where = [];

		if (!empty($this->readable_fields)) {
			foreach ($this->readable_fields as $field) {
				if (!empty($this->params[$field])) {
					$where[$field] = $this->params[$field];
				}
			}
		}

		if (!empty($this->belongs_to)) {
			$where[$this->belongs_to['key']] = $this->parent_model->id;
		}

		$model = $this->model;
		$model = $model::where($where);

		if (!empty($this->join)) {
			$model = $model->join($join);
		}

		if (!empty($this->readable_fields)) {
			$model = $model->columns($this->readable_fields);
		}

		return $this->json(
				$model
			    	->order($options->order_by, $options->order)
					->page($options->page, $options->per_page)->all()
			);
	}

	public function show ()
	{
		$model = $this->model;

		try {
			if (!empty($this->readable_fields)) {
				$data = $model::columns($this->readable_fields)->where(['id' => $this->params->id])->first();
			} else {
				$data = $model->find($this->params->id);
			}

			return $this->json($data);

		} catch (\PDOException $e) {
			$this->log->warning($e->getMessage());

			return $this->http_status(400);
		}
	}

	public function create ()
	{
		$model = $this->model;

		try {
			$data = new $model();

			foreach ($this->allowed_fields as $field) {
				if ($this->params->has_key($field)) {
					$data->$field = $this->params[$field];
				}
			}

			if (!empty($this->belongs_to)) {
				$key = $this->belongs_to['key'];
				$data->$key = $this->parent_model->id;
			}

			$data->save();

			$data = $model::find($data->id);
		} catch (\PDOException $e) {
			$this->log->warning($e->getMessage());

			return $this->http_status(400);
		}

		return $this->json($data);
	}

	public function update ()
	{
		$model = $this->model;

		try {
			$this->data = $model::find($this->params->id);
		} catch (\PDOException $e) {
			$this->log->warning($e->getMessage());

			return $this->http_status(400);
		}

		foreach ($this->allowed_fields as $field) {
			if ($this->params->has_key($field)) {
				if (!empty($this->sortable) && $field == $this->sortable) {
					$this->data->move($this->params[$field]);
				} else {
					$this->data->$field = $this->params[$field];
				}
			}
		}

		try {
			$this->data->save();
		} catch (\PDOException $e) {
			$this->log->warning($e->getMessage());

			return $this->http_status(400);
		}

		return $this->json($this->data);
	}

	public function destroy ()
	{
		$model = $this->model;

		try {
			$this->data = $model::find($this->params->id);

			$this->data->delete();
		} catch (\PDOException $e) {
			$this->log->warning($e->getMessage());

			return $this->http_status(400);
		}

		return $this->json([]);
	}
}

?>
