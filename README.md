# common/io [![Packagist](https://img.shields.io/packagist/v/common-libs/io.svg?style=flat-square)](https://github.com/common-libs/io) [![Packagist Pre Release](https://img.shields.io/packagist/vpre/common-libs/io.svg?style=flat-square)](https://github.com/common-libs/io) [![HHVM](https://img.shields.io/hhvm/common-libs/io.svg?style=flat-square)](https://github.com/common-libs/io) [![license](https://img.shields.io/github/license/common-libs/io.svg?style=flat-square)](https://github.com/common-libs/io) [![codecov](https://codecov.io/gh/common-libs/io/branch/master/graph/badge.svg)](https://codecov.io/gh/common-libs/io) [![Travis](https://img.shields.io/travis/common-libs/io.svg?style=flat-square)](https://github.com/common-libs/io)

***common/io*** brings simple oop based file & directory management to your php project. It's based on [Flysystem](https://flysystem.thephpleague.com/).

## Installation

Run: `composer require common-libs/io`

As always load composer in your main file: `require_once("vendor/autoload.php");`.

## Use it
Using it is very simple. Just initialize a new php object from ***common\io\Directory***. Extending this class gives you the possibility to use any flysystem adapter:

```php
<?php

namespace common\io;

use League\Flysystem\Adapter\Ftp as Adapter;

class Ftp extends Directory {
	public function __construct($dir, $filesystem = NULL) {
		$this->protocol = "ftp";
		parent::__construct($dir, $filesystem);
		Manager::addAdapter(
			$this->getProtocol(),
			new Adapter(
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
}
```
The protocol is used as a class internal virtual mapping and identifier. If you are working in your project dir you can use  ***common\io\Local***. Those objects represents a directory. Once you've initialized such an object you can use a lot of methods, see [![Documentation](https://img.shields.io/badge/Documentation-api-orange.svg?style=flat-square)](https://common-libs.github.io/io/) for a full list of available methods.

When getting a file using`Directory->getFile()` or `Directory->listContents()` you get a new ***common\io\File*** object.

## Example
```php
<?php

use common\io\Directory;
use common\io\File;
use common\io\Local;
use common\io\Manager;

$local = new Local("."); //current dir in vendor/common-libs/io/

$file = $local->createFile("test", "hi"); // create file "test" with content hi
echo $file->getContent() . PHP_EOL; // hi
echo $file->md5() . PHP_EOL; // 49f68a5c8493ec2c0bf489821c21fc3b

/* list all files and dirs recursive and prints their paths & if an file "composer.json" is found more infos are printed */
foreach ($local->listContents() as $listContent) { 
	/**
	 * @var Directory|File $listContent
	 */
	echo $listContent->getPath() . PHP_EOL;
	if ($listContent->isFile()) {
		/**
		 * @var common\io\File $listContent
		 */
		if ($listContent->getName() == "composer.json") {
			echo "size: " . $listContent->getSize() . PHP_EOL;
			echo json_decode($listContent->getContent());
		}
	}
}

$lib = $local->get("lib"); // change to dir "lib"

/* list all files and dirs recursive and prints their paths */
foreach ($lib->listContents() as $listContent) {
	echo $listContent->getPath() . PHP_EOL;
}

$local = $lib->parent(); //redundant, just for demonstration

/* using php7 to get a new ftp object */
$ftp = new class(".") extends Directory {
	public function __construct($dir, $filesystem = NULL) {
		parent::__construct($dir, $filesystem);
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
$kbFile    = $ftp->getFile("100KB.zip")->copy(
	$local->mkdir("testDir" . random_int(0, 9999999999))
);
$randomDir = $kbFile->delete(); // delete downloaded file
$local     = $randomDir->delete(); // delete testDir
```
## License

*****GNU GPL v3*****

    Copyright (C) 2017  Profenter Systems

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License 
    along with this program. If not, see <http://www.gnu.org/licenses/>.
