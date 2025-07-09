<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use Generator;
use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Reference\FixtureLazyReference;
use WebChemistry\Fixtures\Reference\ReferenceRepository;
use WebChemistry\Fixtures\Utility\Range;

/**
 * @template T of object
 * @implements Fixture<T>
 */
abstract class BaseFixture implements Fixture
{

	protected readonly Faker $faker;

	protected readonly ReferenceRepository $ref;

	protected readonly Hydrator\Hydrator $hydrator;

	protected ?Faker $uniqueFaker = null;

	final public function __construct(
		FixtureServices $services,
	)
	{
		$this->faker = $services->faker;
		$this->ref = $services->ref;
		$this->hydrator = $services->hydrator;
	}

	/**
	 * @param array<string, mixed> $defaults
	 * @return Fixture<T>
	 */
	public static function new(array $defaults = []): Fixture
	{
		$fixture = new static(FixtureServices::defaults());

		if ($defaults) {
			$fixture = new FixtureWithDefaults($fixture, $defaults);
		}

		return $fixture;
	}

	/**
	 * @return class-string<T>
	 */
	abstract protected function getClassName(): string;

	/**
	 * @return iterable<T>
	 */
	abstract protected function load(): iterable;

	public function getSqlFile(): ?string
	{
		return null;
	}

	protected function repeatCount(): int|Range
	{
		return 1;
	}

	/**
	 * @return iterable<T>
	 */
	final public function run(): iterable
	{
		yield from $this->repeatGenerator($this->repeatCount(), $this->load(...));
	}

	/**
	 * @return list<class-string<Fixture<covariant object>>>
	 */
	public function dependencies(): array
	{
		return [];
	}

	/**
	 * @param array<string, mixed> $values
	 * @return T
	 */
	final public function make(array $values = []): object
	{
		$values = array_merge($this->defaults(), $values);
		$specialMapping = $this->getSpecialMapping();

		foreach ($values as &$value) {
			if ($value instanceof FixtureLazyReference) {
				$value = $value->resolve();
			}
		}

		foreach ($specialMapping as $field => $fn) {
			if (array_key_exists($field, $values)) {
				$values[$field] = $fn($values[$field]);
			}
		}

		return $this->hydrator->hydrate($this->getClassName(), $values);
	}

	/**
	 * @param array<string, mixed> $values
	 * @return T[]
	 */
	final public function makeMany(int $times, array $values = []): array
	{
		$entities = [];

		for ($i = 0; $i < $times; $i++) {
			$entities[] = $this->make($values);
		}

		return $entities;
	}

	/**
	 * @return array<string, mixed>
	 */
	abstract protected function defaults(): array;

	/**
	 * @return array<string, callable>
	 */
	protected function getSpecialMapping(): array
	{
		return [];
	}

	/**
	 * @param array<string, mixed> $values
	 * @return T
	 */
	protected function create(array $values): object
	{
		return $this->hydrator->hydrate($this->getClassName(), $values);
	}

	protected function getUniqueFaker(): Faker
	{
		return $this->uniqueFaker ??= $this->faker->withUnique(true);
	}

	/**
	 * @template TV of object
	 * @param callable(): TV $callback
	 * @return Generator<TV>
	 */
	protected function repeat(int|Range $times, callable $callback): Generator
	{
		for ($i = 0; $i < Range::toInteger($times); $i++) {
			yield $callback();
		}
	}

	/**
	 * @template TV of object
	 * @param callable(): iterable<TV> $callback
	 * @return Generator<TV>
	 */
	protected function repeatGenerator(int|Range $times, callable $callback): Generator
	{
		for ($i = 0; $i < Range::toInteger($times); $i++) {
			yield from $callback();
		}
	}

}
