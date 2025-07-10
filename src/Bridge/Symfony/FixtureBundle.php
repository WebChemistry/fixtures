<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Bridge\Symfony;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use WebChemistry\Fixtures\Bridge\Doctrine\Record\DoctrineRecordManager;
use WebChemistry\Fixtures\Bridge\Doctrine\Record\RecordManagerPersister;
use WebChemistry\Fixtures\Command\LoadCommand;
use WebChemistry\Fixtures\Fixture;
use WebChemistry\Fixtures\FixtureManager;
use WebChemistry\Fixtures\FixtureRegistry;
use WebChemistry\Fixtures\FixtureServices;
use WebChemistry\Fixtures\Hydrator\Hydrator;
use WebChemistry\Fixtures\Hydrator\ReflectionHydrator;
use WebChemistry\Fixtures\Record\RecordManager;
use WebChemistry\Fixtures\Reference\ReferenceRepository;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

final class FixtureBundle extends AbstractBundle
{

	/**
	 * @param mixed[] $config
	 */
	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		$services = $container->services();
		$services->defaults()
			->tag('container.no_preload');

		$services->set(DoctrineRecordManager::class)
			->autowire()
			->alias(RecordManager::class, DoctrineRecordManager::class);

		$services->set(FixtureManager::class)
			->arg('$sqlFileDirectory', $config['sql_file_directory'] ?? null)
			->autowire();

		$services->set('fixture.registry', FixtureRegistry::class)
			->args([tagged_iterator('fixture')])
			->autowire()
			->public()
			->alias(FixtureRegistry::class, 'fixture.registry');

		$services->set(LoadCommand::class)
			->autowire()
			->autoconfigure();

		$services->set(RecordManagerPersister::class)
			->autowire();

		$services->set(FixtureServices::class)
			->autowire();

		$services->set(ReferenceRepository::class)
			->autowire();

		$services->set(ReflectionHydrator::class)
			->autowire()
			->alias(Hydrator::class, ReflectionHydrator::class);

		$builder->registerForAutoconfiguration(Fixture::class)
			->addTag('fixture');
	}

	public function configure(DefinitionConfigurator $definition): void
	{
		$definition->rootNode() // @phpstan-ignore-line
			->children()
				->stringNode('sql_file_directory')->defaultNull()->end()
			->end();
	}

}
