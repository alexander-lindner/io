# common/io [![Packagist](https://img.shields.io/packagist/v/common-libs/io.svg?style=flat-square)](https://packagist.org/packages/common-libs/io)  [![license](https://img.shields.io/github/license/common-libs/io.svg?style=flat-square)](https://github.com/common-libs/io)  [![codecov](https://codecov.io/gh/common-libs/io/branch/master/graph/badge.svg)](https://codecov.io/gh/common-libs/io) [![Travis](https://img.shields.io/travis/common-libs/io.svg?style=flat-square)](https://travis-ci.org/common-libs/io)

***common/io*** is a simple and powerful I/O library. It wraps the popular [Flysystem](https://flysystem.thephpleague.com/) 
to a oop structure and adds helpful utils.

````php
<?php
use common\io\File;
use common\io\Manager;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

$webdav = new class(".") extends File {
	public function __construct($dir) {
		$this->defaultUrl = ["scheme" => "dav"];
		$this->workingDir = "/";
		parent::__construct($dir);

		Manager::addAdapter(
			$this->getProtocol(),
			new WebDAVAdapter(
				new Client(
					[
						'baseUri'  => 'https://owncloud.domain.tld/',
						'userName' => 'user',
						'password' => '...'
					]
				), "/remote.php/webdav/"
			)
		);
	}
};

$local = new File(".");
$local
    ->get("README.md")
    ->copy($webdav->mkdir("testDir"));
````
## Installation

Run: `composer require common-libs/io`

As always load composer in your main file: `require_once("vendor/autoload.php");`.

## Use it
Using it is very simple. Just initialize a new php object from ***common\io\Directory***.
  ````php
  <?php
  use common\io\File;
    
  $test = new File(".");
  ````
  List all files and dirs:
 ````php
 <?php
 use common\io\File;
   
 $test = new File(".");
 foreach ($test->listContents() as $listContent) {
    echo $listContent->getPath();
 }
 ````
 Get content of a directory structure:
  ````php
  <?php
  use common\io\File;

  $test = new File(".");
  foreach ($test->get("vendor/bin")->listContents() as $listContent) {
    echo $listContent->getPath();
  }
  ````
  
 `commons\io\Directory` implements **Countable, IteratorAggregate, ArrayAccess**, so it can be shorten:
   ````php
   <?php
   use common\io\File;
 
   $test = new File(".");
   foreach ($test->get("vendor/bin") as $listContent) {
    echo $listContent->getPath();
   }
   ```` 
````php
<?php
use common\io\File;

$test = new File(".");
foreach ($test["vendor"]["bin"] as $listContent) {
    echo $listContent->getPath();
}
```` 
   
 
 
 Extending this class gives you the possibility to use any flysystem adapter:

```php
<?php
use common\io\File;
use common\io\Manager;
use League\Flysystem\Adapter\Ftp as Adapter;

class Ftp extends File {
	public function __construct($dir) {
		$this->defaultUrl = ["scheme" => "ftp"];
		$this->workingDir = "/";
		
		parent::__construct($dir);
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
The protocol/scheme is used as a class internal virtual mapping and identifier.

See [![Documentation](https://img.shields.io/badge/Documentation-api-orange.svg?style=flat-square)](https://common-libs.github.io/io/) for a full list of available methods.

## Example
```php
<?php

use common\io\File;
use common\io\Manager;

$local = new File("."); //current dir in vendor/common-libs/io/

$file = $local->createFile("test", "hi"); // create file "test" with content hi
echo $file->getContent() . PHP_EOL; // hi
echo $file->md5() . PHP_EOL; // 49f68a5c8493ec2c0bf489821c21fc3b

/* list all files and dirs recursive and prints their paths & if an file "composer.json" is found more infos are printed */
foreach ($local->listContents() as $listContent) { 
	echo $listContent->getPath() . PHP_EOL;
	if ($listContent->isFile()) {
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

$local = $lib->getParent(); //redundant, just for demonstration

/* using php7 to get a new ftp object */
$ftp = new class(".") extends File {
	public function __construct($dir) {
		$this->defaultUrl = ["scheme" => "ftp"];
		$this->workingDir = "/";
        		
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

/* copy "100KB.zip" on ftp server to local dir "testDirRANDOMNUMBER" */
$kbFile    = $ftp->get("100KB.zip")->copy(
	$local->mkdir("testDir" . random_int(0, 9999999999))
);
$randomDir = $kbFile->delete(); // delete downloaded file
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
