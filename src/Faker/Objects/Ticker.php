<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Faker\Objects;

use JetBrains\PhpStorm\Immutable;

final class Ticker
{

	public function __construct(
		#[Immutable]
		public string $symbol,
		#[Immutable]
		public string $name,
		#[Immutable]
		public string $currency,
		#[Immutable]
		public string $exchange,
		#[Immutable]
		public string $country,
	)
	{
	}

}
