<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Bridge\Doctrine\Key;

use WebChemistry\Fixtures\Key\FixtureKey;

final class DoctrineFixtureKey implements FixtureKey
{

	/**
	 * @param class-string $className
	 */
	public function __construct(
		private string $name,
		private string $className,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return class-string
	 */
	public function getClassName(): string
	{
		return $this->className;
	}

}
