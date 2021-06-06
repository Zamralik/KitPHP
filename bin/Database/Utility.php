<?php

namespace KitPHP\Database;

use \Exception;
use \DateTime;
use \DateTimeInterface;
use \PDO;
use \PDOStatement;

class Utility
{
	/**
	* Bind values to a prepared request
	*
	* @internal
	* @param PDOStatement $request
	* @param array $values
	*/
	static public function Bind(PDOStatement $statement, array $values) : void
	{
		foreach ($values as $placeholder => $value)
		{
			if (is_array($value))
			{
				throw new Exception('Attempt to bind an Array');
			}
			elseif (is_object($value))
			{
				throw new Exception('Attempt to bind an Object');
			}
			elseif (is_bool($value))
			{
				$type = PDO::PARAM_BOOL;
			}
			elseif (is_int($value))
			{
				$type = PDO::PARAM_INT;
			}
			elseif (isset($value))
			{
				/* Float type is also a string */
				$type = PDO::PARAM_STR;
			}
			else
			{
				$type = PDO::PARAM_NULL;
			}

			$statement->bindValue($placeholder, $value, $type);
		}
	}

	/**
	* @param string|array $identifier
	* @param bool $is_recursion
	* @return string
	*/
	static public function Escape($identifier, $is_recursion = false)
	{
		if (is_array($identifier))
		{
			if ($is_recursion)
			{
				// Nested arrays are safe, but it might be a mistake
				throw new Exception('Nested array');
			}

			foreach ($identifier as &$item)
			{
				$item = self::Escape($item, true);
			}
			unset($item);

			return implode(', ', $identifier);
		}
		elseif (is_string($identifier) && preg_match('/^\w+(\.\w+)*$/', $identifier))
		{
			return '`'.str_replace('.', '`.`', $identifier).'`';
		}
		else
		{
			// Waiting for: get_debug_type($identifier);
			$type = is_object($identifier) ? get_class($identifier) : gettype($identifier);
			throw new Exception('Invalid identifier: ' . $type);
		}
	}

	/**
	* @param mixed $value
	* @param bool $is_recursion
	* @return string
	*/
	static public function Secure($value, $is_recursion = false)
	{
		if ($value === null)
		{
			return 'NULL';
		}
		elseif (is_bool($value))
		{
			return $value ? 'TRUE' : 'FALSE';
		}
		elseif (is_scalar($value))
		{
			// string, int, float
			return json_encode($value, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_LINE_TERMINATORS);
		}
		elseif (is_array($value))
		{
			if ($is_recursion)
			{
				// Nested arrays are safe, but it might be a mistake
				throw new Exception('Nested array');
			}

			// Waiting for: array_is_list($value)
			$keys = array_keys($value);
			$is_list = ($keys === array_keys($keys));

			if ($is_list)
			{
				foreach ($value as &$item)
				{
					$item = self::Secure($item, true);
				}
				unset($item);

				return implode(', ', $value);
			}
			else
			{
				$output = [];

				foreach ($value as $field => $mixed)
				{
					$output[] = self::Escape($field) . ' = ' . self::Secure($mixed, true);
				}

				return implode(', ', $output);
			}
		}
		elseif (is_object($value))
		{
			if ($value instanceof DateTimeInterface)
			{
				return $value->format('"Y-m-d H:i:s"');
			}
			else
			{
				throw new Exception('Object value');
			}
		}
		else
		{
			throw new Exception('Resource value');
		}
	}

	/**
	* Replace placeholders in the request
	* Supports complex placeholders
	*
	* @param string $query
	* @param array|null $fields
	* @param array|null $values
	* @return string
	*/
	static public function Build(string $query, array $fields = null, array $values = null)
	{
		if (isset($fields))
		{
			foreach ($fields as $placeholder => $field)
			{
				$fields[$placeholder] = self::Escape($field);
			}
		}

		if (isset($values))
		{
			foreach ($values as $placeholder => $value)
			{
				$values[$placeholder] = self::Secure($value);
			}
		}

		return preg_replace_callback(
			'/:(\w+)/',
			function ($matches) use ($fields, $values)
			{
				return $fields[$matches[1]] ?? $values[$matches[1]] ?? $matches[0];
			},
			$query
		);
	}
}
