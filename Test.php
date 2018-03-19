<?php

require_once("vendor/autoload.php");


use common\io\File;
use common\io\filter\Composer;
use common\io\Manager;




$dir = (new File("."))->get("test")->mkdir();
$dir->get("composerTest/vendor")->mkdir()->getParent()->createFile("composer.json","{}")->getParent()->createFile("test.json","{}");
$dir->addFilter(new Composer(),true);
var_dump($dir->get("composerTest")->listContents());

die;
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

$tmp = (new File("."))->mkdir("testDir" . random_int(0, 9999999999));

$kbFile = $ftp->get("1KB.zip")->copy($tmp);

