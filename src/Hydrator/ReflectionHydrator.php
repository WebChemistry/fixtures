<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Hydrator;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use ReflectionClass;

final class ReflectionHydrator implements Hydrator
{

	/** @var array<string, array<string, callable(mixed): mixed>> */
	private array $converters = [];

	/** @var array<string, string> */
	private array $builtInTypes = [];

	public function __construct(
		private readonly ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param array<string, mixed> $values
	 * @return T
	 */
	public function hydrate(string $className, array $values): object
	{
		$reflection = new ReflectionClass($className);
		$converters = $this->getConverters($className);
		foreach ($values as $key => $value) {
			if (!isset($converters[$key])) {
				continue;
			}

			$values[$key] = $converters[$key]($value);
		}

		$constructor = $reflection->getConstructor();
		$args = [];

		foreach ($constructor?->getParameters() ?? [] as $parameter) {
			if (!array_key_exists($parameter->name, $values)) {
				throw new InvalidArgumentException(sprintf('Parameter %s is missing.', $parameter->name));
			}

			$args[] = $values[$parameter->name];

			unset($values[$parameter->name]);
		}

		$object = $reflection->newInstanceArgs($args);

		foreach ($values as $name => $value) {
			$reflection->getProperty($name)->setValue($object, $value);
		}

		return $object;
	}

	/**
	 * @param class-string $entity
	 * @return array<string, callable(mixed): mixed>
	 */
	private function getConverters(string $entity): array
	{
		if (isset($this->converters[$entity])) {
			return $this->converters[$entity];
		}

		$manager = $this->managerRegistry->getManagerForClass($entity);
		if ($manager === null) {
			return $this->converters[$entity] = [];
		}

		assert($manager instanceof EntityManagerInterface);
		$metadata = $manager->getClassMetadata($entity);

		$platform = $manager->getConnection()->getDatabasePlatform();
		$builtInTypes = $this->getBuiltInTypes();
		$converters = [];
		foreach ($metadata->getFieldNames() as $fieldName) {
			$mapping = $metadata->getFieldMapping($fieldName);

			if (isset($builtInTypes[$mapping->type])) {
				continue;
			}

			$type = Type::getType($mapping->type);
			$converters[$fieldName] = static fn (mixed $value): mixed => $type->convertToPHPValue($value, $platform);
		}

		return $this->converters[$entity] = $converters;
	}

	/**
	 * @return array<string, string>
	 */
	private function getBuiltInTypes(): array
	{
		if ($this->builtInTypes === []) {
			$reflection = new ReflectionClass(Types::class);
			$constants = $reflection->getConstants();

			/** @var string $type */
			foreach ($constants as $type) {
				$this->builtInTypes[$type] = $type;
			}
		}

		return $this->builtInTypes;
	}

}
