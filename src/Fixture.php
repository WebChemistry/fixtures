<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use WebChemistry\Fixtures\Key\FixtureKey;

/**
 * @template T of object
 */
interface Fixture
{

	public function getKey(): FixtureKey;

	/**
	 * @return class-string<Fixture<object>>[]
	 */
	public function dependencies(): array;

	public function getSqlFile(): ?string;

	/**
	 * @return iterable<T>
	 */
	public function run(): iterable;

	/**
	 * @param array<string, mixed> $values
	 * @return T
	 */
	public function make(array $values = []): object;

	/**
	 * @param array<string, mixed> $values
	 * @return T[]
	 */
	public function makeMany(int $times, array $values = []): array;

}
