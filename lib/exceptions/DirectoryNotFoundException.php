<?php

namespace common\io\exceptions;


use League\Flysystem\Exception;

class DirectoryNotFoundException extends Exception {

	/**
	 * DirectoryNotFoundException constructor.
	 */
	public function __construct() {
	}
}