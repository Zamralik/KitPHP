<?php

namespace KitPHP\Tool;

use \Exception;
use \Throwable;
use \ReflectionObject;
use \ReflectionClass;
use \ReflectionFunction;

use \KitPHP\Tool\Dumper\Console;
use \KitPHP\Tool\Dumper\Web;

final class Debug
{
	static private $RootPath = null;
	static private $IgnoreBuffer = true;
	static private $WebContext = true;
	static private $DumpMaxDepth = 4;

	/**
	* @param int $depth
	*/
	static public function SetMaxDumpDepth(int $depth)
	{
		self::$MaxDumpDepth = $depth;
	}

	/**
	* @return int
	*/
	static public function GetMaxDumpDepth()
	{
		return self::$MaxDumpDepth;
	}

	/**
	* @param string $path
	*/
	static public function SetRootPath(string $path)
	{
		self::$RootPath = $path;
	}

	/**
	* @return string
	*/
	static public function GetRootPath()
	{
		return self::$RootPath;
	}

	/**
	* @param bool $flag
	*/
	static public function SetIgnoreBuffer(bool $flag)
	{
		self::$IgnoreBuffer = $flag;
	}

	static public function setModeConsole()
	{
		self::$WebContext = false;
	}

	static public function setModeWeb()
	{
		self::$WebContext = true;
	}

	static public function StopBuffer()
	{
		if (self::$IgnoreBuffer)
		{
			$level = ob_get_level();

			for ($i = 0; $i < $level; ++$i)
			{
				ob_end_clean();
			}
		}
	}

	/**
	* @param mixed ...$args
	*/
	static public function Dump()
	{
		$args = func_get_args();

		if (self::$WebContext)
		{
			foreach ($args as $arg)
			{
				if ($arg instanceof Throwable)
				{
					while ($arg->getPrevious())
					{
						$arg = $arg->getPrevious();
					}

					Web::StackTrace($arg->getMessage(), self::GetTrace($arg));
				}
				else
				{
					Web::Dump($arg, true);
				}
			}
		}
		else
		{
			foreach ($args as $arg)
			{
				if ($arg instanceof Throwable)
				{
					while ($arg->getPrevious())
					{
						$arg = $arg->getPrevious();
					}

					Console::StackTrace($arg->getMessage(), self::GetTrace($arg));
				}
				else
				{
					Console::Dump($arg, true);
				}
			}
		}
	}

	/**
	* @param object $object
	*/
	static public function DumpAncestry($object)
	{
		$mirror = self::GetClassMirror($object);
		$ancestry = [];

		while ($mirror)
		{
			$ancestry[] = [
				'name' => $mirror->getShortName(),
				'fullpath' => $mirror->getName()
			];

			$mirror = $mirror->getParentClass();
		}

		self::Dump($ancestry);
	}

	/**
	* @param object $object
	*/
	static public function DumpMethods($object)
	{
		$methods = get_class_methods($object);
		sort($methods);

		if (self::$WebContext)
		{
			Web::DumpMethods($methods);
		}
		else
		{
			Console::DumpMethods($methods);
		}
	}

	/**
	* Print the stacktrace where it was called
	*/
	static public function StackTrace()
	{
		$exception = new Exception('DEBUG TRACE');
		$message = $exception->getMessage();
		$trace = $exception->getTrace();
		array_shift($trace);

		if (self::$WebContext)
		{
			Web::StackTrace($message, $trace);
		}
		else
		{
			Console::StackTrace($message, $trace);
		}
	}

	/**
	* @param object|string $object
	* @return ReflectionObject|ReflectionClass
	*/
	static public function GetClassMirror($object)
	{
		if (is_object($object))
		{
			return new ReflectionObject($object);
		}
		else
		{
			return new ReflectionClass($object);
		}
	}

	/**
	* @param string $name
	*/
	static public function LocateFunction(string $name)
	{
		try
		{
			$rf = new ReflectionFunction($name);

			self::Dump([
				'function' => $name,
				'file' => $rf->getFileName(),
				'line' => $rf->getStartLine()
			]);
		}
		catch (Throwable $ex)
		{
			self::Dump($ex->getMessage());
		}
	}

	/**
	* @param object $object
	* @param string $name
	*/
	static public function LocateMethod($object, string $name)
	{
		try
		{
			$rc = self::GetClassMirror($object);
			$rm = $rc->getMethod($name);
			$rc = $rm->getDeclaringClass();

			self::Dump([
				'method' => $name,
				'class' => $rc->getName(),
				'file' => $rm->getFileName(),
				'line' => $rm->getStartLine()
			]);
		}
		catch (Throwable $ex)
		{
			self::Dump($ex->getMessage());
		}
	}

	/**
	* @param object $object
	*/
	static public function LocateClass($object)
	{
		try
		{
			$rc = self::GetClassMirror($object);

			self::Dump([
				'class' => $rc->getName(),
				'file' => $rc->getFileName()
			]);
		}
		catch (Throwable $ex)
		{
			self::Dump($ex->getMessage());
		}
	}

	/**
	* @param object $object
	* @param string $name
	*/
	static public function LocateProperty($object, string $name)
	{
		try
		{
			$rc = self::GetClassMirror($object);
			$rp = $rc->getProperty($name);
			$rc = $rp->getDeclaringClass();

			self::Dump([
				'property' => $name,
				'class' => $rc->getName(),
				'file' => $rc->getFileName()
			]);
		}
		catch (Throwable $ex)
		{
			self::Dump($ex->getMessage());
		}
	}

	/**
	* @param object $object
	* @param string $property
	* @return mixed
	*/
	static public function ForceGetter($object, string $property)
	{
		$rc = new ReflectionObject($object);
		$rp = $rc->getProperty($property);
		$rp->setAccessible(true);

		return $rp->getValue($object);
	}

	/**
	* @param Throwable $error
	* @param string|null $base_path
	* @return array
	*/
	static public function GetTrace(Throwable $error, string $base_path = null)
	{
		$formatted_trace = [];

		$trace = $error->getTrace();

		array_unshift(
			$trace,
			[
				'file' => $error->getFile(),
				'line' => $error->getLine()
			]
		);

		foreach ($trace as &$row)
		{
			if (isset($row['file'], $row['line']))
			{
				if (isset($base_path))
				{
					$file = str_replace($base_path, '.', $row['file']);
				}

				$file = str_replace('\\', '/', $file);
				$trace['file'] = $file;

				$formatted_trace[] = $row;
			}
		}

		return $formatted_trace;
	}

	/**
	* @param Throwable $error
	* @return string
	*/
	static public function SerializeError(Throwable $error)
	{
		$full_class = get_class($error);
		$message = $error->getMessage();
		$trace = self::GetPrintableTrace($error);

		return "Throw {$full_class}\nMessage: {$message}\nTrace:\n{$trace}";
	}

	/**
	* @param Throwable $error
	* @param bool $merge
	* @return string|array
	*/
	static public function GetPrintableTrace(Throwable $error, $merge = true)
	{
		$trace = self::GetTrace($error);

		foreach ($trace as $index => &$row)
		{
			$string = '#' . $index  . ' ' . $row['file'] . '(' . $row['line'] . ')';

			if (isset($row['function']))
			{
				$string .= ': ';

				if (isset($row['class']))
				{
					$string .= $row['class'] . $row['type'];
				}

				$string .= $row['function'] . '()';
			}

			$row = $string;
		}
		unset($row);

		if ($merge)
		{
			$trace = implode("\n", $trace) . "\n";
		}

		return $trace;
	}

	/**
	* @param object $object
	* @return array
	*/
	static public function GetObjectMapping($object)
	{
		$mirror = new ReflectionObject($object);
		$visibility = ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC;

		$properties = [];

		do
		{
			$unsorted_properties = $mirror->getProperties($visibility);

			foreach ($unsorted_properties as $property)
			{
				$name = $property->getName();

				if (isset($properties[$name]))
				{
					$property_name = $name;
					$short_name = $mirror->getShortName();
					$name = "{$short_name}->{$property_name}";

					if (isset($properties[$name]))
					{
						$long_name = $mirror->getName();
						$name = "{$long_name}->{$property_name}";
					}
				}

				$properties[$name] = $property;
			}

			$visibility = ReflectionProperty::IS_PRIVATE;
			$mirror = $mirror->getParentClass();
		}
		while ($mirror);

		ksort($properties);

		return $properties;
	}
}
