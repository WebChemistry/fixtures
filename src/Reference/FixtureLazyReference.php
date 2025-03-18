<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Reference;

/**
 * @template T of object|object[]|null
 */
final readonly class FixtureLazyReference
{

	/** @var callable(): T */
	private mixed $fn;

	/**
	 * @param callable(): T $fn
	 */
	public function __construct(callable $fn)
	{
		$this->fn = $fn;
	}

	/**
	 * @return T
	 */
	public function resolve(): object|array|null
	{
		return ($this->fn)();
	}

}
