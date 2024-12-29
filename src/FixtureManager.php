<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use Nette\Utils\Arrays;
use WebChemistry\Fixtures\Bridge\Doctrine\Record\RecordManagerPersister;
use WebChemistry\Fixtures\Record\RecordManager;
use WebChemistry\Fixtures\Reference\ReferenceRepository;
use WebChemistry\Fixtures\Sorter\DependencySorter;

final class FixtureManager
{

	/** @var callable[] */
	public array $onFixtureLoading = [];

	/** @var callable[] */
	public array $onFixtureInitializing = [];

	/** @var callable[] */
	public array $onFixtureFinished = [];

	/** @var callable[] */
	public array $onRecord = [];

	public function __construct(
		private RecordManagerPersister $persister,
	)
	{
	}

	/**
	 * @param RecordManager $recordManager
	 * @param Fixture<object>[] $fixtures
	 * @return string[]
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
	 * @param Fixture<object>[] $fixtures
	 */
	public function load(array $fixtures): void
	{
		$reference = new ReferenceRepository();
		$sorted = DependencySorter::sortDependencies($fixtures);

		foreach ($sorted as $fixture) {
			Arrays::invoke($this->onFixtureInitializing, $fixture);
		}

		$pos = 1;

		foreach ($sorted as $fixture) {
			Arrays::invoke($this->onFixtureLoading, $fixture, $pos);

			$count = 0;
			foreach ($fixture->run() as $object) {
				Arrays::invoke($this->onRecord, $object);

				$this->persister->persist($object);

				$reference->addProcessed($object);

				$count++;
			}

			Arrays::invoke($this->onFixtureFinished, $fixture, $count);

			$pos++;
		}

		$this->persister->flush();
	}

}
