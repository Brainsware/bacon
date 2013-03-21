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

/**
 * @package System
 * @module Collection
 */
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
	protected $joins_scope;

	protected $loaded = false;

	public function __construct($model)
	{
		$this->model       = $model;
		$this->table_name  = $model::table_name();
		$this->primary_key = $model::primary_key();
		$this->db          = \Bacon\DB::__getInstance();
	}

	public function __call($name, $arguments)
	{
		if (empty($this->select_list)) {
			$this->select_list = [];
		}

		$this->select_list[$name] = $arguments[0];

		return $this;
	}

	public function offsetGet($offset)
	{
		if (!$this->loaded) {
			$this->load();
		}

        if (parent::offsetExists($offset)) {
            return parent::offsetGet($offset);
        }

        return false;
	}

	public function getIterator()
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
			throw new \PDOException(get_class($this) . ' not found (' . implode($this->where_scope, ', ') . ')');
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

	public function count()
	{
        if (!$this->loaded) {
            try {
                $statement = 'SELECT COUNT(*) AS count FROM ' . $this->table_name . $this->where_conditions();

                $result = $this->db->query($statement);

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

	public function select($args)
	{
        if (!is_array($args)) {
            $args = [$args];
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
            $args = [$args];
        }

		$this->where_scope = array_merge($this->where_scope, $args);

		return $this;
	}

	public function joins(array $args = [])
	{
		$this->joins_scope = array_merge($this->joins_scope, $args);

		return $this;
	}

	public function order($by, $direction)
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
	public function page ($page)
	{
		$model = $this->model;

		$this->offset($page * $model::$per_page)->limit($model::$per_page);

		return $this;
	}

	public static function __callStatic($name, $arguments)
	{
		$collection = new Collection($arguments[0]);

		switch ($name) {
			case '_select': return $collection->select($arguments[1]);
			case '_where':  return $collection->where($arguments[1]);
			case '_order':  return $collection->order($arguments[1], $arguments[2]);
			case '_group':  return $collection->group($arguments[1]);
			case '_limit':  return $collection->limit($arguments[1]);
			case '_offset': return $collection->offset($arguments[1]);
			case '_all':    return $collection->all();

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

		$statement = 'SELECT ';

		if (count($this->select_list) > 0) {
            if (is_array($this->primary_key)) {
                foreach ($this->primary_key as $key) {
                    if (!in_array($key, $this->select_list)) {
                        $this->select_list[] = $key;
                    }
                }
            } else {
                if (!in_array($this->primary_key, $this->select_list)) {
                    $this->select_list[] = $this->primary_key;
                }
            }

			$statement .= implode(', ', $this->select_list);
		} else {
			$statement .= '*';
		}

		$statement .= ' FROM ' . $this->table_name;

        $statement .= $this->where_conditions();

        if (!empty($this->group_scope)) {
        	$statement .= ' GROUP BY ' . $this->group_scope;
        }

		if (!empty($this->order_scope)) {
			$order_scope = array_merge(['by' => 'id', 'direction' => 'ASC'], $this->order_scope);

			$statement .= ' ORDER BY ' . $order_scope['by'] . ' ' . $order_scope['direction'];
		}

		if (!empty($this->limit_scope)) {
			$statement .= ' LIMIT ' . $this->limit_scope;
		}

		if (!empty($this->offset_scope)) {
			$statement .= ' OFFSET ' . $this->offset_scope;
		}

		try {
			$items = $this->db->query($statement);

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

		if (!empty($this->where_scope)) {
			$conditions = [];

			$statement .= 'WHERE ';

			foreach ($this->where_scope as $column_name => $condition) {
				// array('id' => array(1, 2, 3)) => 'id IN (1, 2, 3)'
				if (is_array($condition)) {
					$in_params = [];

					foreach ($condition as $c) {
						array_push($in_params, $this->db->quote($c));
					}

					array_push($conditions, $this->table_name . '.' . $column_name . ' IN(' . implode(', ', $in_params) . ')');
				} elseif (!$column_name) {
					// String condition, for clauses that cover more complex conditions than simple ANDs.
					array_push($conditions, "(" . $condition . ")");
				} else {
					if ($condition === null) {
						$c = $this->table_name . '.' . $column_name . ' IS NULL';
					} else {
						$c = $this->table_name . '.' . $column_name . ' = ';
						$c .= $this->db->quote($condition);
					}

					array_push($conditions, $c);
				}
			}

			$statement .= implode(' AND ', $conditions);
		}

        return $statement;
    }
}

?>
