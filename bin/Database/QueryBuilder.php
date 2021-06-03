<?php

namespace KitPHP\Database;

use \Exception;

class QueryBuilder
{
	private $sections;

	public function __construct(string $base_table)
	{
		$this->sections = [
			'select' => [],
			'from' => [$base_table],
			'where' => [],
			'groupBy' => [],
			'having' => [],
			'orderBy' => []
		];
	}

	public function __toString()
	{
		$sections = [
			(
				empty($this->sections['select'])
				?
				'SELECT *'
				:
				$this->serialize(
					'SELECT ',
					', ',
					$this->sections['select']
				)
			),
			$this->serialize(
				'FROM ',
				' ',
				$this->sections['from']
			),
			$this->serialize(
				'WHERE ',
				' AND ',
				$this->sections['where']
			),
			$this->serialize(
				'GROUP BY ',
				', ',
				$this->sections['groupBy']
			),
			$this->serialize(
				'HAVING ',
				' AND ',
				$this->sections['having']
			),
			$this->serialize(
				'ORDER BY ',
				', ',
				$this->sections['orderBy']
			),
		];

		$query = implode(' ', $sections);

		if (isset($this->limit))
		{
			$query .= "LIMIT {$this->limit}";
		}

		return $query;
	}

	private function serialize($prefix, $glue, $items)
	{
		if (empty($items))
		{
			return '';
		}

		return $prefix . implode($glue, $items);
	}

	/**
	* @param string $item
	* @return self
	*/
	public function addColumn(string $item)
	{
		$this->sections['select'][] = $item;
		return $this;
	}

	/**
	* @param string $item
	* @return self
	*/
	public function addJoin(string $item)
	{
		$this->sections['from'][] = $item;
		return $this;
	}

	/**
	* @param string $item
	* @return self
	*/
	public function addCondition(string $item)
	{
		$this->sections['where'][] = $item;
		return $this;
	}

	/**
	* @param string $item
	* @return self
	*/
	public function addAggregation(string $item)
	{
		$this->sections['groupBy'][] = $item;
		return $this;
	}

	/**
	* @param string $item
	* @return self
	*/
	public function addFilter(string $item)
	{
		$this->sections['having'][] = $item;
		return $this;
	}

	/**
	* @param string $item
	* @return self
	*/
	public function addSorting(string $item)
	{
		$this->sections['orderBy'][] = $item;
		return $this;
	}

	/**
	* @param int $limit
	* @param int|null $offset
	* @return self
	*/
	public function setLimit(int $limit, int $offset = null)
	{
		if (isset($offset))
		{
			$this->limit = "{$offset}, {$limit}";
		}
		else
		{
			$this->limit = $limit;
		}

		return $this;
	}
}
