<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Reference;

use BadMethodCallException;
use LogicException;
use OutOfBoundsException;
use WeakMap;
use WebChemistry\Fixtures\Utility\Range;

final class ReferenceRepository
{

	/** @var mixed[] */
	private array $references = [];

	/** @var mixed[] */
	private array $processed = [];

	private WeakMap $ignores;

	public function __construct()
	{
		$this->ignores = new WeakMap();
	}

	public function setReference(string $name, object $reference): object
	{
		if (isset($this->ignores[$reference])) {
			throw new LogicException(sprintf('Given object is ignored.'));
		}

		return $this->references[$reference::class][$name] = $reference;
	}

	public function addReference(string $name, object $object): object
	{
		if ($this->hasReference($name, $object::class)) {
			throw new BadMethodCallException(sprintf(
				'Reference to "%s" already exists, use method setReference in order to override it',
				$name
			));
		}

		$this->setReference($name, $object);

		return $object;
	}

	/**
	 * @internal
	 */
	public function addProcessed(object $object): object
	{
		if (isset($this->ignores[$object])) {
			return $object;
		}

		$this->processed[$object::class][] = $object;

		return $object;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getReference(string $className, string $name)
	{
		if (! $this->hasReference($className, $name)) {
			throw new OutOfBoundsException(sprintf('Reference to "%s:%s" does not exist', $className, $name));
		}

		return $this->references[$className][$name];
	}


	public function hasReference(string $className, string $name): bool
	{
		return isset($this->references[$className][$name]);
	}

	/**
	 * @template T of object
	 * @param T $object
	 * @return T
	 */
	public function ignore(object $object): object
	{
		$this->ignores[$object] = true;

		return $object;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getRandom(string $className, bool $onlyReferences = false): object
	{
		if (!$onlyReferences && isset($this->processed[$className])) {
			return $this->processed[$className][array_rand($this->processed[$className])];
		}

		if (isset($this->references[$className])) {
			return $this->references[$className][array_rand($this->references[$className])];
		}

		throw new OutOfBoundsException(sprintf('Reference to "%s" does not exist', $className));
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getAllProcessed(string $className): array
	{
		return $this->processed[$className] ?? [];
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T[]
	 */
	public function getRandomMany(string $className, int|Range $count, bool $onlyReferences = false): array
	{
		$count = Range::toInteger($count);

		$haystack = null;
		if (!$onlyReferences && isset($this->processed[$className])) {
			$haystack = $this->processed[$className];
		} else if (isset($this->references[$className])) {
			$haystack = $this->references[$className];
		}

		if (!$haystack) {
			throw new OutOfBoundsException(sprintf('Reference to "%s" does not exist', $className));
		}

		$return = [];
		while ($count > 0) {
			$return[] = $haystack[array_rand($haystack)];

			$count--;
		}

		return $return;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T[]
	 */
	public function getRandomManyUnique(string $className, int|Range $count, bool $onlyReferences = false): array
	{
		$count = Range::toInteger($count);

		$haystack = null;
		if (!$onlyReferences && isset($this->processed[$className])) {
			$haystack = $this->processed[$className];
		} else if (isset($this->references[$className])) {
			$haystack = $this->references[$className];
		}

		if (!$haystack) {
			throw new OutOfBoundsException(sprintf('Reference to "%s" does not exist', $className));
		}

		if ($count <= 0) {
			return [];
		}

		$currentCount = count($haystack);
		if ($currentCount <= $count) {
			return $haystack;
		}

		$return = [];
		foreach ((array) array_rand($haystack, $count) as $key) {
			$return[] = $haystack[$key];
		}

		return $return;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T|null
	 */
	public function getNullableRandom(string $className, int $nullablePercentChance): ?object
	{
		if (mt_rand(0, 100) < $nullablePercentChance) {
			return null;
		}

		return $this->getRandom($className);
	}

}
