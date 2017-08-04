<?php

use common\io\Directory;
use common\io\File;
use common\io\NoParentAvailableException;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/../vendor/autoload.php';

class DirectoryTest extends TestCase {
	/**
	 * @var Directory
	 */
	protected $dir;

	public function __construct($name = NULL, array $data = [], $dataName = '') {
		parent::__construct($name, $data, $dataName);
		(new Directory("."))->get("test")->mkdir();
		$this->dir = new Directory("./test/");
	}

	public function testMkdir() {
		self::assertDirectoryExists("." . $this->dir->get("testDirectory")->mkdir()->getPath());
		self::assertDirectoryExists("." . $this->dir->get("testDirectory")->mkdir("testMkdir")->getPath());
	}

	public function testFileCreate() {
		self::assertFileExists("." . $this->dir->get("testDirectory")->createFile("testFile", "test")->getPath());
	}

	public function testParent() {
		self::assertEquals($this->dir, $this->dir->get("testDirectory")->parent());
		self::expectException(NoParentAvailableException::class);
		$this->dir->parent();
	}

	public function testListContents() {
		self::assertInternalType('array', $this->dir->listContents(false));
		self::assertGreaterThan(0, count($this->dir->listContents(true)));
		foreach ($this->dir->listFiles() as $listFile) {
			self::assertTrue($listFile->isFile());
		}
		foreach ($this->dir->listDirectories() as $listFile) {
			self::assertTrue($listFile->isDirectory());
		}
		foreach ($this->dir as $list) {
			self::assertDirectoryExists("." . $list->getPath());
		}
		foreach ($this->dir["testDirectory"] as $list) {
			if ($list->isDirectory()) {
				self::assertDirectoryExists("." . $list->getPath());
			}
		}
	}

	public function testCopy() {
		self::assertFileExists("." . $this->dir->get("testDirectory")->getFile("testFile")->copy($this->dir->get("testDirectoryForCopy"))->getPath());
	}

	public function testRename() {
		self::assertTrue($this->dir->get("testDirectory/subTestDirectory")->mkdir()->rename("subTestDirectoryRenamed"));
		self::assertDirectoryExists("." . $this->dir->get("testDirectory/subTestDirectoryRenamed")->getPath());

		self::assertTrue($this->dir->get("testDirectory/testFile")->rename("testFileRenamed"));
		self::assertFileExists("." . $this->dir->get("testDirectory/testFileRenamed")->getPath());
	}

	public function testFile() {
		self::assertEquals(
			$this->dir->get("testDirectory")->getFile("testFileRenamed")->md5(),
			md5_file("." . $this->dir->get("testDirectory")->getFile("testFileRenamed")->getPath())
		);
		self::assertEquals(
			File::get("test/testDirectory/testFileRenamed")->md5(),
			md5_file("." . $this->dir->get("testDirectory")->getFile("testFileRenamed")->getPath())
		);
	}

	public function testVarious() {
		self::assertEquals((string)$this->dir->get("lib"), $this->dir->get("lib")->getPath());
		self::assertTrue($this->dir->get("testDirectory")->isDirectory());
	}

	public function testDeleteDirectory() {
		$this->dir->get("testDirectory")->delete();
		$this->dir->get("testDirectoryForCopy")->delete();
		self::assertDirectoryNotExists("." . $this->dir->get("testDirectory")->getPath());
		self::assertDirectoryNotExists("." . $this->dir->get("testDirectoryForCopy")->getPath());
	}


	public function __destruct() {
		$this->dir->delete();

	}
}
