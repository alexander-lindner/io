<?php

namespace common\libs\io;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memory as CacheStore;
use League\Flysystem\Filesystem;

class Directory {
	/**
	 * @var Filesystem
	 */
	private $dir;
	/**
	 * @var string
	 */
	private $dirPath;

	/**
	 * @var bool
	 */
	private $isRoot = false;
	/**
	 * @var Directory
	 */
	private $parent;

	/**
	 * Directory constructor.
	 *
	 * @param $dir
	 */
	public function __construct($dir, $filesystem = NULL) {
		if (is_array($dir) && isset($dir["object"]) && $dir["object"] instanceof Directory) {
			$this->parent = $dir["object"];
			$this->dir = $this->parent->getDir();
			$this->dirPath = $dir["path"];
		} else {
			if (is_null($filesystem)) {
				$adapter = new Local($dir);
				$cacheStore = new CacheStore();
				$filesystem = new Filesystem(new CachedAdapter($adapter, $cacheStore));
			}
			if (is_dir(getcwd() . "/" . $dir)) {
				$dir = getcwd() . "/" . $dir;
			}
			$this->dir = $filesystem;
			$this->dirPath = $dir;
			$this->isRoot = true;
		}
	}

	/**
	 * return current virtual path
	 *
	 * @return string
	 */
	private function getCurrentPath() {
		$o = $this;
		$paths = [];
		while (!$o->isIsRoot()) {
			$paths[] = $o->getRelativePath();
			$o = $o->getParent();
		}
		$paths[] = $o->getRelativePath();
		return implode("/", array_reverse($paths));
	}

	/**
	 * check if this dir exists
	 *
	 * @return bool
	 */
	public function isDirectory() {
		return is_array($this->dir->listContents());
	}

	/**
	 * to string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getPath();
	}

	/**
	 * get current relative path
	 *
	 * @return string
	 */
	public function getRelativePath() {
		return $this->dirPath;
	}

	/**
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return array(@var File, @var Directory)
	 */
	public function listContents($recursion = true) {
		$a = [];
		foreach ($this->dir->listContents("", $recursion) as $file) {
			if ($file["type"] == "file") {
				$a[] = new File($file["path"], $this);
			} else {
				$a[] = new Directory(["object" => $this, "path" => $file["path"]]);
			}

		}

		return $a;
	}

	public function get($path) {
		/**
		 * @var \League\Flysystem\Directory
		 */
		$directory = $this->dir->get($path);
		return new Directory(["object" => $this, "path" => $directory->getPath()]);
	}

	public function parent() {
		if ($this->isIsRoot()) {
			throw new NoParentAvailableException();
		}
		return $this->parent;
	}
	public function getParent() {
		return $this->parent();
	}

	public function rename($newName) {
		return $this->dir->rename($this->dirPath, $newName);
	}

	public function mkdir() {
		$this->dir->createDir($this->dirPath);
	}

	/**
	 * @return bool
	 */
	public function isIsRoot() {
		return $this->isRoot;
	}

	public function getPath() {
		return Paths::normalize($this->getCurrentPath());
	}

	/**
	 * @return Filesystem
	 */
	public function getDir() {
		return $this->dir;
	}

	public function isFile() {
		return false;
	}

}