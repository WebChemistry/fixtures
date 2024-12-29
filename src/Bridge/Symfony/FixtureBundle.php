<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Bridge\Symfony;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WebChemistry\Fixtures\Bridge\Doctrine\Record\DoctrineRecordManager;
use WebChemistry\Fixtures\Command\LoadCommand;
use WebChemistry\Fixtures\Fixture;
use WebChemistry\Fixtures\FixtureManager;
use WebChemistry\Fixtures\FixtureRegistry;
use WebChemistry\Fixtures\Record\RecordManager;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

final class FixtureBundle extends AbstractBundle
{

	/**
	 * @param mixed[] $config
	 */
	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		$services = $container->services();

		$services->set(DoctrineRecordManager::class)
			->autowire()
			->alias(RecordManager::class, DoctrineRecordManager::class);

		$services->set(FixtureManager::class)
			->autowire();

		$services->set('fixture.registry', FixtureRegistry::class)
			->args([tagged_iterator('fixture')])
			->autowire()
			->public()
			->alias(FixtureRegistry::class, 'fixture.registry');

		$services->set(LoadCommand::class)
			->autowire()
			->autoconfigure();

		$builder->registerForAutoconfiguration(Fixture::class)
			->addTag('fixture');
	}

}
