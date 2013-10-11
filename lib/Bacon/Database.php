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

class Database extends \PDO
{
	protected $supported_drivers = [ 'sqlite', 'mysql', 'pgsql' ];
	protected $config; 
	protected $log;

	public function __construct ($log, $config)
	{
		if (!is_a($log, '\Bacon\Log')) {
			throw new \InvalidArgumentException("Supplied log parameter is not an instance of \Bacon\Log.");
		}

		if (!is_an_array($config)) {
			throw new \InvalidArgumentException("Supplied config parameter is not any known, valid type array.");
		}

		$this->log    = $log;
		$this->config = $this->check_config($this->sanitize_config($config));

		parent::__construct(
			$this->connection_string(),
			$this->config->username,
			$this->config->password,
			[ \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			  \PDO::ATTR_PERSISTENT         => $this->config->persistent,
		      \PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET {$this->config->encoding}" ]
		);
	}

	public function query ($query, $values = [], $fetch_mode = 'multi', $last_id = null)
	{
		/* NOTE: PDO has multiple error handling strategies.
		 *
		 * The ATTR_ERRMODE attribute is set to ERRMODE_EXCEPTION in the
		 * constructor, so PDO *should* throw exceptions on errors. But to ease
		 * the pain in case this does not work as expected, lets keep the
		 * checks in. */

		try {
			$statement = $this->prepare($query);

			if (!$statement) {
				throw new \PDOException(V(\PDO::errorInfo())->join());
			}

		} catch (\PDOException $e) {
			$this->log->error("Error during query preparation: {$e->getMessage()}\nQuery: {$query}");
		}

		$this->log->info("Executing query:\n{$query}\nwith values:\n" . var_export($values, true));

		try {
			$result = $statement->execute($values);

			if (!$result) {
				throw new \PDOException(V($statement->errorInfo())->join());
			}

			$result = $this->fetch($fetch_mode, $statement, $last_id);

			unset($statement);

			return $result;

		} catch (\PDOException $e) {
			$this->log->info("Error during execution of query:\n{$query}\nwith values:\n" . var_export($values, true));

			throw $e;
		}
	}

	protected function fetch ($mode, $statement, $last_id)
	{
		$supported_modes = [ 'multi', 'row', 'one', 'single', 'column', 'last_id', 'affected_rows' ];

		if (!V($supported_modes)->includes($mode)) {
			throw new \InvalidArgumentException("Given fetch mode is not supported: {$mode}");
		}

		$fetch_fn = "fetch_{$mode}";

		return $this->$fetch_fn($statement, $last_id);
	}

	protected function fetch_multi ($statement, $last_id)
	{
		$result = [];

		/* NOTE: PDOStatement#fetchAll(PDO::FETCH_ASSOC) is really slow, thus
		 * using a loop instead. */
		for ($i = 0;; $i++) {
			$row = $statement->fetch(\PDO::FETCH_ASSOC);

			if (!$row) break;

			$result[$i] = $row;
		}

		return $result;
	}

	protected function fetch_row ($statement, $last_id)
	{
		$result = $statement->fetch(PDO::FETCH_ASSOC);

		// TODO: Should an exception be thrown?
		if (empty($result)) {
			return false;
		}

		return $result;
	}

	protected function fetch_one ($statement, $last_id)
	{
		return $this->fetchSingle($statement);
	}

	protected function fetch_single ($statement, $last_id)
	{
		$result = $this->fetchColumn();

		if (empty($result)) {
			return false;
		}

		return $result;
	}

	protected function fetch_column ($statement, $last_id)
	{
		$result = [];

		for ($i = 0;; $i++) {
			$column = $statement->fetchColumn();

			if (!$column) break;

			$result[$i] = $column;
		}

		return $result;
	}

	protected function fetch_last_id ($statement, $last_id)
	{
		return $this->lastInsertId($last_id);
	}

	protected function fetch_affected_rows ($statement, $last_id)
	{
		return $this->rowCount();
	}

	protected function quote_column ($name)
	{
		if ('mysql' == $this->config->type) {
			return "`{$name}`";
		}

		return "\"{$name}\"";
	}

	protected function connection_string ()
	{
		if ('sqlite' == $this->config->type) {
			return "sqlite:{$this->config->name}";
		}

		$str = "{$this->config->type}:host={$this->config->host};";
		$str .= "dbname={$this->config->name};";

		if (!empty($this->config->port)) {
			$str .= "port:{$this->config->port};";
		}

		return $str;
	}

	/* Takes an array and returns only the valid config keys */
	protected function sanitize_config ($config)
	{
		$config = A($config)->select([
			'type',
			'host',
			'port',
			'filename',
			'name',
			'username',
			'password',
			'persistent',
			'encoding',
			'prefix'
		]);

		return $config;
	}

	/* Check config keys and values for errors and fail hard and early in case */
	protected function check_config ($config)
	{
		if ($config->is_empty()) {
			throw new \InvalidArgumentException("Given configuration is empty.");
		}

		if (empty($config->type) || !is_a_string($config->type)) {
			throw new \InvalidArgumentException("Given configuration contains no or a non-string database type.");
		}

		/* NOTE: Even though PDO definitely sports more than those three
		 * drivers, we have not tested any of those as of now. Especially not
		 * with Bacon as a whole. So I'd like to keep them "not supported" for
		 * now and add them later on if we, or anyone else ever need it.
		 */
		if (!V($this->supported_drivers)->includes($config->type)) {
			throw new \InvalidArgumentException("Given configuration contains an unsupported type: {$this->config->type}\nSupported types are: sqlite, mysql, pgsql");
		}

		if ('sqlite' == $config->type) {
			if (empty($config->name) || !is_a_string($config->name)) {
				throw new \InvalidArgumentException("Using SQLite but given configuration contains no or an invalid database name.");
			}

			/* NOTE: We may as well need to check the directory holding the
			 * database file according to this:
			 * http://stackoverflow.com/questions/61085/sqlite-php-read-only
			 */
			if (!\Sauce\Path::check($config->name, 'f', 'rw')) {
				throw new \InvalidArgumentException("Using SQLite but given database is not readable and writable: {$config->name}");
			}
		}

		if ('mysql' == $config->type || 'pgsql' == $config->type) {
			if (empty($config->host) || !is_a_string($config->host)) {
				throw new \InvalidArgumentException("Using MySQL or PostgreSQL but no or an invalid database host was given: {$config->host}");
			}

			if (empty($config->name) || !is_a_string($config->name)) {
				throw new \InvalidArgumentException("Using MySQL or PostgreSQL but no or an invalid database name was given.");
			}
		}

		return $config;
	}
}
