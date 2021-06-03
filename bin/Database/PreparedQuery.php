<?php

namespace KitPHP\Database;

use \PDOStatement;

use \KitPHP\Database\Utility;

final class PreparedQuery
{
	private $statement;

	/**
	* @internal
	*/
	public function __construct(PDOStatement $statement)
	{
		$this->statement = $statement;
	}

	/**
	* @internal
	*/
	public function getStatement(): PDOStatement
	{
		return $this->statement;
	}

	/**
	* Bind values to the prepared query
	*
	* @param array $data
	*/
	public function bind(array $values) : void
	{
		Utility::Bind($this->statement, $values);
	}
}
