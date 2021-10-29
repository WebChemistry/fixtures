<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Reference\ReferenceRepository;

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

}
