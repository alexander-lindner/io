<?php

namespace common\io;


use League\Flysystem\Exception;

class DirectoryNotFoundException extends Exception {

	/**
	 * DirectoryNotFoundException constructor.
	 */
	public function __construct() {
	}
}