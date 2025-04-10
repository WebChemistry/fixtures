<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Sorter;

use MJS\TopSort\Implementations\FixedArraySort;
use WebChemistry\Fixtures\Fixture;

final class DependencySorter
{

	/**
	 * @param Fixture<object>[] $fixtures
	 * @return Fixture<object>[]
	 */
	public static function sortDependencies(array $fixtures): array
	{
		$sorted = new FixedArraySort();
		$indexed = [];
		foreach ($fixtures as $fixture) {
			$sorted->add($fixture::class, $fixture->dependencies());
			$indexed[$fixture::class] = $fixture;
		}

		$return = [];

		foreach ($sorted->sort() as $key) {
			$return[] = $indexed[$key];
		}

		return $return;
	}

}
