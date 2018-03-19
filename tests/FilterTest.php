<?php

use common\io\File;
use common\io\Filter;
use common\io\filter\Composer;
use common\io\filter\HiddenFiles;
use common\io\FilterArray;
use common\io\Utils;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/../vendor/autoload.php';

class FilterTest extends TestCase {
	/**
	 * @var File
	 */
	protected static $dir;

	public static function setUpBeforeClass() {
		self::$dir = (new File("."))->get("test")->mkdir();
		self::$dir->get("directoryTest")->mkdir()->createFile("test.ext", "content");
	}

	public static function tearDownAfterClass() {
		self::$dir->delete();
	}

	public function testCommon() {
		self::assertCount(1, self::$dir);
		self::$dir->addFilter(
			new class() extends Filter {
				function filter(File $dirOrFile): bool {
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
	public function testComposer(){
		self::$dir->get("composerTest/vendor")->mkdir()->getParent()->createFile("composer.json","{}")->getParent()->createFile("test.json","{}");
		self::$dir->addFilter(new Composer(),true);
		self::assertCount(1,self::$dir->get("composerTest")->listContents());
	}
	public function testHiddenFiles(){
		self::$dir->get("hiddenFilesTest/.test")->mkdir()->getParent()->createFile(".config.json","{}")->getParent()->createFile("test.json","{}");
		self::$dir->addFilter(new HiddenFiles(),true);
		self::assertCount(1,self::$dir->get("hiddenFilesTest")->listContents());
	}
	public function testFilterArray(){
		$array = new FilterArray();
		self::assertCount(0,$array);
		$array[] = "hey";
		$array[] = "hey2";
		$array[] = "hey3";
		self::assertCount(3,$array);
		self::assertEquals(2,$array->getCounter());
		unset($array[0]);
		self::assertCount(2,$array);
		self::assertEquals(2,$array->getCounter());
	}
}
