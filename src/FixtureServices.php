<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use Doctrine\Persistence\ManagerRegistry;
use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Hydrator\Hydrator;
use WebChemistry\Fixtures\Hydrator\ReflectionHydrator;
use WebChemistry\Fixtures\Reference\ReferenceRepository;

final class FixtureServices
{

	private static ?ManagerRegistry $managerRegistry = null;

	public readonly Faker $faker;

	public function __construct(
		public readonly ReferenceRepository $ref,
		public readonly Hydrator $hydrator,
	)
	{
		$this->faker = new Faker();
	}

	public static function defaults(): self
	{
		$managerRegistry = self::$managerRegistry;
		if ($managerRegistry === null) {
			throw new \LogicException('ManagerRegistry is not set. Use FixtureServices::setManagerRegistryForTests() to set it.');
		}

		return new self(new ReferenceRepository(), new ReflectionHydrator($managerRegistry));
	}

	public static function setManagerRegistryForTests(ManagerRegistry $managerRegistry): void
	{
		self::$managerRegistry = $managerRegistry;
	}

}
