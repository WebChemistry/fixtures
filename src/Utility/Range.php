<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Utility;

final class Range
{

	public function __construct(
		public int $min,
		public int $max,
	)
	{
	}

	public static function toInteger(int|Range $number, int $min = 0): int
	{
		if (is_int($number)) {
			return max($number, $min);
		}

		return max(mt_rand($number->min, $number->max), $min);
	}

}
