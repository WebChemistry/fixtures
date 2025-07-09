<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Faker\Provider;

use WebChemistry\Fixtures\Faker\Objects\Ticker;

final class TickerProvider
{

	/**
	 * @return list<Ticker>
	 */
	public static function getTickers(): array
	{
		/** @var list<Ticker> */
		return require __DIR__ . '/../../../data/tickers.php';
	}

}
