<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Faker;

use DateTime;
use Faker\Factory;
use Faker\Generator;
use Faker\UniqueGenerator;
use Nette\Utils\Random;
use WebChemistry\Fixtures\Faker\Objects\Ticker;
use WebChemistry\Fixtures\Faker\Provider\TickerProvider;
use WebChemistry\Fixtures\Utility\Range;

class Faker
{

	private Generator|UniqueGenerator $faker;

	public function __construct(
		Generator|UniqueGenerator|null $faker = null,
	)
	{
		$this->faker = $faker ?? Factory::create();
	}

	public function withUnique(): self
	{
		return new self($this->faker->unique());
	}

	public function name(?string $gender = null): string
	{
		return $this->faker->name($gender);
	}

	public function asciiString(int|Range $length, string $charList = '0-9a-z'): string
	{
		return Random::generate(Range::toInteger($length), $charList);
	}

	public function dateTimeBetween(string $startDay = '- 30 years', string $endDay = 'now'): DateTime
	{
		return $this->faker->dateTimeBetween($startDay, $endDay);
	}

	public function randomNullableNumber(int $min, int $max): ?int
	{
		$rand = mt_rand($min, ++$max);

		return $rand === $max ? null : $rand;
	}

	public function bool(): bool
	{
		return mt_rand(0, 1) === 1;
	}

	public function htmlText(int|Range $paragraphs, bool $emoji = false): string
	{
		$count = Range::toInteger($paragraphs);
		if ($count === 0) {
			return '';
		}

		$paragraphs = $this->faker->paragraphs($count);

		if ($emoji) {
			$rand = array_rand($paragraphs);

			$paragraphs[$rand] = $paragraphs[$rand] . ' ' . $this->faker->emoji();
		}

		return '<p>' . implode('</p><p>', $paragraphs) . '</p>';
	}

	public function email(): string
	{
		return $this->faker->email();
	}

	public function words(int|Range $words): string
	{
		return $this->faker->words(Range::toInteger($words), true);
	}

	public function ticker(): Ticker
	{
		$tickers = TickerProvider::getTickers();

		return $tickers[array_rand($tickers)];
	}

}
