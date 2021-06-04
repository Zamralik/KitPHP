<?php

namespace KitPHP\Twig;

use \Twig\Node\Expression\AbstractExpression;
use \Twig\Node\Expression\ArrayExpression;
use \Twig\Node\Expression\ConstantExpression;
use \Twig\Node\Expression\GetAttrExpression;
use \Twig\Node\Expression\NameExpression;

class Utility
{
	/**
	* @param AbstractExpression $expression
	* @return bool
	*/
	static private function ValidateChainLink(AbstractExpression $expression)
	{
		if (!($expression instanceof GetAttrExpression))
		{
			return false;
		}

		$node = $expression->getNode('node');

		if (
			!($node instanceof NameExpression)
			&&
			!($node instanceof GetAttrExpression)
		)
		{
			return false;
		}

		$attribute = $expression->getNode('attribute');

		if (!($attribute instanceof ConstantExpression))
		{
			return false;
		}

		$arguments = $expression->getNode('arguments');

		if (!($arguments instanceof ArrayExpression))
		{
			return false;
		}

		return true;
	}

	/**
	* @param AbstractExpression $expression
	* @return array|null
	*/
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
				if (!self::ValidateChainLink($expression))
				{
					return null;
				}

				$node = $expression->getNode('node');
				$attribute = $expression->getNode('attribute');

				$chain[] = $attribute->getAttribute('value');

				if ($node instanceof NameExpression)
				{
					$chain[] = $node->getAttribute('name');
					$chain = array_reverse($chain);

					return $chain;
				}

				$expression = $node;
			}
			while ($expression instanceof GetAttrExpression);
		}

		return null;
	}
}
