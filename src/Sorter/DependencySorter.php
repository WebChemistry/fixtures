<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Sorter;

use MJS\TopSort\Implementations\FixedArraySort;
use SplFixedArray;
use WebChemistry\Fixtures\Fixture;

final class DependencySorter
{

	/**
	 * @param Fixture[] $fixtures
	 * @return Fixture[]
	 */
	public static function sortDependencies(array $fixtures): iterable
	{
		$sorted = new FixedArraySort();
		$indexed = [];
		foreach ($fixtures as $fixture) {
			$sorted->add($fixture::class, $fixture->dependencies());
			$indexed[$fixture::class] = $fixture;
		}

		foreach ($sorted->sort() as $key) {
			yield $indexed[$key];
		}
	}

}
