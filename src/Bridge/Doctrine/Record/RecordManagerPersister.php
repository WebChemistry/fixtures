<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Bridge\Doctrine\Record;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use WeakMap;

final class RecordManagerPersister
{

	/** @var array<class-string, EntityManagerInterface> */
	private array $entityManagers = [];

	/** @var WeakMap<EntityManagerInterface, bool> */
	private WeakMap $stack;

	public function __construct(
		private readonly ManagerRegistry $registry,
	)
	{
		$this->stack = new WeakMap();
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
	 * @param class-string $entity
	 */
	public function executeStatement(string $entity, string $sql): int
	{
		return (int) $this->getEntityManager($entity)->getConnection()->executeStatement($sql);
	}

	public function persist(object $value): void
	{
		if (!isset($this->entityManagers[$value::class])) {
			$this->entityManagers[$value::class] = $em = $this->getEntityManager($value::class);
			$this->stack[$em] = true;
		}

		$this->entityManagers[$value::class]->persist($value);
	}

	public function flush(): void
	{
		foreach ($this->stack as $em => $_) {
			$em->flush();
		}

		$this->entityManagers = [];
		$this->stack = new WeakMap();
	}

}
