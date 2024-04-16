<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use InvalidArgumentException;
use Nette\Utils\Arrays;
use WebChemistry\Fixtures\Config\FixtureConfig;
use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Record\RecordManager;
use WebChemistry\Fixtures\Reference\ReferenceRepository;
use WebChemistry\Fixtures\Sorter\DependencySorter;
use WebChemistry\Fixtures\Utility\FixtureTools;

final class FixtureManager
{

	private Faker $faker;

	/** @var callable[] */
	public array $onFixtureLoading = [];

	/** @var callable[] */
	public array $onFixtureInitializing = [];

	/** @var callable[] */
	public array $onFixtureFinished = [];

	/** @var callable[] */
	public array $onRecord = [];

	/**
	 * @param array<string, array<string, mixed>> $config
	 */
	public function __construct(
		?Faker $faker = null,
		private array $config = [],
	)
	{
		$this->faker = $faker ?? new Faker();
	}

	/**
	 * @param RecordManager $recordManager
	 * @param Fixture[] $fixtures
	 */
	public function validate(RecordManager $recordManager, array $fixtures): array
	{
		$errors = [];

		foreach ($fixtures as $fixture) {
			if (!$recordManager->isEmpty($fixture)) {
				$errors[] = sprintf('Fixture [%s] %s is not empty.', $fixture->getKey()->getName(), $fixture::class);
			}
		}

		return $errors;
	}

	/**
	 * @param Fixture[] $fixtures
	 */
	public function load(RecordManager $recordManager, array $fixtures): void
	{
		$reference = new ReferenceRepository();
		$sorted = DependencySorter::sortDependencies($fixtures);

		foreach ($sorted as $fixture) {
			$rawConfig = $this->config[$fixture->getKey()->getName()] ?? [];

			if (!is_array($rawConfig)) {
				throw new InvalidArgumentException(sprintf('Config for fixture %s must be array.', $fixture->getKey()->getName()));
			}

			Arrays::invoke($this->onFixtureInitializing, $fixture);

			$fixture->init(new FixtureTools($reference, $this->faker, new FixtureConfig($rawConfig)));
		}

		$pos = 1;

		foreach ($sorted as $fixture) {
			Arrays::invoke($this->onFixtureLoading, $fixture, $pos);

			$count = 0;
			foreach ($fixture->run() as $object) {
				Arrays::invoke($this->onRecord, $object);

				$recordManager->persist($object);

				$reference->addProcessed($object);

				$count++;
			}

			Arrays::invoke($this->onFixtureFinished, $fixture, $count);

			$pos++;
		}

		$recordManager->flush();
	}

}
