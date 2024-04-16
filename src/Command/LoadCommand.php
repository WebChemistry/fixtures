<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebChemistry\Fixtures\Fixture;
use WebChemistry\Fixtures\FixtureManager;
use WebChemistry\Fixtures\FixtureRegistry;
use WebChemistry\Fixtures\Record\RecordManager;

final class LoadCommand extends Command
{

	protected static $defaultName = 'fixtures:load';

	public function __construct(
		private FixtureManager $fixtureManager,
		private FixtureRegistry $fixtureRegistry,
		private RecordManager $recordManager,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setDescription('Creates fixtures')
			->addArgument('fixture', null, 'Load only specific fixture')
			->addOption('no-purge', null, null, 'Do not purge database before loading fixtures');
	}

	public function run(InputInterface $input, OutputInterface $output): int
	{
		$fixtureName = $input->getArgument('fixture');
		$purge = !$input->getOption('no-purge');

		if (is_string($fixtureName) && $fixtureName) {
			$fixtures = $this->fixtureRegistry->getByKeysWithDependencies(
				array_map(trim(...), explode(',', $fixtureName)),
			);
		} else {
			$fixtures = $this->fixtureRegistry->getAll();
		}

		return $this->loadFixtures($input, $output, $fixtures, $purge) ? self::SUCCESS : self::FAILURE;
	}

	/**
	 * @param Fixture[] $fixtures
	 */
	private function loadFixtures(InputInterface $input, OutputInterface $output, array $fixtures, bool $purge): bool
	{
		$io = new SymfonyStyle($input, $output);

		$fixtureNames = implode(', ', array_map(
			fn (Fixture $fixture) => $fixture->getKey()->getName(),
			$fixtures,
		));

		$output->writeln(sprintf('Fixtures to load: %s', $fixtureNames));

		if (!$io->confirm('Do you want to load fixtures?')) {
			return false;
		}

		if ($purge) {
			$output->write('Purging fixtures.');

			$this->recordManager->purge($fixtures);

			$output->writeln(' <info>Done</info>');
		}

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

		$this->fixtureManager->load($this->recordManager, $fixtures);

		$output->writeln('<info>Done</info>');

		return true;
	}

}
