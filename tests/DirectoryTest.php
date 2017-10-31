<?php

use common\adapters\Local;
use common\io\Directory;
use common\io\exceptions\DirectoryNotFoundException;
use common\io\exceptions\NoParentAvailableException;
use common\io\File;
use common\io\Manager;
use common\io\Paths;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/../vendor/autoload.php';

class DirectoryTest extends TestCase {
	/**
	 * @var Directory
	 */
	protected static $dir;

	public static function setUpBeforeClass() {
		( new Local(".") )->get("test")->mkdir();
		self::$dir = new Local("./test/");
	}

	public static function tearDownAfterClass() {
		self::$dir->delete();
	}

	public function testMkdir() {
		self::assertDirectoryExists("." . self::$dir->get("testDirectory")->mkdir()->getPath());
		self::assertDirectoryExists("." . self::$dir->get("testDirectory")->mkdir("testMkdir")->getPath());
	}

	public function testFileCreate() {
		self::assertFileExists("." . self::$dir->get("testDirectory")->createFile("testFile", "test")->getPath());
	}

	public function testParent() {
		self::assertEquals(self::$dir, self::$dir->get("testDirectory")->parent());
		self::expectException(NoParentAvailableException::class);
		self::$dir->parent();
	}

	public function testDirectCall() {
		self::expectException(NoParentAvailableException::class);
		new Directory(".");
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
			self::assertDirectoryExists("." . $list->getPath());
		}
		foreach (self::$dir["testDirectory"] as $list) {
			if ($list->isDirectory()) {
				self::assertDirectoryExists("." . $list->getPath());
			}
		}
		self::expectException(DirectoryNotFoundException::class);
		self::$dir->get(random_int(0, PHP_INT_MAX))->listContents();
	}

	public function testInit() {
		self::expectException(RuntimeException::class);
		new Directory([]);

	}

	public function testInit2() {
		self::expectException(RuntimeException::class);
		new Directory(["object" => new Directory(".")]);
	}

	public function testCopy() {
		self::assertFileExists("." . self::$dir->get("testDirectory")->getFile("testFile")->copy(self::$dir->get("testDirectoryForCopy"))->getPath());
	}

	public function testRename() {
		self::assertTrue(self::$dir->get("testDirectory/subTestDirectory")->mkdir()->rename("subTestDirectoryRenamed"));
		self::assertDirectoryExists("." . self::$dir->get("testDirectory/subTestDirectoryRenamed")->getPath());

		self::assertTrue(self::$dir->get("testDirectory/testFile")->rename("testFileRenamed"));
		self::assertFileExists("." . self::$dir->get("testDirectory/testFileRenamed")->getPath());
	}

	public function testFile() {
		self::assertEquals(
			self::$dir->get("testDirectory")->getFile("testFileRenamed")->md5(),
			md5_file("." . self::$dir->get("testDirectory")->getFile("testFileRenamed")->getPath())
		);
		self::assertEquals(
			File::get("test/testDirectory/testFileRenamed")->md5(),
			md5_file("." . self::$dir->get("testDirectory")->getFile("testFileRenamed")->getPath())
		);
	}

	public function testVarious() {
		self::assertEquals((string)self::$dir->get("lib"), self::$dir->get("lib")->getPath());
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
		self::assertFileNotExists("." . self::$dir["testDirectory"]["testFileArrayAccess.txt"]->getPath());
	}

	public function testAdapter() {
		$ftp = new class(".") extends Directory {
			public function __construct($dir) {
				parent::__construct($dir);
				$this->protocol = "ftp"; // set virtual protocol
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

		/* copy "100KB.zip" on ftp server to local dir "testDirRANDOMNUMBER" */
		$kbFile = $ftp->getFile("1KB.zip")->copy(
			self::$dir->mkdir("testDir" . random_int(0, 9999999999))
		);
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
		self::assertDirectoryNotExists("." . self::$dir->get("testDirectory")->getPath());
		self::assertDirectoryNotExists("." . self::$dir->get("testDirectoryForCopy")->getPath());
		self::$dir->get("testDirectory")->mkdir("testDelete");
		$null = (new Directory("test/testDirectory/testDelete"))->delete();
		self::assertNull($null);
		self::assertDirectoryNotExists("./test/testDirectory/testDelete");
	}
}
