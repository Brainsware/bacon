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

abstract class Model extends \Sauce\Object
{
	protected $db;
	protected static $table_name;
	protected static $primary_key = 'id';
	
	protected static $timestamps = true;
	protected static $created_at = 'created_at';
	protected static $updated_at = 'updated_at';

	protected static $relations;
	protected static $scopes;

	protected $errors;

	private $column_data = [];
	private $updated = false;
	private $stored = false;

	public static $per_page = 30;

	public function __construct($args = [], $stored = false)
	{
		$this->db = \Bacon\DB::__getInstance();

		$this->stored = $stored;

		parent::__construct($args);
	}

	public function __set($name, $value)
	{
		parent::__set($name, $value);

		$this->updated = true;
	}

	public function save($options = [])
	{
		if ($this->stored && !$this->updated && in_array(static::$primary_key, $this->keys(true)) && $this[static::$primary_key]) {
			// Nothing changed, not doing anything.
			return true;
		}

		$this->before_save();
		
		$created_at = static::$created_at;
		$updated_at = static::$updated_at;

		// This came from the database, but needs to be updated!
        if ($this->stored && $this->updated) {
			$timestamps = static::$timestamps;

			if (array_key_exists('timestamps', $options)) {
				$timestamps = $options['timestamps'] ? true : false;
			}

            if ($timestamps) {
                $this->$updated_at = \Sauce\DateTime::now();
            }

			if (!array_key_exists('skip_validation', $options) || $options['skip_validation'] !== true) {
				$this->validate();
			}

			if (!$this->has_errors()) {
				$result = $this->update();

				$this->after_save();

				return $result;
			} else {
				return false;
			}
		}

		// Seems like this is a new entry, needs to be inserted.
		if (static::$timestamps) {
            $this->$created_at = \Sauce\DateTime::now();
            $this->$updated_at = \Sauce\DateTime::now();
		}

		if (!array_key_exists('skip_validation', $options) || $options['skip_validation'] !== true) {
			$this->validate();
		}

		if (!$this->has_errors()) {
			$result = $this->create($options);

			$this->after_save();

			return $result;
		} else {
			return false;
		}
	}

	public function delete ()
	{
		$this->before_delete();

		$statement = 'DELETE FROM ' . static::$table_name;

		$statement .= ' WHERE ' . $this->primary_key_condition();

		$result = $this->db->query($statement);

		$this->stored = true;
		$this->updated = false;

		$this->after_delete();

		return true;
	}

	public static function find($id)
	{
		return Collection::_where(get_called_class(), [static::$primary_key => $id])->first();
	}

	public static function all()
	{
		return Collection::_all(get_called_class());
	}

	public static function select($args)
	{
		return Collection::_select(get_called_class(), $args);
	}

	public static function where($args = [])
	{
		return Collection::_where(get_called_class(), $args);
	}

	public static function joins($args = [])
	{
		return Collection::_joins(get_called_class(), $args);
	}

	public static function order($by, $direction = 'ASC')
	{
		return Collection::_order(get_called_class(), $by, $direction);
	}

	public static function group($by)
	{
		return Collection::_group(get_called_class(), $by);
	}

	public static function limit($limit)
	{
		return Collection::_limit(get_called_class(), $limit);
	}

	public static function __callStatic($name, $argument)
	{
		switch($name) {
			case 'table_name':  return static::$table_name; break;
			case 'primary_key': return static::$primary_key; break;
			default: throw new \Bacon\Exceptions\MethodNotFound(); break;
		}
	}

	public function errors ()
	{
		return V($this->errors);
	}

	public function has_errors ()
	{
		return $this->errors && !$this->errors->is_empty();
	}

	protected function error ($key, $message)
	{
		$this->errors->push(new ValidationError(['key' => $key, 'message' => $message]));
	}

	protected function validate ()
	{

	}

	private function update()
	{
		$this->before_update();

		$statement = 'UPDATE ' . static::$table_name . ' SET ';

		$sets = [];
		$values = [];

		foreach ($this->storage as $key => $value) {
			if ($value !== null) {
				$sets[] = '`' . $key . '` = ?';
				$values[] = $value;
			}
		}

		$statement .= implode(', ', $sets);

		$statement .= ' WHERE ' . $this->primary_key_condition();

		$result = $this->db->query($statement, $values);

		$this->stored = true;
		$this->updated = false;

		$this->after_update();

		return true;
	}

	private function create($options = [])
	{
		$this->before_create();

		$statement = 'INSERT INTO ' . static::$table_name . ' (';

		if (isset($options['include_primary_key'])) {
			$statement .= implode(', ', $this->_keys(false)) . ') VALUES (';
			$values = $this->_values(false);
		} else {
			$statement .= implode(', ', $this->_keys(true)) . ') VALUES (';
			$values = $this->_values();
		}

		$value_set = [];

		foreach ($values as $value) {
			$value_set[] = '?';
		}

		$statement .= implode(', ', $value_set) . ')';

		$result = $this->db->query($statement, $values, 'lastid');

		$this->stored = true;
		$this->updated = false;
		$this->id = $result;

		$this->after_create();

		return true;
	}

	private function _keys($exclude_primary_key = false)
	{
		$keys = [];

		foreach($this->storage as $key => $value) {
			if ($exclude_primary_key) {
				if ($key != static::$primary_key) {
					$keys[] = $key;
				}
			} else {
				$keys[] = $key;
			}
		}

		return $keys;
	}

	private function _values($exclude_primary_key = true)
	{
		$values = [];

		foreach($this->storage as $key => $value) {
			if ($key == static::$primary_key && $exclude_primary_key) {
				continue;
			}
			$values[] = $value;
		}

		return $values;
	}

    private function primary_key_condition () {
        $statement = '';

        if (is_array(static::$primary_key)) {
            $primary_keys_statement = [];

            foreach (static::$primary_key as $key) {
                 array_push($primary_keys_statement, $key . ' = ' . $this[$key]);
            }

            $statement .= implode(' AND ', $primary_keys_statement);
        } else {
            $statement .= static::$primary_key . ' = ' . $this->db->quote($this[static::$primary_key]);
        }

        return $statement;
    }

    protected function before_save () {}
    protected function before_create () {}
    protected function before_update () {}
    protected function before_delete () {}

    protected function after_save () {}
    protected function after_create () {}
    protected function after_update () {}
    protected function after_delete () {}
}

?>
