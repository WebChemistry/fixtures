<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Faker;

use DateTime;
use DateTimeImmutable;
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

	public function withUnique(bool $reset = false): self
	{
		return new self($this->faker->unique($reset));
	}

	public function float(int|Range|null $decimals = null, int $min = 0, ?int $max = null): float
	{
		if ($decimals instanceof Range) {
			$decimals = mt_rand($decimals->min, $decimals->max);
		}

		return $this->faker->randomFloat($decimals, $min, $max);
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

	public function dateTimeImmutableBetween(string $startDay = '- 30 years', string $endDay = 'now'): DateTimeImmutable
	{
		return DateTimeImmutable::createFromInterface($this->faker->dateTimeBetween($startDay, $endDay));
	}

	public function randomNullableNumber(int $min, int $max): ?int
	{
		$rand = mt_rand($min, ++$max);

		return $rand === $max ? null : $rand;
	}

	public function bool(int $chanceOfGettingTrue = 50): bool
	{
		return $this->faker->boolean($chanceOfGettingTrue);
	}

	public function htmlText(int|Range $paragraphs, bool $emoji = false): string
	{
		$count = Range::toInteger($paragraphs);
		if ($count === 0) {
			return '';
		}

		$paragraphs = $this->faker->paragraphs($count);

		if (is_string($paragraphs)) {
			$paragraphs = [$paragraphs];
		}

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
		/** @var string */
		return $this->faker->words(Range::toInteger($words), true);
	}

	public function ticker(): Ticker
	{
		$tickers = TickerProvider::getTickers();

		return $tickers[array_rand($tickers)];
	}

	public function randomFloat(?int $maxDecimals = null, int|float $min = 0, int|float|null $max = null): float
	{
		return $this->faker->randomFloat($maxDecimals, $min, $max);
	}

	public function numberBetween(int $min = 0, int $max = 2147483647): int
	{
		return $this->faker->numberBetween($min, $max);
	}

	public function text(int|Range $maxNbChars = 200): string
	{
		return $this->faker->text(Range::toInteger($maxNbChars));
	}

	public function boolean(int $chanceOfGettingTrue = 50): bool
	{
		return $this->faker->boolean($chanceOfGettingTrue);
	}

}
