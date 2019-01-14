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

abstract class Model extends \Sauce\SObject
{
	use BeforeAndAfter;

	use \Bacon\Traits\Errors {
		error as store_error;
	}

	protected $db;
	protected static $table_name;
	protected static $primary_key = 'id';
	
	protected static $timestamps = true;
	protected static $created_at = 'created_at';
	protected static $updated_at = 'updated_at';
	protected static $sortable   = null;

	/*
	 * protected static $relations = [
	 *   'name' => [ # Name of the relation
	 *   	# Modelname
	 *   	'model'  => '\Namespace\ModelName',
	 *   	# Columnname, defaults to relationname + _id
	 *   	'column' => 'column_name'
	 *   	# Relation type; either has_many or has_one
	 *   	# Defaults to has_one
	 *   	'type'   => 'has_one'
	 *   ]
	 * ]];
	*/
	protected static $relations;
	protected static $belongs_to;

	protected static $scopes;

	protected $stored_errors;

	private $column_data = [];
	private $updated = false;
	private $stored = false;

	public static $per_page = 30;


	/* Constructor for the Model. Gets an instance of \Bacon\Database,
	 * initializes the error store and calls \Sauce\SObject's constructor.
	 *
	 * $args is an array of values to set in a newly created model's column
	 * values. $stored is a boolean indicating whether this object is already
	 * stored in the database.
	 *
	 * Example: Update an object with an array of new values
	 *
	 *   $asset_data = [ 'id' => 10, 'hash' => '1234567890', 'name' => 'New name!' ]
	 *
	 *   # Update object in database
	 *   $asset = new \Model\Asset($asset_data, true);
	 *   $asset->save();
	 *
	 */
	public function __construct($args = [], $stored = false)
	{
		$this->db = \Bacon\ORM\DatabaseSingleton::get_instance();

		$this->stored = $stored;

		$this->__construct_errors();

		parent::__construct($args);
	}

	/* Fetch the item with given id from the database. */
	public static function find($id)
	{
		return Collection::_where(get_called_class(), [ static::$primary_key => $id ])->first();
	}

	/* The following methods are proxies for the corresponding Collection methods. */

	public static function all()
	{
		return Collection::_all(get_called_class());
	}

	public static function columns($args)
	{
		return Collection::_columns(get_called_class(), $args);
	}

	public static function where($args = [])
	{
		return Collection::_where(get_called_class(), $args);
	}

	public static function join($args = [])
	{
		return Collection::_join(get_called_class(), $args);
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

	/* Save the object. This method is used for both, updated and newly created
	 * objects. It finds out what to do based on its current internal state and
	 * acts accordingly.
	 *
	 * The methods #before_save and #after_save are called during the process.
	 * They can be overriden in models to build in some additional
	 * functionality. (Like slug-generation or simple validations, e.g.)
	 *
	 * Possible options:
	 * * timestamps:      boolean   overrides the model's timestamps settings
	 * * skip_validation: boolean   do not call #validate
	 */
	public function save($options = [])
	{
		if ($this->stored
			&& !$this->updated
			//&& in_array(static::$primary_key, $this->keys(true))
			&& $this[static::$primary_key]) {

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

	/* Stores this object in the database with all key-value pairs in
	 * storage. 
	 *
	 * Called by #save
	 *
	 * Calls #before_create and #after_create.
	 */
	private function create($options = [])
	{
		$this->before_create();

		$statement = 'INSERT INTO ' . static::$table_name . ' (';

		if (isset($options['include_primary_key'])) {
			$statement .= implode(', ', $this->_keys(false, true)) . ') VALUES (';
			$values = $this->_values(false);
		} else {
			$statement .= implode(', ', $this->_keys(true, true)) . ') VALUES (';
			$values = $this->_values();
		}

		$value_set = [];

		foreach ($values as $value) {
			$value_set[] = '?';
		}

		$statement .= implode(', ', $value_set) . ')';

		$result = $this->db->query($statement, $values, 'last_id');

		/* NOTE: PDO's lastInsertId does not work with PostgreSQL unless we supply
		 *       the sequence name. This is a hack to circumvent this.
		 *
		 *       Also: it would be nice to handle this in the DB wrapper, but
		 *       unfortunately we don't have any information about the query or table
		 *       there, so the hack has to stay here for now.
		 */
		if ('pgsql' === $this->db->type()) {
			$result = $this->db->lastInsertId(static::$table_name . '_id_seq');
		}

		$this->stored = true;
		$this->updated = false;
		$this->id = $result;

		$this->after_create();

		return true;
	}

	/* Update this object in the database with all key-value pairs in
	 * storage (except the primary key).
	 *
	 * Called by #save.
	 *
	 * Calls #before_update and #after_update.
	 */
	private function update ()
	{
		$this->before_update();

		$statement = 'UPDATE ' . static::$table_name . ' SET ';

		$sets = [];
		$values = [];

		foreach ($this->storage as $key => $value) {
			if ($value !== null) {
				$sets[] = $this->db->quote_column($key) . ' = ?';
				$values[] = $value;
			}
		}

		$statement .= implode(', ', $sets);

		$pk = $this->primary_key_condition();

		$statement .= ' WHERE ' . $pk->statement;

		$values = array_merge($values, $pk->values->getArrayCopy());

		$result = $this->db->query($statement, $values, null, false);

		$this->stored = true;
		$this->updated = false;

		$this->after_update();

		return true;
	}

	/* Delete this object from the database.
	 *
	 * Calls #before_delete and #after_delete.
	 */
	public function delete ()
	{
		$this->before_delete();

		$statement = 'DELETE FROM ' . static::$table_name;

		$pk = $this->primary_key_condition();

		$statement .= ' WHERE ' . $pk->statement;

		$result = $this->db->query($statement, $pk->values->getArrayCopy(), null, false);

		$this->stored = true;
		$this->updated = false;

		$this->after_delete();

		return true;
	}

	public function move ($new)
	{
		if (empty(static::$sortable) || $new === null) {
			return false;
		}

		$sort = static::$sortable;
		$values = [];

		if (!empty(static::$belongs_to)) {
			$fk_ids = [];

			$fk_query = '';

			foreach (static::$belongs_to as $fk) {
				$fk_query .= ' ' . $fk['column'] . ' = ?';
				$fk_column = $fk['column'];

				$values[] = $this->$fk_column;
			}
		}

		if ($new > $this->$sort) {
			$values[] = $this->$sort;
			$values[] = $new;

			$this->db->query(
				'UPDATE ' . static::$table_name . '
				SET ' . $sort . ' = ' . $sort . ' - 1 WHERE ' . $fk_query . ' AND ' . $sort . ' > ? AND ' . static::$sortable . ' <= ?',
				$values
			);
		} else {
			$values[] = $new;
			$values[] = $this->$sort;

			$this->db->query(
				'UPDATE ' . static::$table_name . '
				SET ' . $sort . ' = ' . $sort . ' + 1 WHERE ' . $fk_query . ' AND ' . $sort . ' >= ? AND ' . $sort . ' < ?',
				$values
			);
		}

		$this->$sort = $new;
		$this->save();
	}

	/* Overriding Object#__set so we can determine whether something was
	 * updated. 
	 */
	public function __set ($name, $value)
	{
		parent::__set($name, $value);

		$this->updated = true;
	}

	/* Using the __call method to handle relations between models. */
	public function __call ($method, $args)
	{
		$result = $this->handle_relations($method, $args);

		if (null === $result) {
			throw new \InvalidArgumentException("Given relation type is not valid: {$type}\nSupported types are: belongs_to, has_one and has_many");
		}

		return $result;
	}

	public static function __callStatic($name, $argument)
	{
		switch($name) {
			case 'table_name':  return static::$table_name; break;
			case 'primary_key': return static::$primary_key; break;
			default: throw new \Bacon\Exceptions\MethodNotFound(); break;
		}
	}

	protected function error ($column, $message)
	{
		$this->store_error(new \Bacon\Exceptions\ValidationError([ 'column' => $column, 'message' => $message ]));
	}

	/* Returns all keys as \Sauce\Vector except for the primary key! */
	private function _keys($exclude_primary_key = false, $escape_keys = false)
	{
		$keys = [];

		foreach($this->storage as $key => $value) {
			if ($exclude_primary_key) {
				if ($key != static::$primary_key) {
					$keys[] = $escape_keys ? $this->db->quote_column($key) : $key;
				}
			} else {
				$keys[] = $escape_keys ? $this->db->quote_column($key) : $key;
			}
		}

		return $keys;
	}

	/*
	 * TODO: relations documentation!
	 */
	private function handle_relations ($method, $args)
	{
		$relations = A(static::$relations);

		if (!$relations->keys()->includes($method)) {
			return parent::__call($method, $args);
		}

		$model  = $relations[$method]['model'];
		$column = isset($relations[$method]['column']) ? strtolower($relations[$method]['column']) : $method . '_id';
		$type = isset($relations[$method]['type']) ? strtolower($relations[$method]['type']) : 'has_one';

		if (!V([ 'belongs_to', 'has_one', 'has_many' ])->includes($type)) {
		}

		switch ($type) {
			case 'has_one':
				if (!empty($this->$column)) {
					return $model::find($this->$column);
				} else {
					return new $model;
				}
				break;

			case 'belongs_to':
				return $model::where([ $column => $this->id ])->first();
				break;

			case 'has_many':
				return $model::where([ $column => $this->id ]);
				break;

			default: return null;
		}
	}

	/* Returns all values as \Sauce\Vector except for the primary key! */
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

	/* Returns a condition string (WHERE <conditions...>) for the primary
	 * key(s) depending on how it is/they are defined in static::$primary_key.
	 */
	private function primary_key_condition () {
		$statement = '';
		$values = V();

		if (is_array(static::$primary_key)) {
			$primary_keys_statement = [];

			foreach (static::$primary_key as $key) {
				array_push($primary_keys_statement, $key . ' = ?');
				$values->push($this[$key]);
			}

			$statement .= implode(' AND ', $primary_keys_statement);
		} else {
			$statement .= static::$primary_key . ' = ?';
			$values->push($this[static::$primary_key]);
		}

		return A([ 'statement' => $statement, 'values' => $values ]);
	}
}

?>
