<?php

use common\io\Directory;
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
		self::assertDirectoryExists("." . $this->dir->get("test")->mkdir()->getPath());
	}

	public function testFileCreate() {
		self::assertFileExists("." . $this->dir->get("test")->createFile("test", "test"));
	}

	public function testParent() {
		self::assertEquals($this->dir, $this->dir->get("lib")->parent());
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
	}

	public function testCopy() {
		self::assertFileExists("." . $this->dir->get("test")->getFile("test")->copy($this->dir->get("test2"))->getPath());
	}

	public function testFile() {
		self::assertEquals($this->dir->get("test")->getFile("test")->md5(), md5_file("." . $this->dir->get("test")->getFile("test")->getPath()));
	}

	public function testDeleteDirectory() {
		$this->dir->get("test")->delete();
		$this->dir->get("test2")->delete();
		self::assertDirectoryNotExists("." . $this->dir->get("test")->getPath());
		self::assertDirectoryNotExists("." . $this->dir->get("test2")->getPath());
	}

	public function __destruct() {
		$this->dir->delete();
	}
}
