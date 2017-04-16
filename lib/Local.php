<?php

namespace common\io;

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;

/**
 * Class Local
 *
 * @package common\io
 */
class Local extends Directory {
	/**
	 * Local constructor.
	 *
	 * @param string|array            $dir
	 * @param Filesystem|MountManager $filesystem
	 */
	public function __construct($dir, $filesystem = NULL) {
		parent::__construct($dir, $filesystem);
		$this->protocol = "local";
	}
}