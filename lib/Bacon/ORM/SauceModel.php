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
	use BeforeAndAfter;
	use Pagination;

	use \Bacon\Traits\Errors;

	protected $db;
	protected static $table_name;
	protected static $primary_key = 'id';
	
	protected static $timestamps;
	protected static $created_at = 'created_at';
	protected static $updated_at = 'updated_at';

	protected static $scopes;

	private $updated = false;
	private $stored = false;

	public static $per_page = 30;

	/* Constructor for the Model. Gets an instance of \Bacon\DB to talk to the
	 * database, initializes the error store and calls \Sauce\Object's
	 * constructor.
	 *
	 * $args is an array of values to set in a newly created model's column values.
	 * $stored is a boolean indicating whether this object is already stored in the
	 * database.
	 *
	 * NOTE: $stored is currently used internally by \Bacon\ORM\Collection, but
	 * might as well be used for assigning multiple attributes to an object.
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
	public function __construct ($args = [], $stored = false)
	{
		$this->db = \Bacon\DB::__getInstance();
		$this->__construct_errors();

		$this->stored = $stored;

		parent::__construct($args);
	}

	/* Fetch the item with given id from the database. */
	public static function find($id)
	{ return Collection::_where(get_called_class(), [static::$primary_key => $id])->first(); }

	/* The following methods are proxies for the corresponding Collection methods. */

	public static function all()
	{ return Collection::_all(get_called_class()); }

	public static function select($args)
	{ return Collection::_select(get_called_class(), $args); }

	public static function where($args = [])
	{ return Collection::_where(get_called_class(), $args); }

	public static function joins($args = [])
	{ return Collection::_joins(get_called_class(), $args); }

	public static function order($by, $direction)
	{ return Collection::_order(get_called_class(), $by, $direction); }

	public static function group($by)
	{ return Collection::_group(get_called_class(), $by); }

	public static function limit($limit)
	{ return Collection::_limit(get_called_class(), $limit); }

	/* Overriding Object#__set so we can determine whether something was updated. */
	public function __set ($name, $value)
	{
		parent::__set($name, $value);

		$this->updated = true;
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
	public function save ($options = [])
	{
		if ($this->stored && !$this->updated &&
			/* I am not sure this is correct. Why would I exclude the primary key
			 * and then check whether it's in the array? Hrm. TODO: Test this. */
			$this->_keys(true)->includes(static::$primary_key) && 
			$this[static::$primary_key]) {
			return true;
		}

		$this->before_save();

		$options = A($options);
		$timestamps = static::$timestamps;

		if ($options->has_key('timestamps')) {
			$timestamps = $options->timestamps ? true : false;
		}

		/* If timestamps are enabled, set updated_at for all and created_at only
		 * for new objects */
		if ($timestamps) {
			$this->updated_at = \Sauce\DateTime::now();

			if (!$this->stored) {
				$this->created_at = \Sauce\DateTime::now();
			}
		}

		if ($options->has_key('skip_validation')) {
			if ($options->skip_validation !== true) { $this->validate(); }
		}

		if ($this->has_errors()) {
			return false;
		}

		$result = null;

		if ($this->stored && $this->updated) {
			/* An existing object was updated */
			$result = $this->update();

		} else {
			/* Store the new object in the database */
			$result = $this->create();
		}

		$this->after_save();

		return $result;
	}

	/* Stores this object in the database with all key-value pairs in
	 * storage. 
	 *
	 * Called by #save
	 *
	 * Calls #before_create and #after_create.
	 */
	private function create ()
	{
		$this->before_create();

		/* INSERT INTO table_name (keys...) */
		$statement = 'INSERT INTO ' . static::$table_name . ' (' . implode(', ', $this->_keys(true)) . ') ';

		/* VALUES (?...) */
		$statement .= 'VALUES (' . str_repeat('?, ', $this->_values(true)->count() - 1) . ', ?)';

		$result = $this->db->query($statement, $values, 'lastid');

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
	private function update()
	{
		$this->before_update();

		$statement = 'UPDATE ' . static::$table_name . ' SET ';

		$values = [];


		$sets = $this->collect(function ($key, $value) {
			$values->push($value);

			return "`$key` = ?";
		});

		$statement .= $sets->join(', ');

		/*
		 * NOTE: I hope the collect above can actually access $values. If not,
		 *       remove the above collect code and put back the foreach loop.
		 */
		/*
		foreach ($this->storage as $key => $value) {
			if ($value !== null) {
				$sets[] = '`' . $key . '` = ?';
				$values[] = $value;
			}
		}

		$statement .= implode(', ', $sets);
		 */

		$statement .= ' WHERE ' . $this->primary_key_condition();

		$result = $this->db->query($statement, $values->to_array());

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

		$statement .= ' WHERE ' . $this->primary_key_condition();

		$result = $this->db->query($statement);

		$this->stored = true;
		$this->updated = false;

		$this->after_delete();

		return true;
	}

	/* Returns a condition string (WHERE <conditions...>) for the primary
	 * key(s) depending on how it is/they are defined in static::$primary_key.
	 */
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

	/* Returns all keys as \Sauce\Vector except for the primary key! */
	private function _keys ($exclude_primary_key = false)
	{
		return $this->keys(function ($key) {
			return !($exclude_primary_key && $key == static::$primary_key);
		});
	}

	/* Returns all values as \Sauce\Vector except for the primary key! */
	private function _values ($exclude_primary_key = false)
	{
		return $this->values(function ($key, $value) {
			return !($exclude_primary_key && $key == static::$primary_key);
		});
	}
}

?>
