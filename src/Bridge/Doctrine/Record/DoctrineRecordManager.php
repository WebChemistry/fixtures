<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Bridge\Doctrine\Record;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
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
		private readonly EntityManagerInterface $em,
		private array $excludeTables = [],
	)
	{
	}

	public function persist(object $value): void
	{
		$this->em->persist($value);
	}

	public function flush(): void
	{
		$this->em->flush();
	}

	public function isEmpty(Fixture $fixture): bool
	{
		$key = $fixture->getKey();

		if (!$key instanceof DoctrineFixtureKey) {
			throw new LogicException(sprintf('Fixture key must be instance of %s in %s.', DoctrineFixtureKey::class, $fixture::class));
		}

		$metadata = $this->em->getClassMetadata($key->getClassName());

		return $this->em->getRepository($metadata->name)->count([]) === 0;
	}

	/**
	 * @param Fixture[] $fixtures
	 */
	public function purge(array $fixtures, int $mode = self::PurgeModeDefault): void
	{
		$classes = [];

		foreach ($fixtures as $fixture) {
			$key = $fixture->getKey();

			if (!$key instanceof DoctrineFixtureKey) {
				throw new LogicException(sprintf('Fixture key must be instance of %s in %s.', DoctrineFixtureKey::class, $fixture::class));
			}

			$metadata = $this->em->getClassMetadata($key->getClassName());

			if ($metadata->isMappedSuperclass || (isset($metadata->isEmbeddedClass) && $metadata->isEmbeddedClass)) {
				throw new LogicException(sprintf('Entity %s is not an embedded class or superclass.', $metadata->name));
			}

			$classes[] = $metadata;
		}

		$commitOrder = array_map(
			fn (string $className) => $this->em->getClassMetadata($className),
			$this->getCommitOrder($this->em, $classes)
		);

		// Get platform parameters
		$platform = $this->em->getConnection()->getDatabasePlatform();

		// Drop association tables first
		$orderedTables = $this->getAssociationTables($commitOrder, $platform);

		// Drop tables in reverse commit order
		for ($i = count($commitOrder) - 1; $i >= 0; --$i) {
			$class = $commitOrder[$i];

			if (
				(isset($class->isEmbeddedClass) && $class->isEmbeddedClass) ||
				$class->isMappedSuperclass ||
				($class->isInheritanceTypeSingleTable() && $class->name !== $class->rootEntityName)
			) {
				continue;
			}

			$orderedTables[] = $this->getTableName($class, $platform);
		}

		$connection            = $this->em->getConnection();
		$filterExpr            = method_exists(
			$connection->getConfiguration(),
			'getFilterSchemaAssetsExpression'
		) ? $connection->getConfiguration()->getFilterSchemaAssetsExpression() : null;

		$schemaAssetsFilter = method_exists(
			$connection->getConfiguration(),
			'getSchemaAssetsFilter'
		) ? $connection->getConfiguration()->getSchemaAssetsFilter() : null;

		foreach ($orderedTables as $tbl) {
			// If we have a filter expression, check it and skip if necessary
			if (! empty($filterExpr) && ! preg_match($filterExpr, $tbl)) {
				continue;
			}

			// If the table is excluded, skip it as well
			if (array_search($tbl, $this->excludeTables) !== false) {
				continue;
			}

			// Support schema asset filters as presented in
			if (is_callable($schemaAssetsFilter) && ! $schemaAssetsFilter($tbl)) {
				continue;
			}

			if ($mode === self::PurgeModeDelete) {
				$connection->executeStatement($this->getDeleteFromTableSQL($tbl, $platform));
			} else {
				$connection->executeStatement($platform->getTruncateTableSQL($tbl, true));
			}
		}
	}

	/**
	 * @param ClassMetadata[] $classes
	 * @return string[]
	 */
	private function getCommitOrder(EntityManagerInterface $em, array $classes)
	{
		$dependencies = [];

		foreach ($classes as $class) {
			if (!isset($dependencies[$class->name])) {
				$dependencies[$class->name] = [];
			}

			// $class before its parents
			foreach ($class->parentClasses as $parentClass) {
				$parentClass     = $em->getClassMetadata($parentClass);
				$parentClassName = $parentClass->getName();

				if (!isset($dependencies[$parentClassName])) {
					$dependencies[$parentClassName] = [];
				}

				$dependencies[$class->name][] = $parentClassName;
			}

			foreach ($class->associationMappings as $assoc) {
				if (! $assoc['isOwningSide']) {
					continue;
				}

				$targetClass = $em->getClassMetadata($assoc['targetEntity']);
				assert($targetClass instanceof ClassMetadata);
				$targetClassName = $targetClass->getName();

				if (!isset($dependencies[$targetClassName])) {
					$dependencies[$targetClassName] = [];
				}

				// add dependency ($targetClass before $class)
				$dependencies[$targetClassName][] = $class->name;

				// parents of $targetClass before $class, too
				foreach ($targetClass->parentClasses as $parentClass) {
					$parentClass     = $em->getClassMetadata($parentClass);
					$parentClassName = $parentClass->getName();

					if (!isset($dependencies[$parentClassName])) {
						$dependencies[$parentClassName] = [];
					}

					$dependencies[$parentClassName][] = $class->name;
				}
			}
		}

		$sorter = new FixedArraySort($dependencies);
		$sorter->setThrowCircularDependency(false);

		return array_reverse($sorter->sort());
	}

	/**
	 * @param array $classes
	 *
	 * @return array
	 */
	private function getAssociationTables(array $classes, AbstractPlatform $platform)
	{
		$associationTables = [];

		foreach ($classes as $class) {
			foreach ($class->associationMappings as $assoc) {
				if (! $assoc['isOwningSide'] || $assoc['type'] !== ClassMetadata::MANY_TO_MANY) {
					continue;
				}

				$associationTables[] = $this->getJoinTableName($assoc, $class, $platform);
			}
		}

		return $associationTables;
	}

	private function getTableName(ClassMetadata $class, AbstractPlatform $platform): string
	{
		if (isset($class->table['schema']) && ! method_exists($class, 'getSchemaName')) {
			return $class->table['schema'] . '.' .
				   $this->em->getConfiguration()
					   ->getQuoteStrategy()
					   ->getTableName($class, $platform);
		}

		return $this->em->getConfiguration()->getQuoteStrategy()->getTableName($class, $platform);
	}

	/**
	 * @param mixed[] $assoc
	 */
	private function getJoinTableName(
		array $assoc,
		ClassMetadata $class,
		AbstractPlatform $platform
	): string {
		if (isset($assoc['joinTable']['schema']) && ! method_exists($class, 'getSchemaName')) {
			return $assoc['joinTable']['schema'] . '.' .
				   $this->em->getConfiguration()
					   ->getQuoteStrategy()
					   ->getJoinTableName($assoc, $class, $platform);
		}

		return $this->em->getConfiguration()->getQuoteStrategy()->getJoinTableName($assoc, $class, $platform);
	}

	private function getDeleteFromTableSQL(string $tableName, AbstractPlatform $platform): string
	{
		$tableIdentifier = new Identifier($tableName);

		return 'DELETE FROM ' . $tableIdentifier->getQuotedName($platform);
	}

}
