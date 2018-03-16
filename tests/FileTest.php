<?php

use common\io\File;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/../vendor/autoload.php';

class FileTest extends TestCase {
	/**
	 * @var File
	 */
	protected static $file;

	public static function setUpBeforeClass() {
		self::$file = (new File("."))->get("testFile")->mkdir()->createFile("test.ext", "content");
	}

	public static function tearDownAfterClass() {
		(new File("."))->get("testFile")->delete();
		(new File("."))->get("testFileMove")->delete();
	}

	public function testCommon() {
		self::assertInstanceOf(File::class, self::$file);
	}

	public function testMetaData() {
		self::assertEquals(getcwd()."/testFile/test.ext", self::$file->getPath());
		self::assertEquals(md5_file("./testFile/test.ext"), self::$file->md5());
		self::assertEquals(sha1_file("./testFile/test.ext"), self::$file->sha1());
		self::assertEquals("ext", self::$file->getExtension());
		self::assertEquals(self::$file, self::$file->getPath());
		self::assertTrue(self::$file->isFile());
		self::assertTrue(self::$file->isReadable());
		self::assertTrue(self::$file->isWritable());
		self::assertEquals("text/plain", self::$file->getMimeType());
		self::assertEquals("content", self::$file->getContent());
		self::assertLessThanOrEqual(time(), self::$file->getModifiedTime());
	}


	public function testDelete() {
		self::$file->delete();
		self::assertFileNotExists("./testFile/test.ext");
		self::$file = (new File("."))->get("testFile")->mkdir()->createFile("test.ext", "content");
	}

	public function testMove() {
		self::$file = self::$file->move((new File("."))->get("testFileMove")->mkdir());
		self::assertFileNotExists("./testFile/test.ext");
		self::assertFileExists("./testFileMove/test.ext");
	}
}
