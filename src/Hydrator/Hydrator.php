<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Hydrator;

interface Hydrator
{

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param mixed[] $values
	 * @return T
	 */
	public function hydrate(string $className, array $values): object;

}
