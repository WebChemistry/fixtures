<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use Generator;
use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Reference\ReferenceRepository;
use WebChemistry\Fixtures\Utility\Range;

abstract class Fixture
{

	protected ReferenceRepository $referenceRepository;

	protected Faker $faker;

	final public function setUp(ReferenceRepository $referenceRepository, Faker $faker)
	{
		$this->referenceRepository = $referenceRepository;
		$this->faker = $faker;
	}

	abstract public function load(): iterable;

	public function dependencies(): array
	{
		return [];
	}

	/**
	 * @template T of object
	 * @param int $times
	 * @param callable(): T $callback
	 * @param mixed[] ...$values
	 * @return Generator<T>
	 */
	protected function repeat(int|Range $times, callable $callback): Generator
	{
		for ($i = 0; $i < Range::toInteger($times); $i++) {
			yield $callback();
		}
	}

}
