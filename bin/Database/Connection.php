<?php

namespace KitPHP\Database;

use \PDO;
use \PDOStatement;
use \Exception;

use \KitPHP\Database\Utility;
use \KitPHP\Database\PreparedQuery;

final class Connection
{
	private $pdo = null;
	private $defaultDatabaseName = '';

	/**
	* Required: dbname
	* Optional: host, port, username, password
	*
	* @param array $config
	*/
	public function __construct(array $config)
	{
		if (empty($config['dbname']))
		{
			throw new Exception('Missing information: dbname');
		}

		$dbname = $config['dbname'];

		$host = $config['host'] ?? '127.0.0.1';
		$port = $config['port'] ?? '3306';

		$username = $config['username'] ?? '';
		$password = $config['password'] ?? '';

		$this->pdo = new PDO(
			'mysql:charset=UTF8;host='.$host.';port='.$port.';dbname='.$dbname,
			$username,
			$password,
			[
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]
		);

		$this->defaultDatabaseName = $dbname;
	}

	/**
	* @return string
	*/
	public function getDefaultDatabaseName()
	{
		return $this->defaultDatabaseName;
	}

	/**
	* @return int
	*/
	public function getLastInsertId()
	{
		return (int) $this->pdo->lastInsertId();
	}

	/**
	* @param string $query
	* @return PreparedQuery
	*/
	public function prepare(string $query): PreparedQuery
	{
		return new PreparedQuery($this->pdo->prepare($query));
	}

	/**
	* @param PreparedQuery|string $query
	* @param array $values
	* @return PDOStatement
	*/
	private function process($query, array $values = null)
	{
		if (is_string($query))
		{
			if (isset($values))
			{
				$query = Utility::Build($query, null, $values);
			}

			$statement = $this->pdo->prepare($query);
		}
		else
		{
			$statement = $query->getStatement();

			if (isset($values))
			{
				Utility::Bind($statement, $values);
			}
		}

		if (!$statement->execute())
		{
			$error = $statement->errorInfo();
			$statement->closeCursor();
			throw new Exception($error[2]);
		}

		return $statement;
	}

	/**
	* @param PreparedQuery|string $query
	* @param array|null $values
	*/
	public function execute($query, array $values = null)
	{
		$statement = $this->process($query, $values);
		$statement->closeCursor();
	}

	/**
	* @param PreparedQuery|string $query
	* @param array|null $values
	* @return int
	*/
	public function modify($query, array $values = null)
	{
		$statement = $this->process($query, $values);
		$count = $statement->rowCount();
		$statement->closeCursor();

		return $count;
	}

	/**
	* @param PreparedQuery|string $query
	* @param array|null $values
	* @return int
	*/
	public function insert($query, array $values = null)
	{
		$statement = $this->process($query, $values);
		$statement->closeCursor();

		return $this->getLastInsertId();
	}

	/**
	* @param PreparedQuery|string $query
	* @param array|null $values
	* @return array
	*/
	public function getMatrix($query, array $values = null)
	{
		$statement = $this->process($query, $values);
		$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
		$statement->closeCursor();

		return empty($rows) ? [] : $rows;
	}

	/**
	* @param PreparedQuery|string $query
	* @param array|null $values
	* @return array
	*/
	public function getColumn($query, array $values = null): array
	{
		$statement = $this->process($query, $values);
		$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
		$statement->closeCursor();

		return empty($rows) ? [] : array_map('current', $rows);
	}

	/**
	* @param PreparedQuery|string $query
	* @param array|null $values
	* @return array
	*/
	public function getRow($query, array $values = null)
	{
		$statement = $this->process($query, $values);
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		$statement->closeCursor();

		return empty($row) ? null : $row;
	}

	/**
	* @param PreparedQuery|string $query
	* @param array|null $values
	* @return mixed
	*/
	public function getValue($query, array $values = null)
	{
		$statement = $this->process($query, $values);
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		$statement->closeCursor();

		return empty($row) ? null : current($row);
	}

	/**
	* @return bool
	*/
	public function inTransaction()
	{
		return $this->pdo->inTransaction();
	}

	/**
	* @return bool
	*/
	public function startTransaction()
	{
		return $this->pdo->beginTransaction();
	}

	/**
	* @return bool
	*/
	public function confirmTransaction()
	{
		return $this->pdo->commit();
	}

	/**
	* @return bool
	*/
	public function cancelTransaction()
	{
		return $this->pdo->rollBack();
	}

	/**
	* @param array $type_map
	* @param array $raw_data
	* @return array
	*/
	public function mapping($type_map, $raw_data)
	{
		return Utility::Mapping($type_map, $raw_data);
	}

	/**
	* @param string $path
	*/
	public function executeFile(string $path)
	{
		$path = realpath($path);

		if (empty($path))
		{
			throw new Exception('File not found');
		}
		elseif (!is_file($path))
		{
			throw new Exception('Directory found');
		}
		elseif (!is_readable($path))
		{
			throw new Exception('File is not readable');
		}
		elseif (pathinfo($path, \PATHINFO_EXTENSION) !== 'sql')
		{
			throw new Exception('File does not have the SQL extension');
		}

		// Will use ; as initial delimiter
		$content = ';' . file_get_contents($path);
		// Remove comments
		$content = preg_replace('~/\*.*\*/|(//|-- |#).*\n~sU', '', $content);
		// Split content at each delimiter change
		$content = preg_split('~\s+DELIMITER\s+~', $content);

		foreach ($content as $section)
		{
			$section = trim($section);
			// Retrieve delimiter for the queries in this section
			$delimiter = $section[0];
			// Remove delimiter character
			$section = substr($section, 1);
			// Split section into queries
			$queries = explode($delimiter, $section);

			foreach ($queries as $query)
			{
				$query = trim($query);

				if (!empty($query))
				{
					$this->pdo->exec($query);
					$error = $this->pdo->errorInfo();

					if (!empty($error[2]))
					{
						throw new Exception($error[2]);
					}
				}
			}
		}
	}
}
