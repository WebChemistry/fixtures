<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\Fixtures\DoctrineFixtureManager;
use WebChemistry\Fixtures\Fixture;
use WebChemistry\Fixtures\Purger\OrmPurger;

final class FixturesCommand extends Command
{

	protected static $defaultName = 'fixtures';

	public function __construct(
		private DoctrineFixtureManager $fixtureManager,
		private OrmPurger $ormPurger,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setDescription('Creates fixtures');
	}

	public function run(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('Purge database.');

		$this->ormPurger->purge();

		$output->writeln('Load fixtures.');

		$this->fixtureManager->onFixture[] = fn (Fixture $fixture) => $output->writeln(
			sprintf('Loading fixture <comment>%s</comment>', $fixture::class)
		);
		$this->fixtureManager->onFixtureFinished[] = fn (Fixture $fixture, int $count) => $output->writeln(
			sprintf('Fixture <comment>%s</comment> creates <info>%d</info> entities.', $fixture::class, $count)
		);

		$this->fixtureManager->load();

		return self::SUCCESS;
	}

}
