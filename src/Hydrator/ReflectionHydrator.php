<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Hydrator;

use InvalidArgumentException;
use ReflectionClass;

final class ReflectionHydrator implements Hydrator
{

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param array<string, mixed> $values
	 * @return T
	 */
	public function hydrate(string $className, array $values): object
	{
		$reflection = new ReflectionClass($className);

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

}
