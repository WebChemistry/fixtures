<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\DI;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use WebChemistry\Fixtures\Bridge\Doctrine\Record\DoctrineRecordManager;
use WebChemistry\Fixtures\Command\LoadCommand;
use WebChemistry\Fixtures\FixtureManager;
use WebChemistry\Fixtures\FixtureRegistry;
use WebChemistry\Fixtures\Record\RecordManager;

final class FixturesExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'adapter' => Expect::string()->nullable(),
			'config' => Expect::arrayOf(Expect::arrayOf(Expect::mixed(), Expect::string()), Expect::string()),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $config */
		$config = $this->getConfig();

		if ($config->adapter === 'doctrine') {
			$builder->addDefinition($this->prefix('recordManager'))
				->setType(RecordManager::class)
				->setFactory(DoctrineRecordManager::class);
		}

		$builder->addDefinition($this->prefix('manager'))
			->setFactory(FixtureManager::class, ['config' => $config->config]);

		$builder->addDefinition($this->prefix('registry'))
			->setFactory(FixtureRegistry::class);

		$builder->addDefinition($this->prefix('command'))
			->setFactory(LoadCommand::class);
	}

}
