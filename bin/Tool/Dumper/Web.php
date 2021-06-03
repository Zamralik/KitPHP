<?php

namespace KitPHP\Tool\Dumper;

use \Throwable;
use \Exception;
use \DateTime;
use \ReflectionFunction;
use \ReflectionMethod;
use \ReflectionProperty;
use \ReflectionObject;
use \ReflectionClass;

use \KitPHP\Debug;

class WebDumper
{
	static private $DumpResources = true;
	static private $DumpDepth = 0;
	static private $DumpPath;

	static private function PreDump()
	{
		Debug::StopBuffer();

		if (self::$DumpResources)
		{
			self::DumpStyle();
			self::DumpScript();
			self::$DumpResources = false;
			self::$DumpPath = dirname(__FILE__, 2);
		}

		self::$DumpDepth = Debug::GetMaxDumpDepth();
	}

	/**
	* @param mixed $arg
	*/
	static public function Dump($arg)
	{
		self::PreDump();
		echo '<dump-root><dump-wrapper><dump-container>';

		try
		{
			throw new Exception();
		}
		catch (Exception $exception)
		{
			$trace = $exception->getTrace();
			$root_path = Debug::GetRootPath();

			foreach ($trace as $row)
			{
				if (
					empty($row['file'])
					||
					empty($row['line'])
					||
					strpos($row['file'], self::$DumpPath) === 0
					||
					(
						isset($root_path)
						&&
						strpos($row['file'], $root_path) !== 0
					)
				)
				{
					continue;
				}

				$file = $row['file'];

				if (isset($root_path))
				{
					$file = str_replace($, '.', $file);
				}

				$file = str_replace('\\', '/', $file);

				echo '<dump-trace><table><caption>Called from</caption><tbody><tr><td>';
				echo $file, '</td><td>(', $row['line'];
				echo ')</td></tr></tbody></table></dump-trace>';

				break;
			}
		}

		self::DumpSwitch($arg);
		echo '</dump-container></dump-wrapper></dump-root>';
	}

	/**
	* @param array $methods
	*/
	static public function DumpMethods($methods)
	{
		self::PreDump();
		echo '<dump-root><dump-wrapper><dump-container><dump-array><table><caption>', count($methods), ' Methods</caption><tbody>';

		foreach ($methods as $i => $method)
		{
			echo '<tr><td><dump-key>', $i, '</dump-key></td><td><dump-scalar>', $method, '</dump-scalar></td></tr>';
		}

		echo '</tbody></table></dump-array></dump-container></dump-wrapper></dump-root>';
	}

	/**
	* @param string $message
	* @param array $trace
	*/
	static public function StackTrace(string $message, array $trace)
	{
		self::PreDump();
		$root = realpath(__DIR__ . '/../../../..');

		echo '<dump-root><dump-wrapper><dump-container>';
		echo '<dump-trace><table><caption>', htmlspecialchars($message), '</caption><tbody>';

		foreach ($trace as $row)
		{
			if (isset($row['file'], $row['line']))
			{
				echo '<tr>';

				if (isset($row['function']))
				{
					if (isset($row['class']))
					{
						echo '<td>', $row['class'], '</td><td>', $row['type'], '</td>';
					}
					else
					{
						echo '<td colspan="2"></td>';
					}

					echo '<td>', $row['function'], '</td>';
					$mirror = null;

					if (isset($row['class']))
					{
						if (strpos($row['function'], '{closure}') === false)
						{
							$mirror = new ReflectionMethod($row['class'], $row['function']);
						}
					}
					elseif (!in_array($row['function'], ['require', 'require_once', 'include', 'include_once'], true))
					{
						$mirror = new ReflectionFunction($row['function']);
					}

					if (isset($mirror))
					{
						$args = $mirror->getParameters();
						echo '<td><dump-table><table><caption>', count($args), '</caption><tbody hidden>';

						if (!empty($args))
						{
							foreach ($args as $arg)
							{
								echo
								'<tr><td>',
								$arg->getName(),
								'</td><td>',
								($arg->isPassedByReference() ? '&' : ''),
								($arg->hasType() ? $arg->getType() : 'any'),
								'</td><td>';

								if ($arg->isOptional())
								{
									echo ($arg->isDefaultValueAvailable() ? json_encode($arg->getDefaultValue(), \JSON_UNESCAPED) : '?');
								}

								echo '</td></tr>';
							}
						}
						echo '</tbody></table></dump-table></td>';
					}
					else
					{
						echo '<td></td>';
					}
				}
				else
				{
					echo '<td colspan="4"></td>';
				}

				$file = str_replace($root, '.', $row['file']);
				$file = str_replace('\\', '/', $file);
				echo '<td>', $row['line'], '</td><td>', $file, '</td></tr>';
			}
		}

		echo '</tbody></table></dump-trace></dump-container></dump-wrapper></dump-root>';
	}

	static private function DumpStyle()
	{
		echo '
		<style>
		.sf-dump
		{
			display: none !important;
		}
		dump-root
		{
			float: left;
			clear: both;
			max-width: calc(97vw - 10px);
			margin: calc(5px + 1vw);
			padding: 0;
			overflow-x: hidden;
			overflow-y: visible;
			color: black;
		}
		dump-root *
		{
			color: inherit;
		}
		dump-wrapper
		{
			border: 1px solid black;
			padding	: 3px;
			background-color: green;
			overflow-x: hidden;
			overflow-y: visible;
		}
		dump-container
		{
			width: 100vw;
			width: -moz-available;
			max-width: 100%;
			overflow-x: auto;
			overflow-y: visible;
		}
		dump-root,
		dump-wrapper,
		dump-container,
		dump-object,
		dump-trace,
		dump-array,
		dump-table,
		dump-string,
		dump-scalar,
		dump-key
		{
			display: block;
			box-sizing: border-box;
		}
		dump-root caption,
		dump-root th,
		dump-trace,
		dump-key,
		dump-scalar
		{
			font-size: 13px;
			font-family: monospace;
		}
		dump-root caption,
		dump-root th
		{
			font-weight: bold;
		}
		dump-root th,
		dump-root td
		{
			padding: 2px;
			white-space: nowrap;
			vertical-align: top;
		}
		dump-root caption,
		dump-key,
		dump-scalar
		{
			padding: 3px 5px;
		}
		dump-root caption,
		dump-root th,
		dump-key,
		dump-scalar
		{
			white-space: nowrap;
		}
		dump-root table
		{
			border-collapse: collapse;
			width: 100%;
		}
		dump-object,
		dump-trace,
		dump-array,
		dump-table,
		dump-string,
		dump-scalar,
		dump-root tr:nth-child(n+2)
		{
			background-color: whitesmoke;
		}
		dump-root tbody tr:nth-child(odd)
		{
			background-color: lightsteelblue;
		}
		dump-root caption
		{
			text-align: left;
			cursor: pointer;
			background-color: wheat;
			overflow: hidden;
		}
		dump-object,
		dump-trace,
		dump-array,
		dump-table,
		dump-string,
		dump-scalar,
		dump-table > table > thead > tr > th,
		dump-table > table > tbody > tr > td
		{
			border: 1px solid dimgray;
		}
		dump-root thead + tbody,
		dump-root tr:nth-child(n+2)
		{
			border-top: 1px solid dimgray;
		}
		dump-key
		{
			border: 1px solid transparent;
		}
		dump-root th:last-child,
		dump-root td:last-child
		{
			width: 100%;
		}
		dump-trace td:nth-last-child(2)
		{
			text-align: right;
		}
		dump-trace dump-table td:nth-last-child(2)
		{
			text-align: left;
		}
		dump-string dump-scalar
		{
			white-space: pre;
		}
		dump-scalar
		{
			overflow: auto;
		}
		dump-args
		{
			position: relative;
			display: block;
		}
		dump-args ol
		{
			position: absolute;
			top: -95%;
			left: 95%;
			z-index: 1;
			display: none;
			padding: 3px;
			list-style-type: none;
			background-color: green;
		}
		dump-args:hover ol
		{
			display: block;
		}
		dump-args li
		{
			background-color: white;
		}
		dump-args li:nth-child(even)
		{
			background-color: lightsteelblue;
		}
		dump-trace tr:nth-last-child(-n+3) ol
		{
			top: auto;
			bottom: -95%;
		}
		</style>';
	}

	static private function DumpScript()
	{
		echo '<script>
			document.addEventListener(
				"mousedown",
				function (event)
				{
					if (event.button !== 0 || event.altKey)
					{
						return;
					}
					let target = event.target;
					if (target.tagName === "CAPTION" && target.closest("dump-container"))
					{
						event.stopImmediatePropagation();
						event.preventDefault();
						target = target.nextElementSibling;
						if (target)
						{
							const state = !target.hidden;
							if (event.ctrlKey)
							{
								const childs = target.parentNode.querySelectorAll("thead, tbody");
								const length = childs.length;
								let i = 0;
								for (; i < length; ++i)
								{
									childs[i].hidden = state;
								}
							}
							else
							{
								while (target)
								{
									target.hidden = state;
									target = target.nextElementSibling;
								}
							}
						}
					}
				},
				true
			);
		</script>
		';
	}

	/**
	* @param mixed $mixed
	*/
	static private function DumpSwitch($mixed)
	{
		if (is_resource($mixed))
		{
			self::DumpResource($mixed);
		}
		elseif (is_object($mixed))
		{
			if ($mixed instanceof Throwable)
			{
				self::DumpException($mixed);
			}
			elseif (self::$DumpDepth)
			{
				--self::$DumpDepth;
				self::DumpObject($mixed);
				++self::$DumpDepth;
			}
			else
			{
				echo '(...)';
			}
		}
		elseif (is_array($mixed))
		{
			if (self::IsTwoDimensionalArray($mixed))
			{
				self::DumpTable($mixed);
			}
			else
			{
				self::DumpArray($mixed);
			}
		}
		else
		{
			self::DumpScalar($mixed);
		}
	}

	/**
	* @param array $array
	* @return bool
	*/
	static private function IsTwoDimensionalArray(array $array) : bool
	{
		if (empty($array) || !is_array($array) || empty(reset($array)))
		{
			return false;
		}

		$keys = array_keys($array);

		if ($keys !== array_keys($keys))
		{
			return false;
		}

		foreach ($array as $row)
		{
			if (!is_array($row))
			{
				return false;
			}

			$keys = array_keys($row);

			if ($keys !== array_keys($keys))
			{
				return false;
			}
			foreach ($row as $cell)
			{
				if (isset($cell) && !is_scalar($cell))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	* @param resource $resource
	*/
	static private function DumpResource($resource)
	{
		echo '<dump-scalar>Resource: ', get_resource_type($resource), '</dump-scalar>';
	}

	/**
	* @param object $object
	*/
	static private function DumpObject($object)
	{
		echo '<dump-object><table><caption>', get_class($object), '</caption><tbody>';

		if ($object instanceof DateTime)
		{
			$tz = date_default_timezone_get();
			date_default_timezone_set('UTC');
			$value = $object->format('Y-m-d H:i:s');
			date_default_timezone_set($tz);

			echo '<tr><td><dump-key>date</dump-key></td><td>';
			self::DumpScalar($value);
			echo '</td></tr>';
		}
		else
		{
			$properties = Debug::GetObjectMapping($object);

			foreach ($properties as $name => $property)
			{
				$property->setAccessible(true);
				echo '<tr><td><dump-key>', $name, '</dump-key></td><td>';
				self::DumpSwitch($property->getValue($object));
				echo '</td></tr>';
			}
		}

		echo '</tbody></table></dump-object>';
	}

	/**
	* @param Throwable $exception
	*/
	static private function DumpException(Throwable $exception)
	{
		$root = realpath(__DIR__ . '/../../../..');

		while ($exception->getPrevious())
		{
			$exception = $exception->getPrevious();
		}

		$trace = Debug::GetTrace($exception);
		echo '<dump-trace><table><caption>', htmlspecialchars($exception->getMessage()), '</caption><tbody>';

		foreach ($trace as $row)
		{
			if (isset($row['file'], $row['line']))
			{
				echo '<tr>';

				if (isset($row['function']))
				{
					if (isset($row['class']))
					{
						echo '<td>', $row['class'], '</td><td>', $row['type'], '</td>';
					}
					else
					{
						echo '<td colspan="2"></td>';
					}
					echo '<td>', $row['function'], '</td>';
				}
				else
				{
					echo '<td colspan="3"></td>';
				}

				$file = str_replace($root, '.', $row['file']);
				$file = str_replace('\\', '/', $file);
				echo '<td>', $row['line'], '</td><td>', $file, '</td></tr>';
			}
		}

		echo '</tbody></table></dump-trace>';
	}

	/**
	* @param array $array
	*/
	static private function DumpTable(array $table)
	{
		$row = reset($table);
		echo '<dump-table><table><caption>Table (', count($table), ' rows, ', count($row), ' columns)</caption>';
		$columns = array_keys($row);

		if ($columns !== array_keys($columns))
		{
			echo '<thead><tr>';

			foreach ($columns as $column)
			{
				echo '<th>', $column, '</th>';
			}

			echo '</tr></thead>';
		}

		echo '<tbody>';

		foreach ($table as $row)
		{
			echo '<tr>';

			foreach ($row as $cell)
			{
				echo '<td>';
				self::DumpSwitch($cell);
				echo '</td>';
			}

			echo '</tr>';
		}

		echo '</tbody></table></dump-table>';
	}

	/**
	* @param array $array
	*/
	static private function DumpArray(array $array)
	{
		ksort($array);
		echo '<dump-array><table><caption>Array (', count($array), ' rows)</caption><tbody>';

		foreach ($array as $key => $row)
		{
			if ($key === '_view_model')
			{
				continue;
			}
			elseif ($key === '_evented_controller')
			{
				$row = get_class($row);
			}
			elseif ($key === '_template_controller')
			{
				$row[0] = get_class($row[0]);
			}
			elseif ($key === '_site')
			{
				$row = $row->getMainDomain();
			}
			elseif ($key === 'contentDocument')
			{
				$row = $row->getRealFullPath();
			}

			echo '<tr><td><dump-key>', $key, '</dump-key></td><td>';
			self::DumpSwitch($row);
			echo '</td></tr>';
		}

		echo '</tbody></table></dump-array>';
	}

	/**
	* @param null|bool|int|float|string $scalar
	*/
	static private function DumpScalar($scalar)
	{
		if (!is_string($scalar))
		{
			echo '<dump-scalar>', json_encode($scalar, \JSON_UNESCAPED), '</dump-scalar>';
		}
		elseif ($scalar === '')
		{
			echo
				'<dump-string><table><caption>String (0 characters)</caption></table></dump-string>';
		}
		else
		{
			echo
				'<dump-string><table><caption>String (',
				mb_strlen($scalar),
				' characters)</caption><tbody><tr><td><dump-scalar>',
				htmlspecialchars($scalar),
				'</dump-scalar></td></tr></tbody></table></dump-string>';
		}
	}
}
