<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Hydrator\Hydrator;
use WebChemistry\Fixtures\Hydrator\ReflectionHydrator;
use WebChemistry\Fixtures\Reference\ReferenceRepository;

final readonly class FixtureServices
{

	public Faker $faker;

	public function __construct(
		public ReferenceRepository $ref,
		public Hydrator $hydrator,
	)
	{
		$this->faker = new Faker();
	}

	public static function defaults(): self
	{
		return new self(new ReferenceRepository(), new ReflectionHydrator());
	}

}
