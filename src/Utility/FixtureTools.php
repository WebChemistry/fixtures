<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Utility;

use Nette\Schema\Processor;
use Nette\Schema\Schema;
use WebChemistry\Fixtures\Config\FixtureConfig;
use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Reference\ReferenceRepository;

final class FixtureTools
{

	public readonly Faker $uniqueFaker;

	public function __construct(
		public readonly ReferenceRepository $ref,
		public readonly Faker $faker,
		public readonly FixtureConfig $cfg,
	)
	{
		$this->uniqueFaker = $this->faker->withUnique();
	}

	/**
	 * @template T
	 * @return T
	 */
	public function validateConfig(string $key, Schema $schema, mixed $default): mixed
	{
		return (new Processor())->process($schema, $this->cfg->get($key, $default));
	}

}
