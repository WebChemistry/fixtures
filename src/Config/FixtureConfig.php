<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Config;

use InvalidArgumentException;
use WebChemistry\Fixtures\Utility\Range;

final class FixtureConfig
{

	public const RepeatKey = 'repeat';

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(
		private array $config,
	)
	{
	}

	/**
	 * @template T
	 * @param T $default
	 * @return T
	 */
	public function get(string $key, mixed $default): mixed
	{
		return $this->config[$key] ?? $default;
	}

	public function getRepeat(int|Range|null $default, string $key = self::RepeatKey): int|Range|null
	{
		if (!isset($this->config[$key])) {
			return $default;
		}

		$value = $this->config[$key];

		if (is_numeric($value)) {
			return (int) $value;
		}

		if (is_array($value) && count($value) === 2) {
			return new Range($value[0], $value[1]);
		}

		throw new InvalidArgumentException(sprintf('Invalid repeat value for key %s.', $key));
	}

}
