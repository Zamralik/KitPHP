<?php

namespace KitPHP\Twig\TwigTest;

use \Twig\Compiler;
use \Twig\Node\Expression\Test\DefinedTest;

use \KitPHP\Twig\Utility;

class Filled extends DefinedTest
{
	public function compile(Compiler $compiler)
	{
		$node = $this->getNode('node');
		$chain = Utility::FlattenChain($node);

		if (isset($chain))
		{
			$var = '$context["' . implode('"]["', $chain) . '"]';
			$compiler->raw("(!empty({$var}) || isset({$var}) && {$var} === '0')");
		}
		else
		{
			parent::compile($compiler);
		}
	}
}
