<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Faker\Provider;

use WebChemistry\Fixtures\Faker\Objects\Ticker;

final class TickerProvider
{

	/**
	 * @return Ticker[]
	 */
	public static function getTickers(): array
	{
		return require __DIR__ . '/../../../data/tickers.php';
	}

}
