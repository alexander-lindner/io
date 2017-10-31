<?php

namespace common\adapters;

use common\io\Directory;

class Local implements Adapter {
	protected $startPath;
	protected $currentPath;
	protected $rootPath;

	public function __construct($startPath = ".", string $currentPath = NULL, $rootPath = "/") {
		if ($currentPath == NULL) {
			$currentPath = getcwd();
		}
		$this->startPath   = $startPath;
		$this->currentPath = $currentPath;
		$this->rootPath    = $rootPath;

		return new Directory($this);
	}

	public function getRootPath()
	: string {
		return $this->rootPath;
	}

	public function getCurrentPath()
	: string {
		return $this->currentPath;
	}

	public function getStartPath()
	: string {
		return $this->startPath;
	}
}