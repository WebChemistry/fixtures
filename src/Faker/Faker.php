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

final readonly class Faker
{

	public Generator|UniqueGenerator $original;

	public function __construct(
		Generator|UniqueGenerator|null $faker = null,
	)
	{
		$this->original = $faker ?? Factory::create();
	}

	public function withUnique(bool $reset = false): self
	{
		return new self($this->original->unique($reset));
	}

	public function float(int|Range|null $decimals = null, int $min = 0, ?int $max = null): float
	{
		if ($decimals instanceof Range) {
			$decimals = mt_rand($decimals->min, $decimals->max);
		}

		return $this->original->randomFloat($decimals, $min, $max);
	}

	public function name(?string $gender = null): string
	{
		return $this->original->name($gender);
	}

	public function asciiString(int|Range $length, string $charList = '0-9a-z'): string
	{
		return Random::generate(Range::toInteger($length), $charList);
	}

	public function dateTimeBetween(string $startDay = '- 30 years', string $endDay = 'now'): DateTime
	{
		return $this->original->dateTimeBetween($startDay, $endDay);
	}

	public function dateTimeImmutableBetween(string $startDay = '- 30 years', string $endDay = 'now'): DateTimeImmutable
	{
		return DateTimeImmutable::createFromInterface($this->original->dateTimeBetween($startDay, $endDay));
	}

	public function randomNullableNumber(int $min, int $max): ?int
	{
		$rand = mt_rand($min, ++$max);

		return $rand === $max ? null : $rand;
	}

	public function bool(int $chanceOfGettingTrue = 50): bool
	{
		return $this->original->boolean($chanceOfGettingTrue);
	}

	public function htmlText(int|Range $paragraphs, bool $emoji = false): string
	{
		$count = Range::toInteger($paragraphs);
		if ($count === 0) {
			return '';
		}

		$paragraphs = $this->original->paragraphs($count);

		if (is_string($paragraphs)) {
			$paragraphs = [$paragraphs];
		}

		if ($emoji) {
			$rand = array_rand($paragraphs);

			$paragraphs[$rand] = $paragraphs[$rand] . ' ' . $this->original->emoji();
		}

		return '<p>' . implode('</p><p>', $paragraphs) . '</p>';
	}

	public function email(): string
	{
		return $this->original->email();
	}

	public function words(int|Range $words): string
	{
		/** @var string */
		return $this->original->words(Range::toInteger($words), true);
	}

	public function ticker(): Ticker
	{
		$tickers = TickerProvider::getTickers();

		return $tickers[array_rand($tickers)];
	}

	public function randomFloat(?int $maxDecimals = null, int|float $min = 0, int|float|null $max = null): float
	{
		return $this->original->randomFloat($maxDecimals, $min, $max);
	}

	public function numberBetween(int $min = 0, int $max = 2147483647): int
	{
		return $this->original->numberBetween($min, $max);
	}

	public function text(int|Range $maxNbChars = 200): string
	{
		return $this->original->text(Range::toInteger($maxNbChars));
	}

	public function boolean(int $chanceOfGettingTrue = 50): bool
	{
		return $this->original->boolean($chanceOfGettingTrue);
	}

}
