<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use WebChemistry\Fixtures\Key\FixtureKey;

/**
 * @template T of object
 * @implements Fixture<T>
 */
final readonly class FixtureWithDefaults implements Fixture
{

	/**
	 * @param Fixture<T> $fixture
	 * @param array<string, mixed> $defaults
	 */
	public function __construct(
		private Fixture $fixture,
		private array $defaults,
	)
	{
	}

	public function getKey(): FixtureKey
	{
		return $this->fixture->getKey();
	}

	public function getSqlFile(): ?string
	{
		return $this->fixture->getSqlFile();
	}

	public function dependencies(): array
	{
		return $this->fixture->dependencies();
	}

	public function run(): iterable
	{
		yield from $this->fixture->run();
	}

	public function make(array $values = []): object
	{
		return $this->fixture->make(array_merge($this->defaults, $values));
	}

	public function makeMany(int $times, array $values = []): array
	{
		return $this->fixture->makeMany($times, array_merge($this->defaults, $values));
	}

}
