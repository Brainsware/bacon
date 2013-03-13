<?php
/**
 * @package System
 */

namespace Bacon;

/**
 * Bacon DB ORM
 * 
 * @package System
 */
abstract class ORM extends \ArrayObject
{
	/**
	 * @var array $instances Array holding all objects
	 */
	private static $instances = array();
	/**
	 * @var string $db_table Defines the table for the module - without prefix.
	 */
	protected static $db_table;
	/**
	 * @var bool $db_multilang Use multilanguage feature?
	 */
	protected static $db_multilang = false;
	/**
	 * @var string $db_id Defines the ID column
	 */
	protected static $db_id = 'id';
	/**
	 * @var array $db_fks A list of foreign keys
	 */
	protected static $db_fks = array();
	/**
	 * @var array $db_langfields Columns being used with multiple languages
	 */
	protected static $db_langfields = array();
	
	/**
	 * @var object $db The DB object
	 */
	protected $db;
	
	/**
	 * @var array $db_column_data Columns to be loaded from the table
	 */
	private $db_column_data = array();
	/**
	 * @var bool $db_updated Has data been updated?
	 */
	private $db_updated = false;
	/**
	 * @var string $db_lang Currently active language
	 */
	private $db_language;
	
	public function offsetGet ($index)
	{
		if(!empty($this->db_column_data[$index])) {
			return $this->db_column_data[$index];
		} else {
			return parent::offsetGet($index);
		}
	}
	
	public function offsetExists ($index)
	{
		return (!empty($this->db_column_data[$index]) || parent::offsetExists($index));
	} 
	
	/**
	 * Get single object by id
	 */
	public static function getById (
		$id )
	{
		if (empty($id)) {
			return false;
		} elseif (isset(self::$instances[$id])) {
			return self::$instances[$id];
		}
		$classname = get_called_class();
		self::$instances[$classname][$id] = new $classname($id);
		return self::$instances[$classname][$id];
	}
	
	/**
 	 * Get all items as objects
	 * 
	 * @param array $foreign_keys Foreign keys to be included in query
	 * 
	 * @return mixed Objectified instances or false on DB failure
	 */
	public static function getList (
		$foreign_keys = NULL,
		$position_col = NULL,
  		$limit = 0,
   		$limitOffset = 0 )
	{
		$db = DB::__getInstance();
		# get only fields relevant for language handling if module is multilanguaged
		$statement = '
			SELECT ' . (static::$db_multilang ? '$$PRE_' . static::$db_table . '.*, $$PRE_' . static::$db_table . '_lang.' . implode(', $$PRE_' . static::$db_table . '_lang.', static::$db_langfields) : '*') . '
			FROM $$PRE_' . static::$db_table;
		if (!empty(static::$db_multilang)) {
			$statement .= '
				LEFT JOIN $$PRE_' . static::$db_table . '_lang ON ' . static::$db_id . ' = ' . static::$db_table . '_' . static::$db_id . ' AND language = ?';
		}
		if (!empty($foreign_keys)) {
			# include provided foreign keys
			$statement .= '
				WHERE';
			foreach ($foreign_keys as $key => $value) {
				$statement .= ' ' . $key . ' = ' . $value . ' AND';
			}
			$statement = rtrim($statement, 'AND');
		}
		if (!empty($position_col)) {
			$statement .= '
				ORDER BY ' . $position_col;
		}
		if (!empty($limit)) {
			$statement .= '
				LIMIT ' . $limit . (!empty($limitOffset)? ' OFFSET ' . $limitOffset : '');
		}
		try {
			$values = static::$db_multilang ? array (Request::$request['language']) : array ();
			$itemlist = $db->query($statement, $values);
		} catch (PDOException $e) {
			return false;
		}
		if (empty($itemlist)) {
			return false;
		}
		$classname = get_called_class();
		foreach ($itemlist as $item) {
			self::$instances[$classname][$item['id']] = new $classname($item['id'], $item);
		}
		return self::$instances[$classname];
	}
	
	/**
	 * Initializes ORM object
	 * 
	 * @param array $columns Specifies a list of columns to be loaded - if empty, all are loaded.
	 * @param bool $with_data Defines if the columns array is an associative array containing data.
	 */
	public function __construct (
		array $column_data = array(),
		$with_data = false,
   		$language = NULL )
	{
		if (empty(static::$db_id)) {
			Log::fatal('No ID column set for the table in class "' . get_class($this) . '" set.');
		}
		$classname = get_called_class();
		static::$db_multilang = $classname::$db_multilang;
		if (static::$db_multilang && empty(static::$db_langfields)) {
			Log::fatal('Class: ' . get_class($this) . ' - No language fields set!');
		} elseif (static::$db_multilang) {
			if (!empty($language)) {
				$this->db_language = $language;
			} else {
				$this->db_language = Request::$request['language'];
			}
		}
		# no instance set - get default db instance.
		if (!$this->db instanceof DB) {
			$this->db = DB::__getInstance();
		}
		if (count($column_data) > 0) {
			if ($with_data) {
				# for prefilling with data when calling over a mass load for performance increase
				$this->db_column_data = $column_data;
			} else {
				foreach ($column_data as $column) {
					$this->db_column_data[$column] = '';
				}
			}
		}
		
		$db_id = static::$db_id;
		if (!empty($this->$db_id) && !$with_data) {
			$this->load();
		}
	}
	
	/**
	 * Save all data if update has been made and not saved.
	 */
	public function __destruct ()
	{
		if ($this->db_updated) {
			# save all data.
			$this->save();
		}
	}
	
	/**
	 * Return all column data
	 * 
	 * @return array
	 */
	public function getAll ()
	{
		return array_merge($this->db_column_data, $this->getArrayCopy());
	}
	
	/**
	 * Get column field data
	 */
	public function __get (
		$name )
	{
		# get column data
		if (isset($this->db_column_data[$name])) {
			return $this->db_column_data[$name];
		}
	}
	
	/**
	 * Set column field data
	 */
	public function __set (
		$name,
		$value )
	{
		if (isset($this->db_column_data[$name]) || property_exists($this, 'db_' . $name) || in_array($name, static::$db_langfields)) {
			$this->db_column_data[$name] = ($value === false ? 0 : $value);
			$this->db_updated = true;
		}
	}
	
	/**
	 * Checks whether column field is set
	 */
	public function __isset (
		$name )
	{
		return isset($this->db_column_data[$name]);
	}
	
	/**
	 * Loads either all or a specified set of column data from the specified id (in db_id)
	 *
	 * @return mixed
	 */
	public function load ()
	{
		$db_id = static::$db_id;
		if (empty($this->$db_id)) {
			return false;
		}
		# PRE LOAD TRIGGER FUNCTION
		if (method_exists($this, 'pre_load')) {
			if (!call_user_func_array(array($this, 'pre_load'), func_get_args())) {
				return false;
			}
		}
		$statement = '
			SELECT ';
		if (count($this->db_column_data) > 0) {
			$statement .= implode(', ', array_keys($this->db_column_data));
		} else {
			$statement .= '*';
		}
		$statement .= '
			FROM $$PRE_' . static::$db_table . '
			WHERE ' . static::$db_id . ' = ?';
		try {
			$static_fields = $this->db->query($statement, array($this->$db_id), 'row');
		} catch (Exception $e) {
			return false;
		}
		if (empty($static_fields)) {
			return false;
		}
		
		$lang_fields = array();
		if (static::$db_multilang) {
			# now load language fields
			$statement = '
				SELECT ' . implode(', ', static::$db_langfields) . '
				FROM $$PRE_' . static::$db_table . '_lang
				WHERE ' . static::$db_table . '_' . static::$db_id . ' = ? AND language = ?';
			try {
				$lang_fields = $this->db->query($statement, array($this->$db_id, $this->db_language), 'row');
			} catch (Exception $e) {
				return false;
			}
			if (empty($lang_fields)) {
				$lang_fields = array();
			}
		}
		$this->db_column_data = array_merge($static_fields, $lang_fields);
		if (method_exists($this, 'post_load')) {
			if (!call_user_func_array(array($this, 'post_load'), func_get_args())) {
				return false;
			}
		}
	}
	
	/**
	 *
	 */
	public function save (
		array $columns = array() )
	{
		if (count($columns) > 0) {
			foreach ($columns as $key => $data) {
				$this->$key = $data;
			}
		}
		$db_id = static::$db_id;
		$static_fields = array();
		$lang_fields = array();
		foreach ($this->db_column_data as $key => $data) {
			if (in_array($key, static::$db_langfields))  {
				$lang_fields[$key] = $data;
			} else {
				$static_fields[$key] = $data;
			}
		}
		$this->db_updated = true;
		if (empty($this->$db_id)) {
			if (count($this->db_column_data) == 0) {
				return false;
			}
			# PRE CREATE TRIGGER FUNCTION
			if (method_exists($this, 'pre_create')) {
				if (!call_user_func_array(array($this, 'pre_create'), func_get_args())) {
					return false;
				}
			}
			$column_string = implode(', ', array_keys($static_fields));
			$statement = '
				INSERT INTO $$PRE_' . static::$db_table . ' (' . $column_string . ')
				VALUES (' . rtrim(str_repeat('?,', count($static_fields)), ',') . ')';
			try {
				if ($this->$db_id = $this->db->query($statement, array_values($static_fields), 'lastid')) {
					$this->db_updated = false;
				}
			} catch (Exception $e) {
				return false;
			}
			# POST CREATE TRIGGER FUNCTION
			if (method_exists($this, 'post_create')) {
				if (!call_user_func_array(array($this, 'post_create'), func_get_args())) {
					return false;
				}
			}
		} elseif ($this->db_updated) {
			# PRE UPDATE TRIGGER FUNCTION
			if (method_exists($this, 'pre_update')) {
				if (!call_user_func_array(array($this, 'pre_update'), func_get_args())) {
					return false;
				}
			}
			$statement = '
				UPDATE $$PRE_' . static::$db_table . '
				SET
					';
			foreach ($static_fields as $key => $data) {
				$statement .= $key . ' = ?,';
			}
			$statement = rtrim($statement, ',');
			$statement .= '
				WHERE id = ?';
			$values = array_values($static_fields);
			$values[] = $this->$db_id;
			
			try {
				if ($this->db->query($statement, $values, 'affectedrows')) {
					$this->db_updated = false;
				}
			} catch (Exception $e) {
				return false;
			}
			# POST UPDATE TRIGGER FUNCTION
			if (method_exists($this, 'post_update')) {
				if (!call_user_func_array(array($this, 'post_update'), func_get_args())) {
					return false;
				}
			}
		}
		
		if (static::$db_multilang) {
			$statement = '
				SELECT COUNT(*)
				FROM $$PRE_' . static::$db_table . '_lang
				WHERE ' . static::$db_table . '_id = ? AND language = ?';
			$values = array_values($lang_fields);
			$values[] = $this->$db_id;
			try {
				if ($this->db->query($statement, array($this->$db_id, $this->db_language), 'one')) {
					# exists - update
					$statement = '
						UPDATE $$PRE_' . static::$db_table . '_lang
						SET
						';
					foreach ($lang_fields as $key => $data) {
						$statement .= $key . ' = ?,';
					}
					$statement = rtrim($statement, ',');
					$statement .= '
						WHERE ' . static::$db_table . '_id = ? AND language = ?';
					
					$values[] = $this->db_language;
					
					try {
						if ($this->db->query($statement, $values, 'affectedrows')) {
							$this->db_updated = false;
						}
					} catch (Exception $e) {
						return false;
					}
				} else {
					# no exists - insert
					$column_string = implode(', ', array_keys($lang_fields));
					$statement = '
						INSERT INTO $$PRE_' . static::$db_table . '_lang (' . $column_string . ', ' . static::$db_table . '_id, language)
						VALUES (' . rtrim(str_repeat('?,', count($lang_fields)), ',') . ', ?, ?)';
					
					$values[] = $this->db_language;
					
					try {
						if ($this->db->query($statement, $values, 'lastid')) {
							$this->db_updated = false;
						}
					} catch (Exception $e) {
						return false;
					}
				}
			} catch (Exception $e ) {
				return false;
			} 
		}
		
		return !$this->db_updated;
	}
	
	/**
	 * Deletes the row with the in db_id specified id
	 */
	public function delete ()
	{
		# PRE DELETE TRIGGER FUNCTION
		if (method_exists($this, 'pre_delete')) {
			if (!call_user_func_array(array($this, 'pre_delete'), func_get_args())) {
				return false;
			}
		}
		$db_id = static::$db_id;
		if (empty($this->$db_id)) {
			return false;
		}
		
		if (static::$db_multilang) {
			$statement = '
				DELETE FROM $$PRE_' . static::$db_table . '_lang
				WHERE ' . static::$db_table . '_id = ?';
			try {
				$this->db->query($statement, array($this->$db_id));
			} catch (Exception $e) {
				return false;
			}
		}
		
		$statement = '
			DELETE FROM $$PRE_' . static::$db_table . '
			WHERE ' . static::$db_id . ' = ?';
		try {
			if ($this->db->query($statement, array($this->$db_id), 'affectedrows')) {
				# POST DELETE TRIGGER
				if (method_exists($this, 'post_delete')) {
					if (!call_user_func_array(array($this, 'post_delete'), func_get_args())) {
						return false;
					}
				}
				$this->$db_id = 0;
				return true;
			} else {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}
}

?>
