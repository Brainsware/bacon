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

namespace Bacon\ORM;

class Collection extends \ArrayObject
{
	protected $db;
	protected $model;
	protected $table_name;
	protected $primary_key;

	protected $select_list;
	protected $where_scope;
	protected $order_scope;
	protected $group_scope;
	protected $limit_scope;
	protected $offset_scope;
	protected $join_scope;

	protected $loaded = false;

	public function __construct($model)
	{
		$this->model       = $model;
		$this->table_name  = $model::table_name();
		$this->primary_key = $model::primary_key();
		$this->db          = \Bacon\ORM\DatabaseSingleton::get_instance();
	}

	public function offsetGet($offset): mixed
	{
		if (!$this->loaded) {
			$this->load();
		}

		if (parent::offsetExists($offset)) {
			return parent::offsetGet($offset);
		}

		return false;
	}

	public function getIterator(): \Iterator
	{
		if (!$this->loaded) {
			$this->load();
		}

		return parent::getIterator();
	}

	public function all()
	{
		if (!$this->loaded) {
			$this->load();
		}

		return $this;
	}

	// TODO: Make $throw configurable at start up
	public function first($throw = true)
	{
		$this->limit(1);

		$result = $this->offsetGet(0);

		if ($result) {
			return $result;
		} elseif ($throw) {
			throw new \PDOException(get_class($this) . ' "' . $this->table_name . '" not found (' . implode(',', $this->where_scope) . ')');
		}

		return null;
	}

	public function last()
	{
		if (empty($this->order_scope)) {
			$this->order(['by' => $this->primary_key, 'direction' => 'DESC']);
		} else {
			$direction = strtolower($this->order_scope['direction']);

			switch($direction) {
				case 'asc':
					$this->order_scope['direction'] = 'DESC';
				break;

				case 'desc':
				default: $this->order_scope['direction'] = 'ASC';
			}
		}

		$this->limit(1);

		return $this->offsetGet(0);
	}

	public function count(): int
	{
		if (!$this->loaded) {
			try {
				$wc = $this->where_conditions();
				$values = $wc->values;
				$statement = 'SELECT COUNT(*) AS count FROM ' . $this->table_name . $wc->statement;

				$result = $this->db->dbquery($statement, $values->getArrayCopy());

				return $result[0]['count'];

			} catch (\PDOException $e) {
				return 0;
			}
		} else {
			return parent::count();
		}
	}

	public function is_empty ()
	{
		return $this->count() == 0;
	}

	public function columns($args)
	{
		if (!is_an_array($args)) {
			$args = [ $args ];
		}

		$this->select_list = $args;

		return $this;
	}

	public function where($args = [])
	{
		if (empty($this->where_scope)) {
			$this->where_scope = [];
		}

		if (is_string($args)) {
			$args = [ $args ];
		}

		$this->where_scope = array_merge($this->where_scope, $args);

		return $this;
	}

	public function join($args = [])
	{
		if (empty($this->join_scope)) {
			$this->join_scope = [];
		}

		if (is_string($args)) {
			$args = [ $args ];
		}

		$this->join_scope = array_merge($this->join_scope, $args);

		return $this;
	}

	public function order($by, $direction = 'ASC')
	{
		if (empty($this->order_scope)) {
			$this->order_scope = [];
		}

		$this->order_scope = array_merge(
			$this->order_scope,
			['by' => $by, 'direction' => $direction]
		);

		return $this;
	}

	public function group($by)
	{
		$this->group_scope = $by;

		return $this;
	}

	public function limit($limit)
	{
		$this->limit_scope = $limit;

		return $this;
	}

	public function offset($offset)
	{
		$this->offset_scope = $offset;

		return $this;
	}

	/* Same as #offset, but takes a per_page parameter. */
	// TODO: implement the per_page parameter to override the one in the model
	public function page ($page, $per_page = null)
	{
		$model = $this->model;

		if($per_page === null) { $per_page = $model::$per_page; }

		$this->offset($page * $per_page)->limit($per_page);

		return $this;
	}

	public static function __callStatic($name, $arguments)
	{
		$collection = new Collection($arguments[0]);

		switch ($name) {
			case '_columns': return $collection->columns($arguments[1]);
			case '_where':   return $collection->where($arguments[1]);
			case '_join':    return $collection->join($arguments[1]);
			case '_order':   return $collection->order($arguments[1], $arguments[2]);
			case '_group':   return $collection->group($arguments[1]);
			case '_limit':   return $collection->limit($arguments[1]);
			case '_offset':  return $collection->offset($arguments[1]);
			case '_all':     return $collection->all();

			default: throw new \Bacon\Exceptions\MethodNotFound();
		}
	}

	public function loaded ($status = null)
	{
		if ($status === false || $status === true) {
			$this->loaded = $status;
		}

		return $this->loaded == true;
	}

	private function load()
	{
		if ($this->loaded) return;

		$values = [];
		$statement = 'SELECT ';

		if (!empty($this->select_list)) {
			if (is_array($this->primary_key)) {
				foreach ($this->primary_key as $key) {
					if (!in_array($key, $this->select_list)) {
						$this->select_list[] = $key;
					}
				}
			} elseif (!in_array($this->primary_key, $this->select_list)) {
				$this->select_list[] = $this->primary_key;
			}

			$statement .= implode(', ', $this->select_list);
		} else {
			$statement .= '*';
		}

		$statement .= ' FROM ' . $this->table_name;

		$jc = $this->join_conditions();
		$statement .= $jc->statement;

		$wc = $this->where_conditions();
		$statement .= $wc->statement;

		$values = array_merge($values, $wc->values->to_array());

		if (!empty($this->group_scope)) {
			$statement .= ' GROUP BY ' . $this->group_scope;
		}

		if (!empty($this->order_scope)) {
			$order_scope = array_merge([ 'by' => 'id', 'direction' => 'ASC' ], $this->order_scope);

			$statement .= ' ORDER BY ' . $order_scope['by'] . ' ' . $order_scope['direction'];
		}

		if (!empty($this->limit_scope)) {
			$statement .= ' LIMIT ' . $this->limit_scope;
		}

		if (!empty($this->offset_scope)) {
			$statement .= ' OFFSET ' . $this->offset_scope;
		}

		try {
			$items = $this->db->dbquery($statement, $values);

		} catch (\PDOException $e) {
			$this->loaded = true;

			#throw $e;
		}

		if (empty($items)) {
			$items = V([]);
		}

		$model = $this->model;

		foreach ($items as $item) {
			$this[] = new $model($item, true);
		}

		$this->loaded = true;
	}

	private function where_conditions ()
	{
		$statement = ' ';
		$values = V();

		if (!empty($this->where_scope)) {
			$conditions = V();

			$statement .= 'WHERE ';

			foreach ($this->where_scope as $column_name => $condition) {
				if (is_an_array($condition)) {
					// array('id' => array(1, 2, 3)) => 'id IN (1, 2, 3)'
					$conditions->push($column_name . ' IN (' . str_repeat('?,', count($condition) - 1) . '?)');

					$values->push($condition);

				} elseif (!$column_name) {
					// String condition, for clauses that cover more complex conditions than simple ANDs.
					$conditions->push("(" . $condition . ")");

				} else {
					if ($condition === null) {
						$c = $column_name . ' IS NULL';

					} else {
						$c = $column_name . ' = ?';

						$values->push($condition);
					}

					$conditions->push($c);
				}
			}

			$statement .= $conditions->join(' AND ');
		}

		return A([ 'statement' => $statement, 'values' => $values ]);
	}

	private function join_conditions ()
	{
		$statement = ' ';
		$values = V();

		if (!empty($this->join_scope)) {
			foreach ($this->join_scope as $table_name => $condition) {
				$statement .= ' ' . $condition . ' ';
				continue;

				# XXX TODO: other cases than join strings

				if (is_an_array($condition)) {
					// array('id' => array(1, 2, 3)) => 'id IN (1, 2, 3)'
					$conditions->push($column_name . ' IN (' . str_repeat('?,', count($condition) - 1) . '?)');

					$values->push($condition);

				} elseif (!$table_name) {
					// String condition, for clauses that cover more complex conditions than simple ANDs.
					$statement .= ' ' . $condition . ' ';

				} else {
					if ($condition === null) {
						$c = $column_name . ' IS NULL';

					} else {
						$c = $column_name . ' = ?';

						$values->push($condition);
					}

					$conditions->push($c);
				}
			}
		}

		return A([ 'statement' => $statement, 'values' => $values ]);
	}
}

?>
