<?php

namespace KitPHP\Twig;

use \Twig\Node\Expression\AbstractExpression;
use \Twig\Node\Expression\ArrayExpression;
use \Twig\Node\Expression\ConstantExpression;
use \Twig\Node\Expression\GetAttrExpression;
use \Twig\Node\Expression\NameExpression;

class Utility
{
	static public function FlattenChain(AbstractExpression $expression)
	{
		if ($expression instanceof NameExpression)
		{
			return [$expression->getAttribute('name')];
		}

		if ($expression instanceof GetAttrExpression)
		{
			$chain = [];

			do
			{
				$node = $expression->getNode('node');
				$attribute = $expression->getNode('attribute');
				$arguments = $expression->getNode('arguments');

				if (
					(
						$node instanceof NameExpression
						||
						$node instanceof GetAttrExpression
					)
					&&
					$attribute instanceof ConstantExpression
					&&
					$arguments instanceof ArrayExpression
				)
				{
					$chain[] = $attribute->getAttribute('value');

					if ($node instanceof NameExpression)
					{
						$chain[] = $node->getAttribute('name');
					}

					$expression = $node;
				}
				else
				{
					return null;
				}
			}
			while ($expression instanceof GetAttrExpression);

			$chain = array_reverse($chain);

			return $chain;
		}

		return null;
	}
}
