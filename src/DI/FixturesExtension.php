<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\DI;

use Nette\DI\CompilerExtension;
use WebChemistry\Fixtures\Command\FixturesCommand;
use WebChemistry\Fixtures\DoctrineFixtureManager;
use WebChemistry\Fixtures\Purger\OrmPurger;

final class FixturesExtension extends CompilerExtension
{

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('manager'))
			->setFactory(DoctrineFixtureManager::class);

		$builder->addDefinition($this->prefix('command'))
			->setFactory(FixturesCommand::class);

		$builder->addDefinition($this->prefix('purger'))
			->setFactory(OrmPurger::class);
	}

}
