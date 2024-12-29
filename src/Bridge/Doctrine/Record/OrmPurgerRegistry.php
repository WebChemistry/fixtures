<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Bridge\Doctrine\Record;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use WeakMap;

final class OrmPurgerRegistry
{

	/** @var EntityManagerInterface[] */
	private array $entityManagers = [];

	/** @var WeakMap<EntityManagerInterface, ClassMetadata<object>[]> */
	private WeakMap $map;

	public function __construct(
		private readonly ManagerRegistry $registry,
	)
	{
	}

	/**
	 * @param class-string $className
	 */
	public function add(string $className): void
	{
		$em = $this->getEntityManager($className);

		if (!in_array($em, $this->entityManagers, true)) {
			$this->entityManagers[] = $em;
		}

		$metadata = $em->getClassMetadata($className);

		if ($metadata->isMappedSuperclass || (isset($metadata->isEmbeddedClass) && $metadata->isEmbeddedClass)) {
			throw new LogicException(sprintf('Entity %s is not an embedded class or superclass.', $metadata->name));
		}

		if (!isset($this->map[$em])) {
			$this->map[$em] = [];
		}

		$this->map[$em][] = $metadata;
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

	/**
	 * @param string[] $excludeTables
	 */
	public function purge(int $mode, array $excludeTables): void
	{
		foreach ($this->entityManagers as $em) {
			$classesToPurge = $this->map[$em] ?? throw new LogicException('No classes to purge.');

			$exclude = $this->getTablesToExclude($classesToPurge, $em->getMetadataFactory()->getAllMetadata());
			$purger = new ORMPurger($em, array_unique(array_merge($exclude, $excludeTables)));
			$purger->setPurgeMode($mode);
			$purger->purge();
		}
	}

	/**
	 * @param ClassMetadata<object>[] $classesToPurge
	 * @param ClassMetadata<object>[] $allClasses
	 * @return string[]
	 */
	private function getTablesToExclude(array $classesToPurge, array $allClasses): array
	{
		foreach ($classesToPurge as $metadata) {
			foreach ($allClasses as $key => $metadata2) {
				if ($metadata->name === $metadata2->name) {
					unset($allClasses[$key]);
				}
			}
		}

		return array_map(fn (ClassMetadata $metadata) => $metadata->getTableName(), $allClasses);
	}

}
