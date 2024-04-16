<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use Generator;
use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Key\FixtureKey;
use WebChemistry\Fixtures\Reference\ReferenceRepository;
use WebChemistry\Fixtures\Utility\FixtureTools;
use WebChemistry\Fixtures\Utility\Range;

abstract class Fixture
{

	protected Faker $faker;

	protected ReferenceRepository $ref;

	protected FixtureTools $tools;

	protected int|Range|null $repeatLoad = null;

	abstract public function getKey(): FixtureKey;

	/**
	 * @return iterable<object>
	 */
	abstract public function load(): iterable;

	final public function init(FixtureTools $tools): void
	{
		$tools = $this->configureTools($tools);

		$this->initialize($tools);
	}

	/**
	 * @return iterable<object>
	 */
	final public function run(): iterable
	{
		if ($this->repeatLoad) {
			yield from $this->repeatGenerator($this->repeatLoad, $this->load(...));
		} else {
			yield from $this->load();
		}
	}

	public function configureTools(FixtureTools $tools): FixtureTools
	{
		return $tools;
	}

	public function initialize(FixtureTools $tools): void
	{
		$this->faker = $tools->faker;
		$this->ref = $tools->ref;
		$this->tools = $tools;
	}

	/**
	 * @return string[]
	 */
	public function dependencies(): array
	{
		return [];
	}

	/**
	 * @template T of object
	 * @param callable(): T $callback
	 * @return Generator<T>
	 */
	protected function repeat(int|Range $times, callable $callback): Generator
	{
		for ($i = 0; $i < Range::toInteger($times); $i++) {
			yield $callback();
		}
	}

	/**
	 * @template T of object
	 * @param callable(): Generator<T> $callback
	 * @return Generator<T>
	 */
	protected function repeatGenerator(int|Range $times, callable $callback): Generator
	{
		for ($i = 0; $i < Range::toInteger($times); $i++) {
			yield from $callback();
		}
	}

}
