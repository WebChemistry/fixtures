<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Nette\Utils\Arrays;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Serializer;
use WebChemistry\Fixtures\Faker\Faker;
use WebChemistry\Fixtures\Reference\ReferenceRepository;
use WebChemistry\Fixtures\Sorter\DependencySorter;
use WebChemistry\Fixtures\Utility\ArrayToEntity;

final class DoctrineFixtureManager
{

	private Faker $faker;

	private Serializer $serializer;

	private PropertyAccessor $propertyAccessor;

	/** @var callable[] */
	public array $onFixture = [];

	/** @var callable[] */
	public array $onFixtureFinished = [];

	/** @var callable[] */
	public array $onEntity = [];

	/**
	 * @param Fixture[] $fixtures
	 */
	public function __construct(
		private EntityManagerInterface $em,
		private array $fixtures,
		?Faker $faker = null,
	)
	{
		$this->faker = $faker ?? new Faker();
	}

	public function setSerializer(Serializer $serializer): self
	{
		$this->serializer = $serializer;

		return $this;
	}

	public function setPropertyAccessor(PropertyAccessor $propertyAccessor): self
	{
		$this->propertyAccessor = $propertyAccessor;

		return $this;
	}

	public function cleanup(): void
	{

	}

	public function load(): void
	{
		$reference = new ReferenceRepository();

		foreach (DependencySorter::sortDependencies($this->fixtures) as $fixture) {
			Arrays::invoke($this->onFixture, $fixture);

			$fixture->setUp($reference, $this->faker);

			$count = 0;
			foreach ($fixture->load() as $entity) {
				Arrays::invoke($this->onEntity, $entity);

				$this->em->persist($entity);

				$reference->addProcessed($entity);

				$count++;
			}

			Arrays::invoke($this->onFixtureFinished, $fixture, $count);
		}

		$this->em->flush();
	}

	private function convertArrayToEntity(ArrayToEntity $entity): object
	{
		if (isset($this->serializer)) {
			return $this->serializer->denormalize($entity->fields, $entity->className);
		} else {
			throw new LogicException('Serializer or property accessor must be set.');
		}
	}

}
