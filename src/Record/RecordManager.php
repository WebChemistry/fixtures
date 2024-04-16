<?php declare(strict_types = 1);

namespace WebChemistry\Fixtures\Record;

use WebChemistry\Fixtures\Fixture;
use WebChemistry\Fixtures\Key\FixtureKey;

interface RecordManager
{

	public const PurgeModeDelete = 1;
	public const PurgeModeTruncate = 2;
	public const PurgeModeDefault = self::PurgeModeDelete;

	public function persist(object $value): void;

	public function flush(): void;

	/**
	 * @param Fixture[] $fixtures
	 */
	public function purge(array $fixtures, int $mode = self::PurgeModeDefault): void;

	public function isEmpty(Fixture $fixture): bool;

}
