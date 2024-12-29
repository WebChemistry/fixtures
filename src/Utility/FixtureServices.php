<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Utility;

use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Reference\ReferenceRepository;

final readonly class FixtureServices
{

	public function __construct(
		public ReferenceRepository $ref,
		public Faker $faker,
	)
	{
	}

}
