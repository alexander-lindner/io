<?php

use common\io\Directory;
use common\io\Filter;
use common\io\Utils;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/../vendor/autoload.php';

class FilterTest extends TestCase {
	/**
	 * @var Directory
	 */
	protected static $dir;

	public static function setUpBeforeClass() {
		self::$dir = (new Directory("."))->get("test")->mkdir();
		self::$dir->get("directoryTest")->mkdir()->createFile("test.ext", "content");
	}

	public static function tearDownAfterClass() {
		self::$dir->delete();
	}

	public function testCommon() {
		self::assertCount(1, self::$dir);
		self::$dir->addFilter(
			new class() extends Filter {
				function filter($dirOrFile): bool {
					if ($dirOrFile->isDirectory() && $dirOrFile->getName() == "directoryTest") {
						return false;
					}
					if ($dirOrFile->isFile() && Utils::strcontains($dirOrFile->getPath(), "directoryTest")) {
						return false;
					}

					return true;
				}
			}
		);
		self::assertCount(0, self::$dir);
	}
}
