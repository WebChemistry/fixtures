<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use InvalidArgumentException;

final class FixtureRegistry
{

	/** @var array<string, Fixture<object>> */
	private array $fixtures = [];

	/** @var array<class-string<Fixture<object>>, Fixture<object>> */
	private array $fixtureClassIndex = [];

	/** @var iterable<Fixture<object>> */
	private iterable $unprocessed;

	/**
	 * @param iterable<Fixture<object>> $fixtures
	 */
	public function __construct(iterable $fixtures)
	{
		$this->unprocessed = $fixtures;
	}

	/**
	 * @return Fixture<object>[]
	 */
	public function getAll(): array
	{
		$this->process();

		return $this->fixtures;
	}

	/**
	 * @param string[] $keys
	 * @return Fixture<object>[]
	 */
	public function getByKeysWithDependencies(array $keys): array
	{
		$this->process();

		$fixtures = [];

		foreach ($keys as $key) {
			if (!isset($this->fixtures[$key])) {
				throw new InvalidArgumentException(sprintf('Fixture with key %s not found.', $key));
			}

			$fixtures[$key] = $this->fixtures[$key];

			foreach ($this->fixtures[$key]->dependencies() as $dependency) {
				$dependencyFixture = $this->getByClassName($dependency);

				if (!$dependencyFixture) {
					throw new InvalidArgumentException(sprintf('Dependency %s for fixture %s not found.', $dependency, $key));
				}

				$fixtures[$dependencyFixture->getKey()->getName()] = $dependencyFixture;
			}
		}

		return $fixtures;
	}

	/**
	 * @template T of Fixture
	 * @param class-string<T> $id
	 * @return T|null
	 */
	public function getByClassName(string $id): ?Fixture
	{
		$this->process();

		/** @var T|null */
		return $this->fixtureClassIndex[$id] ?? null;
	}

	private function process(): void
	{
		foreach ($this->unprocessed as $fixture) {
			$key = $fixture->getKey()->getName();

			if (isset($this->fixtures[$key])) {
				throw new InvalidArgumentException(sprintf('Fixture with key %s already exists.', $key));
			}

			if (isset($this->fixtureClassIndex[$fixture::class])) {
				throw new InvalidArgumentException(sprintf('Fixture with class %s already exists.', $fixture::class));
			}

			$this->fixtures[$key] = $fixture;
			$this->fixtureClassIndex[$fixture::class] = $fixture;
		}

		$this->unprocessed = [];
	}

}
