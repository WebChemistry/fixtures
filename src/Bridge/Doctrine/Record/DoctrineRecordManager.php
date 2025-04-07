<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Bridge\Doctrine\Record;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use MJS\TopSort\Implementations\FixedArraySort;
use WebChemistry\Fixtures\Bridge\Doctrine\Key\DoctrineFixtureKey;
use WebChemistry\Fixtures\Fixture;
use WebChemistry\Fixtures\Record\RecordManager;

final class DoctrineRecordManager implements RecordManager
{

	/**
	 * @param string[] $excludeTables
	 */
	public function __construct(
		private readonly ManagerRegistry $registry,
		private readonly array $excludeTables = [],
	)
	{
	}

	/**
	 * @param class-string $entity
	 */
	private function getEntityManager(string $entity): EntityManagerInterface
	{
		$em = $this->registry->getManagerForClass($entity);

		if ($em === null) {
			throw new LogicException(sprintf('Entity manager for class %s not found.', $entity));
		}

		assert($em instanceof EntityManagerInterface);

		return $em;
	}

	public function isEmpty(Fixture $fixture): bool
	{
		$key = $fixture->getKey();

		if (!$key instanceof DoctrineFixtureKey) {
			throw new LogicException(sprintf('Fixture key must be instance of %s in %s.', DoctrineFixtureKey::class, $fixture::class));
		}

		$em = $this->getEntityManager($key->getClassName());
		$metadata = $em->getClassMetadata($key->getClassName());

		return $em->getRepository($metadata->name)->count() === 0;
	}

	/**
	 * @param Fixture<object>[] $fixtures
	 * @return string[]
	 */
	public function purge(array $fixtures, int $mode = self::PurgeModeDefault): array
	{
		$registry = new OrmPurgerRegistry($this->registry);

		foreach ($fixtures as $fixture) {
			$key = $fixture->getKey();

			if (!$key instanceof DoctrineFixtureKey) {
				throw new LogicException(sprintf('Fixture key must be instance of %s in %s.', DoctrineFixtureKey::class, $fixture::class));
			}

			$registry->add($key->getClassName());
		}

		return $registry->purge($mode, $this->excludeTables);
	}

}
