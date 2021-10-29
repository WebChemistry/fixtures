<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Purger;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use MJS\TopSort\Implementations\FixedArraySort;
use MJS\TopSort\Implementations\StringSort;

class OrmPurger
{
	public const PURGE_MODE_DELETE   = 1;
	public const PURGE_MODE_TRUNCATE = 2;

	private int $purgeMode = self::PURGE_MODE_DELETE;

	/**
	 * @param string[] $excluded
	 */
	public function __construct(
		private EntityManagerInterface $em,
		private array $excluded = [],
	)
	{
	}

	public function setPurgeMode(int $mode): void
	{
		$this->purgeMode = $mode;
	}

	public function getPurgeMode(): int
	{
		return $this->purgeMode;
	}

	public function purge(): void
	{
		$classes = [];

		foreach ($this->em->getMetadataFactory()->getAllMetadata() as $metadata) {
			if ($metadata->isMappedSuperclass || (isset($metadata->isEmbeddedClass) && $metadata->isEmbeddedClass)) {
				continue;
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
		$emptyFilterExpression = empty($filterExpr);

		$schemaAssetsFilter = method_exists(
			$connection->getConfiguration(),
			'getSchemaAssetsFilter'
		) ? $connection->getConfiguration()->getSchemaAssetsFilter() : null;

		foreach ($orderedTables as $tbl) {
			// If we have a filter expression, check it and skip if necessary
			if (! $emptyFilterExpression && ! preg_match($filterExpr, $tbl)) {
				continue;
			}

			// If the table is excluded, skip it as well
			if (array_search($tbl, $this->excluded) !== false) {
				continue;
			}

			// Support schema asset filters as presented in
			if (is_callable($schemaAssetsFilter) && ! $schemaAssetsFilter($tbl)) {
				continue;
			}

			if ($this->purgeMode === self::PURGE_MODE_DELETE) {
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

					$dependencies[$parentClassName] = $class->name;
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
