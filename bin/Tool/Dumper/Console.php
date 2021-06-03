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

class Console
{
	static private $SkipNextIndent = false;
	static private $IndentLevel = 0;
	static private $DumpDepth = 0;
	static private $DumpPath;

	static public function PreDump()
	{
		Debug::StopBuffer();
		self::$DumpDepth = Debug::GetMaxDumpDepth();
		self::$IndentLevel = 0;

		if (empty(self::$DumpPath))
		{
			self::$DumpPath = dirname(__FILE__, 2);
		}
	}

	/**
	* @param mixed $arg
	*/
	static public function Dump($arg)
	{
		self::PreDump();
		echo "\n";
		self::$SkipNextIndent = true;

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

				echo 'Called from: ', $file, ' (', $row['line'], ')', "\n";

				break;
			}
		}

		self::DumpSwitch($arg);
		echo "\n";
	}

	/**
	* @param array $methods
	*/
	static public function DumpMethods($methods)
	{
		self::PreDump();

		echo "\n";

		foreach ($methods as $i => $method)
		{
			echo $i, ': ', $method, "\n";
		}

		echo "\n";
	}

	/**
	* @param string $message
	* @param array $trace
	*/
	static public function StackTrace(string $message, array $trace)
	{
		self::PreDump();

		echo "\n", $message, "\n";
		$depth = 0;

		foreach ($trace as $row)
		{
			if (!isset($row['file'], $row['line']))
			{
				continue;
			}

			$file = $row['file'];

			$root_path = Debug::GetRootPath();

			if (isset($root_path))
			{
				$file = str_replace($, '.', $file);
			}

			$file = str_replace('\\', '/', $file);

			echo '#', $depth, ' ', $file, '(', $row['line'], '): ';
			++$depth;

			if (isset($row['function']))
			{
				if (isset($row['class']))
				{
					echo $row['class'], $row['type'];
				}

				echo $row['function'], '(';
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

					if (!empty($args))
					{
						$comma = false;

						foreach ($args as $arg)
						{
							if ($comma)
							{
								echo ', ';
							}
							else
							{
								$comma = true;
							}

							if ($arg->isOptional())
							{
								echo '?';
							}

							echo ($arg->hasType() ? $arg->getType() : 'any'), ' ';

							if ($arg->isPassedByReference())
							{
								echo '&';
							}

							echo '$', $arg->getName();
						}
					}
				}

				echo ')';
			}

			echo "\n";
		}
	}

	static private function DumpIndent()
	{
		if (self::$SkipNextIndent)
		{
			self::$SkipNextIndent = false;
		}
		else
		{
			echo "\n", str_pad('', self::$IndentLevel * 4, ' ');
		}
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
			if (self::$DumpDepth)
			{
				--self::$DumpDepth;
				self::DumpArray($mixed);
				++self::$DumpDepth;
			}
			else
			{
				echo '(...)';
			}
		}
		else
		{
			self::DumpScalar($mixed);
		}
	}

	/**
	* @param resource $resource
	*/
	static private function DumpResource($resource)
	{
		self::DumpIndent();
		echo get_resource_type($resource);
	}

	/**
	* @param object $object
	*/
	static private function DumpObject($object)
	{
		self::DumpIndent();
		echo get_class($object);
		self::DumpIndent();
		echo '{';
		++self::$IndentLevel;

		if ($object instanceof DateTime)
		{
			$tz = date_default_timezone_get();
			date_default_timezone_set('UTC');
			$value = $object->format('Y-m-d H:i:s');
			date_default_timezone_set($tz);
			self::DumpIndent();
			echo '"date": ', $value;
		}
		else
		{
			$properties = Debug::GetObjectMapping($object);

			foreach ($properties as $name => $property)
			{
				$property->setAccessible(true);
				self::DumpIndent();
				echo json_encode($name, \JSON_UNESCAPED), ': ';
				self::$SkipNextIndent = true;
				++self::$IndentLevel;
				self::DumpSwitch($property->getValue($object));
				--self::$IndentLevel;
			}
		}

		--self::$IndentLevel;
		self::DumpIndent();
		echo '}';
	}

	/**
	* @param Throwable $exception
	*/
	static private function DumpException(Throwable $exception)
	{
		while ($exception->getPrevious())
		{
			$exception = $exception->getPrevious();
		}

		$trace = Debug::GetTrace($exception);
		self::DumpIndent();
		echo get_class($exception);
		++self::$IndentLevel;
		self::DumpIndent();
		echo 'Message: ', $exception->getMessage();
		self::DumpIndent();
		echo 'Trace:';
		++self::$IndentLevel;

		foreach ($trace as $index => $row)
		{
			self::DumpIndent();
			echo '#', $index, ' ', $row['file'], '(', $row['line'], ')';

			if (isset($row['function']))
			{
				echo ': ';

				if (isset($row['class']))
				{
					echo $row['class'], $row['type'];
				}

				echo $row['function'], '()';
			}
		}

		--self::$IndentLevel;
		--self::$IndentLevel;
	}

	/**
	* @param array $array
	*/
	static private function DumpArray(array $array)
	{
		ksort($array);
		$length = count($array);

		if ($length > 0)
		{
			self::DumpIndent();
			echo 'Array(', $length, ')';
			self::DumpIndent();
			echo '[';
			++self::$IndentLevel;

			foreach ($array as $key => $row)
			{
				self::DumpIndent();
				echo json_encode($key, \JSON_UNESCAPED), ' => ';
				self::$SkipNextIndent = true;
				++self::$IndentLevel;
				self::DumpSwitch($row);
				// In case no indent occured, reset skip
				self::$SkipNextIndent = false;
				--self::$IndentLevel;
			}

			--self::$IndentLevel;
			self::$SkipNextIndent = false;
			self::DumpIndent();
			echo ']';
		}
		else
		{
			self::DumpIndent();
			echo 'Array(0) []';
		}
	}

	/**
	* @param null|bool|int|float|string $scalar
	*/
	static private function DumpScalar($scalar)
	{
		echo self::DumpIndent();

		if (isset($scalar))
		{
			if (is_string($scalar))
			{
				echo 'String(', mb_strlen($scalar), ') ';
			}

			echo json_encode($scalar, \JSON_UNESCAPED);
		}
		else
		{
			echo 'NULL';
		}
	}
}
