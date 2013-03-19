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

use \PDO;
use \PDOException;
use \Bacon\Log;
use \Config\Database as CfgDB;

/**
 * Bacon Database handler
 *
 * @package System
 */
class DB extends \PDO
{
	private $dbtype    = '';
	private $dbserver  = 'localhost';
	private $dbport    = '';
	private $dbname    = '';
	private $dbuser    = '';
	private $dbpass    = '';
	private $dbpersist = [ PDO::ATTR_PERSISTENT => 'false' ];


	public $prefix = '';

	private static $instances = [];
	private static $static_log;
	private $log;

	public static function setLog ($log)
	{
		static::$static_log = $log;
	}

	/**
	 * DB Instance managing function.
	 *
	 * @param string $instance Instance name
	 * @param array $startupvars DB vars
	 *
	 * @return object
	 */
	public static function __getInstance ($name = 'default', array $config = NULL)
	{
		if (!is_string($name)) {
			static::$static_log->error('Wrong db instance parameter: ' . $name);

			return false;
		}

		if (!isset(self::$instances[$name])) {
			$config = NULL;

			if (!empty($name) && $name != 'default' && empty($config)) {
				$config = $name;
			}

			self::$instances[$name] = new DB($config, $config);
		}

		return self::$instances[$name];
	}

	/**
	 * Initializes the BaconDB class with all the options specified in the
	 * main section of the configs/database.php
	 * The init() method doesn't take any parameters nor does it do any returns.
	 * Instead it does a lot of sanity and paranoia checking and dies with
	 * fatal() whenever the errors are too grave.
	 *
	 * @param string $configsection Database config section
	 * @param array $dbconfig Database access vars may be passed here too.
	 */
	public function __construct ($configsection = NULL, array $dbconfig = NULL)
	{
		$this->log = static::$static_log;

		if (empty($dbconfig)) {
			if (empty($configsection)) {
				$dbconfig = CfgDB::$main;
			} else {
				$dbconfig = CfgDB::$$configsection;
			}

			if (!$dbconfig) {
				$this->log->fatal('Database config section "' . $configsection . '" not set. Please check your config file.');
			}
		}


		// Fill variables out of config
		if (!isset($dbconfig['type'])) {
			$this->log->fatal('No type specified!');
		}

		// setting $dbtype here.
		$this->dbtype = $dbconfig['type'];

		if ($dbconfig['type'] == 'sqlite') {
			self::construct_SQLite ($dbconfig);
		} else {
			self::construct_DB ($dbconfig);
		}

		if (isset($dbconfig['prefix'])) {
			$this->prefix = $dbconfig['prefix'] . (($dbconfig['prefix'] != '') ? '_':'');
		}

		// Build the connection
		try {
			if ($this->dbtype == 'sqlite') {
				parent::__construct($this->dbtype . ':' . $this->dbname);
			} else {
				parent::__construct(
					$this->dbtype . ':host=' . $this->dbserver .
					(!empty($this->dbname) ? ';dbname=' . $this->dbname : '') .
					(!empty($this->dbport) ? ';port=' . $this->dbport : ''),
						$this->dbuser, $this->dbpass, $this->dbpersist);
			}

		} catch (\PDOException $e) {
			// Since raising an exception in __construct invalidates $this, we cannot use
			// $this->log here, but have to fall back to the statically stored log instance
			$log = static::$static_log;
			$log->error('Could not create Database Connection: ' . $e->getMessage());

			throw $e;
		}

		if ($this->dbtype == 'mysql' && !empty($dbconfig['encoding'])) {
			$this->query('SET CHARACTER SET ' . $dbconfig['encoding']);
		}
	}

	/**
	 * Helper function to build SQLite DB object
	 */
	private function construct_SQLite (array $dbconfig)
	{
		if (!isset($dbconfig['name'])) {
			$this->log->fatal('No name specified');

		} elseif (!\Sauce\Path::check($dbconfig['name'], 'f', 'rw')) {
			$this->log->fatal('SQLite Databasefile specified in name is not readable and writable.');
		}

		$this->dbname = $dbconfig['name'];
	}

	private function construct_DB (array $dbconfig)
	{
		if (!isset($dbconfig['server'])) {
			$this->log->debug('No server specified. Defaulting to localhost.');
		}

		$this->dbserver = $dbconfig['server'];
		$this->dbname = empty($dbconfig['name']) ? '' : $dbconfig['name'];

		if (isset($dbconfig['username'])) {
			$this->dbuser = $dbconfig['username'];
		} else {
			$this->log->fatal('No username specified.');
		}

		if (isset($dbconfig['password'])) {
			$this->dbpass = $dbconfig['password'];
		} else {
			$this->log->fatal('No password specified.');
		}

		if (isset($dbconfig['persist'])) {
			if ($dbconfig['persist']) {
				$dbconfig['persist'] = true;
			}

			$this->dbpersist[PDO::ATTR_PERSISTENT] = $dbconfig['persist'];
		}
	}

	/**
	 * Executes query with given values as prepared statement.
	 *
	 * @param string $query Query for execution
	 * @param array $values Array with values for prepared statement.
	 * @param string $fetchMode Possible values:
	 * 	<li>
	 * 		<ul>multi - returns all values in numbered associative arrays</ul>
	 * 		<ul>row - returns the first row</ul>
	 * 		<ul>single/one - fetches the first column of the first row</ul>
	 * 		<ul>column - fetches the first column on multiple rows</ul>
	 * 		<ul>lastid - returns the last id of an insert statement</ul>
	 * 		<ul>affectedrows - returns number of affected rows</ul>
	 * 	</li>
	 * @param string $lastId Index name of last ID (only needed for PostgreSQL)
	 *
	 * @return mixed
	 */
	public function query (
		$query,
		array $values = [],
		$fetchMode    = 'multi',
		$lastId       = NULL)
	{
		$this->log->debug('Executing query: \'' . $query . '\' with values: \'' . serialize($values) . '\'');

		// try to prepare. PDO has different error handling strategies
		// this is why this code looks so ugly.
		try {
			if (!$stmt = $this->prepare($query)) {
				$error_info = \PDO::errorInfo();

				$this->log->error('Error during query preparation: ' . $error_info[2] . ' Query: \'' . $query  . '\'');

				return false;
			}

		} catch (PDOException $e) {
			$this->log->error('Error during query preparation: ' . $e->getMessage() . ' Query: \'' . $query .  '\'');

			throw $e;
		}

		$this->log->debug("Executing query:\n{$query}");

		// Trying to execute
		try {
			if ($stmt->execute($values)) {
				$fetchMethod = 'fetch' . ucfirst($fetchMode);
				$result = $this->$fetchMethod($stmt, $lastId);
				$stmt = NULL;

				//$this->log->debug('Query result: ' . print_r($result, true));
				return $result;
			} else {
				$this->log->error('Error during query execution: ' . implode(', ', $stmt->errorInfo()) .
					       ' Query: \'' . $query . '\' Values: \'' . serialize($values) . '\'');

				throw new PDOException(implode(', ', $stmt->errorInfo()));
			}

		} catch (PDOException $e) {
			$this->log->error('Error during query execution: ' . $e->getMessage() .
				       ' Query: \'' . $query . '\' Values: \'' . serialize($values) . '\'');

			throw $e;
		}
	}

	## Helper functions for query()

	private function fetchLastid ($stmt, $lastId)
	{
		return $this->lastInsertId($lastId);
	}

	private function fetchAffectedRows ($stmt)
	{
		return $stmt->rowCount();
	}

	private function fetchRow ($stmt)
	{
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		if (sizeof($result) == 0) {
			$result = false;
		}

		return $result;
	}

	private function fetchOne ($stmt)
	{
		return $this->fetchSingle($stmt);
	}

	private function fetchSingle ($stmt)
	{
		$result = $stmt->fetchColumn();

		if (empty($result)) {
			$result = false;
		}

		return $result;
	}

	private function fetchColumn ($stmt)
	{
		$retarr = [];

		for ($i = 0; $col = $stmt->fetchColumn(); ++$i) {
			$retarr[$i] = $col;
		}

		if (sizeof($retarr) > 0) {
			return $retarr;
		} else {
			return [];
		}
	}

	private function fetchMulti ($stmt)
	{
		/* This shit is slow as shit:
			$retarr = $stmt->fetchAll(PDO::FETCH_ASSOC);
			Thus: Not in use.
		 */
		$retarr = [];

		for ($i = 0; $row = $stmt->fetch(PDO::FETCH_ASSOC); ++$i) {
			$retarr[$i] = $row;
		}

		if (sizeof($retarr) > 0) {
			return $retarr;
		} else {
			return [];
		}
	}
}

?>
