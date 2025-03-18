<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Reference;

use BadMethodCallException;
use LogicException;
use OutOfBoundsException;
use WeakMap;
use WebChemistry\Fixtures\Utility\Range;

final class ReferenceRepository
{

	public const DefaultReference = 'defaultRef';

	/** @var mixed[] */
	private array $references = [];

	/** @var mixed[] */
	private array $processed = [];

	/** @var mixed[] */
	private array $buckets = [];

	/** @var WeakMap<object, bool> */
	private WeakMap $ignores;

	public function __construct()
	{
		$this->ignores = new WeakMap();
	}

	/**
	 * @template T of object
	 * @param T $reference
	 * @return T
	 */
	public function addToBucket(string $name, object $reference): object
	{
		if (!isset($this->buckets[$reference::class])) {
			$this->buckets[$reference::class] = [];
		}
		if (!isset($this->buckets[$reference::class][$name])) {
			$this->buckets[$reference::class][$name] = [];
		}

		return $this->buckets[$reference::class][$name][] = $reference;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T[]
	 */
	public function getBucket(string $className, string $name): array
	{
		return $this->buckets[$className][$name] ?? throw new OutOfBoundsException(sprintf('Bucket "%s:%s" does not exist', $className, $name));
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getRandomFromBucket(string $className, string $name): object
	{
		$bucket = $this->getBucket($className, $name);

		return $bucket[array_rand($bucket)];
	}

	/**
	 * @template T of object
	 * @param T $reference
	 * @return T
	 */
	public function setReference(string $name, object $reference): object
	{
		if (isset($this->ignores[$reference])) {
			throw new LogicException(sprintf('Given object is ignored.'));
		}

		return $this->references[$reference::class][$name] = $reference;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getDefaultReference(string $className): object
	{
		return $this->getReference($className, self::DefaultReference);
	}

	/**
	 * @template T of object
	 * @param T $reference
	 * @return T
	 */
	public function setDefaultReference(object $reference): object
	{
		return $this->setReference(self::DefaultReference, $reference);
	}

	/**
	 * @template T of object
	 * @param T $object
	 * @return T
	 */
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
	public function getReference(string $className, string $name): object
	{
		return $this->getReferenceOrNull($className, $name)
			   ??
			   throw new OutOfBoundsException(sprintf('Reference to "%s:%s" does not exist', $className, $name));
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T|null
	 */
	public function getReferenceOrNull(string $className, string $name): ?object
	{
		if (! $this->hasReference($className, $name)) {
			return null;
		}

		return $this->references[$className][$name];
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getReferenceOrRandom(string $className, string $name): object
	{
		return $this->getReferenceOrNull($className, $name) ?? $this->getRandom($className);
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
	 * @return T[]
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

	/**
	 * @template T of object|object[]|null
	 * @param callable(): T $fn
	 * @return FixtureLazyReference<T>
	 */
	public function lazy(callable $fn): FixtureLazyReference
	{
		return new FixtureLazyReference($fn);
	}

}
