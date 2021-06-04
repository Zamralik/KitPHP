<?php

namespace KitPHP\Twig;

use \Twig\Extension\AbstractExtension;
use \Twig\TwigFunction;
use \Twig\TwigTest;

use \KitPHP\Twig\TwigTest\Existing;
use \KitPHP\Twig\TwigTest\Filled;

class Extension extends AbstractExtension
{
	public function getTests()
	{
		return [
			new TwigTest(
				'existing',
				null,
				[
					'node_class' => Existing::class
				]
			),
			new TwigTest(
				'filled',
				null,
				[
					'node_class' => Filled::class
				]
			)
		];
	}
}
