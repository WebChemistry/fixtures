<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Record;

use WebChemistry\Fixtures\Fixture;

interface RecordManager
{

	public const int PurgeModeDelete = 1;
	public const int PurgeModeTruncate = 2;
	public const int PurgeModeDefault = self::PurgeModeDelete;

	/**
	 * @param Fixture<object>[] $fixtures
	 * @return string[]
	 */
	public function purge(array $fixtures, int $mode = self::PurgeModeDefault): array;

	/**
	 * @param Fixture<object> $fixture
	 */
	public function isEmpty(Fixture $fixture): bool;

}
