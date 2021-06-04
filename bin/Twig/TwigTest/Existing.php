<?php

namespace KitPHP\Twig\TwigTest;

use \Twig\Compiler;
use \Twig\Node\Expression\Test\DefinedTest;

use \KitPHP\Twig\Utility;

class Existing extends DefinedTest
{
	public function compile(Compiler $compiler)
	{
		$node = $this->getNode('node');
		$chain = Utility::FlattenChain($node);

		if (isset($chain))
		{
			$var = '$context["' . implode('"]["', $chain) . '"]';
			$compiler->raw('(isset(' . $var . '))');
		}
		else
		{
			parent::compile($compiler);
		}
	}
}
