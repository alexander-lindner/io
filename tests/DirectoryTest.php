<?php
use common\io\Directory;

require __DIR__ . '/../vendor/autoload.php';

class DirectoryTest extends PHPUnit\Framework\TestCase {
	public function testExample() {
		$dir = new Directory(".");
		self::assertObjectHasAttribute("parent", $dir);
	}
}
