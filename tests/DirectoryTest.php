<?php

use common\io\File;
use common\io\exceptions\DirectoryNotFoundException;
use common\io\exceptions\NoParentAvailableException;
use common\io\Manager;
use common\io\Paths;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/../vendor/autoload.php';

class DirectoryTest extends TestCase {
	/**
	 * @var File
	 */
	protected static $dir;

	public static function setUpBeforeClass() {
		self::$dir = new File("./test/");
	}

	public static function tearDownAfterClass() {
		self::$dir->delete();
	}
	public function testInit() {
	}
	public function testMkdir() {
		self::assertDirectoryExists(self::$dir->get("testDirectory")->mkdir()->getPath());
		self::assertDirectoryExists(self::$dir->get("testDirectory")->mkdir("testMkdir")->getPath());
	}

	public function testFileCreate() {
		self::assertFileExists(self::$dir->get("testDirectory")->createFile("testFile", "test")->getPath());
	}

	public function testParent() {
		self::assertEquals((string)self::$dir, (string)self::$dir->get("testDirectory")->getParent());
	}

	public function testDirectCall() {
		self::expectException(NoParentAvailableException::class);
		(new File("/"))->getParent();
	}

	public function testListContents() {
		self::assertInternalType('array', self::$dir->listContents(false));
		self::assertGreaterThan(0, count(self::$dir->listContents(true)));
		foreach (self::$dir->listFiles() as $listFile) {
			self::assertTrue($listFile->isFile());
		}
		foreach (self::$dir->listDirectories() as $listFile) {
			self::assertTrue($listFile->isDirectory());
		}
		foreach (self::$dir as $list) {
			self::assertDirectoryExists($list->getPath());
		}
		foreach (self::$dir["testDirectory"] as $list) {
			if ($list->isDirectory()) {
				self::assertDirectoryExists($list->getPath());
			}
		}
		self::expectException(DirectoryNotFoundException::class);
		self::$dir->get(random_int(0, PHP_INT_MAX))->listContents();
	}


	public function testCopy() {
		self::assertFileExists(self::$dir->get("testDirectory")->get("testFile")->copy(self::$dir->get("testDirectoryForCopy"))->getAbsolutePath());
	}

	public function testRename() {
		self::assertTrue(self::$dir->get("testDirectory/subTestDirectory")->mkdir()->rename("subTestDirectoryRenamed"));
		self::assertDirectoryExists(self::$dir->get("testDirectory/subTestDirectoryRenamed")->getPath());

		self::assertTrue(self::$dir->get("testDirectory/testFile")->rename("testFileRenamed"));
		self::assertFileExists(self::$dir->get("testDirectory/testFileRenamed")->getPath());
	}

	public function testVarious() {
		self::assertEquals((string)self::$dir->get("lib"), self::$dir->get("lib")->getAbsolutePath());
		self::assertTrue(self::$dir->get("testDirectory")->isDirectory());
		self::assertEquals(self::$dir->count(), count(self::$dir));
		self::assertEquals("/", Paths::normalize("/lib/../"));
		self::assertTrue(isset(self::$dir["testDirectory"]["testFileRenamed"]));
		self::assertTrue(isset(self::$dir["testDirectory"]));
		self::assertFalse(isset(self::$dir["testDirectory5555"]));
		self::$dir["testDirectory"]["testFileArrayAccess.txt"] = "TestContent";
		self::assertEquals("TestContent", self::$dir->get("testDirectory/testFileArrayAccess.txt")->getContent());
		self::$dir["testDirectory"]["testFileArrayAccess.txt"] = "TestSet";
		self::assertEquals("TestSet", self::$dir->get("testDirectory/testFileArrayAccess.txt")->getContent());
		self::expectException(LogicException::class);
		self::$dir["testDirectory"] = "TestContent";
		unset(self::$dir["testDirectory"]["testFileArrayAccess.txt"]);
		self::assertFileNotExists(self::$dir["testDirectory"]["testFileArrayAccess.txt"]->getPath());
	}

	public function testAdapter() {
		$ftp = new class(".") extends File {
			public function __construct($dir) {
				$this->defaultUrl = ["scheme" => "ftp"];
				$this->workingDir = "/"; // set virtual protocol
				parent::__construct($dir);

				Manager::addAdapter(
					$this->getProtocol(),
					new League\Flysystem\Adapter\Ftp(
						[
							'host'    => 'speedtest.tele2.net',
							'port'    => 21,
							'root'    => '/',
							'passive' => true,
							'ssl'     => false,
							'timeout' => 30,
						]
					)
				);
			}
		};

		$kbFile = $ftp->get("1KB.zip")->copy((new File("."))->mkdir("testDir" . random_int(0, 9999999999)));

		self::assertEquals(1024, $kbFile->getSize());
	}

	public function testSearch() {
		self::assertCount(1, self::$dir->search("testFileRenamed"));
		self::assertCount(1, self::$dir->searchDirectory("testDirectoryForCopy"));
		self::$dir->mkdir("testSearch")->createFile("search.txt", "search");
		self::assertCount(1, self::$dir->searchFile("search.txt"));
		self::assertCount(1, self::$dir->searchContent("search"));
	}

	public function testPrintTree() {
		ob_start();
		self::$dir->printTree();
		$content = ob_get_clean();
		self::assertNotEmpty($content);

	}

	public function testDeleteDirectory() {
		self::$dir->get("testDirectory")->delete();
		self::$dir->get("testDirectoryForCopy")->delete();
		self::assertDirectoryNotExists(self::$dir->get("testDirectory")->getPath());
		self::assertDirectoryNotExists(self::$dir->get("testDirectoryForCopy")->getPath());
		self::$dir->get("testDirectory")->mkdir("testDelete");
		self::assertDirectoryExists(self::$dir->get("testDirectory/testDelete")->getPath());

		(new File("test/testDirectory/testDelete"))->delete();
		self::assertDirectoryNotExists("./test/testDirectory/testDelete");
	}
}
