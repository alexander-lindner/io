<?php

namespace common\io;

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;

/**
 * Class Directory
 *
 * @package common\io
 */
class Directory {
	/**
	 * @var string
	 */
	protected $protocol = "file";
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
	private $parent = NULL;

	/**
	 * Directory constructor.
	 *
	 * @param string|array            $dir
	 * @param Filesystem|MountManager $filesystem
	 */
	public function __construct($dir, $filesystem = NULL) {
		if (is_array($dir) && isset($dir["object"]) && $dir["object"] instanceof Directory) {
			$this->parent   = $dir["object"];
			$this->dir      = $this->parent->getDir();
			$this->dirPath  = $dir["path"];
			$this->protocol = $this->parent->getProtocol();
		} else {
			if (is_null($filesystem)) {
				$filesystem = Manager::getManager();
			}
			$this->dir     = $filesystem;
			$this->dirPath = Paths::normalize($dir);
			$this->isRoot  = true;
		}
		$this->dirPath = "/" . rtrim(ltrim($this->dirPath, '/'), '/') . '/';
	}

	/**
	 * get Flysystems Filesystem
	 *
	 * @return MountManager
	 */
	public function getDir(): MountManager {
		return $this->dir;
	}

	/**
	 * @return string
	 */
	public function getProtocol(): string {
		if ($this->isIsRoot()) {
			return $this->protocol;
		} else {
			return $this->getParent()->getProtocol();
		}
	}

	/**
	 * check if it is root directory
	 *
	 * @return bool
	 */
	public function isIsRoot(): bool {
		return $this->isRoot;
	}

	/**
	 * get Parent directory
	 *
	 * @return Directory
	 * @throws \common\io\NoParentAvailableException
	 */
	public function getParent(): Directory {
		return $this->parent();
	}

	/**
	 * get Parent directory
	 *
	 * @return Directory
	 * @throws \common\io\NoParentAvailableException
	 */
	public function parent(): Directory {
		if ($this->isIsRoot() || is_null($this->parent)) {
			throw new NoParentAvailableException();
		}

		return $this->parent;
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
	 * get current path
	 *
	 * @return string
	 */
	public function getPath(): string {
		return Paths::normalize($this->dirPath);
	}

	/**
	 * get current path with protocol
	 *
	 * @return string
	 */
	public function getFullPath(): string {
		return Paths::normalize(Paths::makePath($this->getProtocol(), $this->getPath()));
	}

	/**
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return array(@var int => @var File|Directory)
	 */
	public function listContents(bool $recursion = true): array {
		$a        = [];
		$contents = $this->dir->listContents(
			Paths::makePath($this->getProtocol(), $this->dirPath . "."),
			$recursion
		);
		foreach ($contents as $file) {
			$file["path"] = "/" . ltrim($file["path"], '/');
			if ($file["type"] == "file") {
				$a[] = new File($file["path"], $this);
			} else {
				$a[] = new Directory(["object" => $this, "path" => $file["path"]]);
			}
		}

		return $a;
	}

	/**
	 * Rename current directory
	 *
	 * @param string $newName new Name
	 *
	 * @return bool
	 */
	public function rename(string $newName): bool {
		return $this->dir->rename(Paths::makePath($this->getProtocol(), $this->dirPath), $newName);
	}

	/**
	 * create current or given path
	 *
	 * @param string $path path which should be create
	 *
	 * @return Directory
	 */
	public function mkdir(string $path = ""): Directory {
		if (!empty($path)) {
			$dir = new Directory(["object" => $this, "path" => $this->dirPath . "/" . $path]);
			if (!$dir->isDirectory()) {
				$dir->mkdir();
			}

			return $dir;
		} else {
			$this->dir->createDir(Paths::makePath($this->getProtocol(), $this->dirPath));

			return $this;
		}
	}

	/**
	 * check if this dir exists
	 *
	 * @return bool
	 */
	public function isDirectory(): bool {
		return $this->dir->has(Paths::makePath($this->getProtocol(), $this->dirPath));
	}

	/**
	 * Check if it is a file
	 * Useful for listContents loop
	 *
	 * @return bool
	 */
	public function isFile(): bool {
		return false;
	}

	/**
	 * get name of current directory
	 *
	 * @return string
	 */
	public function getName(): string {
		$e = explode("/", Paths::normalize($this->dirPath));

		return $e[count($e) - 1];
	}


	/**
	 * creates a file
	 *
	 * @param string $name    name of file
	 * @param string $content content
	 *
	 * @return File
	 */
	public function createFile(string $name, string $content = ""): File {
		$this->getDir()->put(Paths::makePath($this->getProtocol(), $name), $content);

		return new File($name, $this);
	}

	/**
	 * get a sub directory of current directory
	 *
	 * @param string $path name of directory
	 *
	 * @return Directory
	 */
	public function get(string $path): Directory {
		$path = $this->dirPath . $path;
		/**
		 * @var \League\Flysystem\Directory
		 */
		$directory = $this->dir->get(Paths::makePath($this->getProtocol(), $path));

		return new Directory(["object" => $this, "path" => $directory->getPath()]);
	}

	/**
	 * get a existing file in current directory
	 *
	 * @param string $file file name
	 *
	 * @return File
	 */
	public function file(string $file): File {
		return $this->getFile($file);
	}

	/**
	 * get a existing file in current directory
	 *
	 * @param string $path file name
	 *
	 * @return File
	 */
	public function getFile(string $path): File {
		$path = Paths::normalize($this->dirPath . $path);

		/**
		 * @var \League\Flysystem\Directory
		 */
		$file = $this->dir->get(Paths::makePath($this->getProtocol(), $path));

		return new File($file->getPath(), $this);
	}

	/**
	 * Delete current directory
	 *
	 * @return Directory
	 */
	public function delete(): Directory {
		$this->dir->deleteDir(Paths::makePath($this->getProtocol(), $this->dirPath));
		if ($this->isIsRoot()) {
			return NULL;
		} else {
			return $this->getParent();
		}
	}
}