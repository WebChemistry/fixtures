<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use RuntimeException;

/**
 * @template T of object
 * @implements Fixture<T>
 */
abstract class SqlOnlyFixture implements Fixture
{

	abstract public function getSqlFile(): string;

	/**
	 * @return list<class-string<Fixture<object>>>
	 */
	public function dependencies(): array
	{
		return [];
	}

	final public function run(): iterable
	{
		return [];
	}

	final public function make(array $values = []): object
	{
		throw new RuntimeException('SQL only fixture cannot be used to create entities');
	}

	final public function makeMany(int $times, array $values = []): array
	{
		throw new RuntimeException('SQL only fixture cannot be used to create entities');
	}

}
