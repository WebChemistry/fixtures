<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\Fixtures\Fixture;
use WebChemistry\Fixtures\FixtureManager;
use WebChemistry\Fixtures\FixtureRegistry;
use WebChemistry\Fixtures\Record\RecordManager;

final class LoadCommand extends Command
{

	public function __construct(
		private readonly FixtureManager $fixtureManager,
		private readonly FixtureRegistry $fixtureRegistry,
		private readonly RecordManager $recordManager,
	)
	{
		parent::__construct('fixtures:load');
	}

	protected function configure(): void
	{
		$this->setDescription('Creates fixtures')
			->addArgument('fixture', null, 'Load only specific fixture')
			->addOption('load-only', null, null, 'Do not purge database before loading fixtures')
			->addOption('purge-only', null, null, 'Purge database only');
	}

	public function run(InputInterface $input, OutputInterface $output): int
	{
		$fixtureName = $input->getArgument('fixture');
		$loadOnly = (bool) $input->getOption('load-only');
		$purgeOnly = (bool) $input->getOption('purge-only');

		if (is_string($fixtureName) && $fixtureName) {
			$fixtures = $this->fixtureRegistry->getByKeysWithDependencies(
				array_map(trim(...), explode(',', $fixtureName)),
			);
		} else {
			$fixtures = $this->fixtureRegistry->getAll();
		}

		$fixtureNames = implode(', ', array_map(
			fn (Fixture $fixture) => $fixture->getKey()->getName(),
			$fixtures,
		));

		$output->writeln(sprintf('Fixtures to load: %s', $fixtureNames));

		if ($purgeOnly) {
			return $this->purge($output, $fixtures) ? self::SUCCESS : self::FAILURE;
		}

		if ($loadOnly) {
			return $this->load($output, $fixtures) ? self::SUCCESS : self::FAILURE;
		}

		if (!$this->purge($output, $fixtures)) {
			return self::FAILURE;
		}

		if (!$this->load($output, $fixtures)) {
			return self::FAILURE;
		}

		return self::SUCCESS;
	}

	/**
	 * @param Fixture<object>[] $fixtures
	 */
	private function purge(OutputInterface $output, array $fixtures): bool
	{
		$output->writeln('Purging fixtures.');

		$tableNames = $this->recordManager->purge($fixtures);

		foreach ($tableNames as $tableName) {
			$output->writeln(sprintf('Purged <info>%s</info> table.', $tableName));
		}

		$output->writeln('<info>Done</info>');

		return true;
	}

	/**
	 * @param Fixture<object>[] $fixtures
	 */
	private function load(OutputInterface $output, array $fixtures): bool
	{
		$total = count($fixtures);

		$this->fixtureManager->onFixtureLoading[] = function (Fixture $fixture, int $position) use ($output, $total): void {
			$output->write(sprintf(
				'%d/%d <comment>[%s]</comment> Loading fixture %s.',
				$position,
				$total,
				$fixture->getKey()->getName(),
				$fixture::class,
			));
		};

		$this->fixtureManager->onFixtureFinished[] = function (Fixture $fixture, int $count) use ($output): void {
			$output->writeln(sprintf(
				' Loaded <info>%d</info> records. <info>Done</info>',
				$count,
			));
		};

		$output->write('Validating fixtures.');

		$errors = $this->fixtureManager->validate($this->recordManager, $fixtures);

		if ($errors) {
			$output->writeln('');

			foreach ($errors as $error) {
				$output->writeln(sprintf('<error>%s</error>', $error));
			}

			return false;
		}

		$output->writeln(' <info>Done</info>');

		$output->writeln('Loading fixtures.');

		$this->fixtureManager->load($fixtures);

		$output->writeln('<info>Done</info>');

		return true;
	}

}
