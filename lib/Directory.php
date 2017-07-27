<?php

namespace common\io;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;

/**
 * Class Directory
 *
 * @package common\io
 */
class Directory implements Countable, IteratorAggregate, ArrayAccess {
	/**
	 * @var string
	 */
	protected $protocol = "file";
	/**
	 * @var FilterArray
	 */
	protected $filter;
	/**
	 * @var FilterArray
	 */
	protected $recursiveFilter;
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
			$this->parent          = $dir["object"];
			$this->dir             = $this->parent->getDir();
			$this->dirPath         = $dir["path"];
			$this->protocol        = $this->parent->getProtocol();
			$this->recursiveFilter = $dir["filter"];
		} else {
			if (is_null($filesystem)) {
				$filesystem = Manager::getManager();
			}
			$this->dir             = $filesystem;
			$this->dirPath         = Paths::normalize($dir);
			$this->isRoot          = true;
			$this->recursiveFilter = new FilterArray();
		}
		$this->filter  = new FilterArray();
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
			$dir = new Directory(["object" => $this, "path" => $this->dirPath . "/" . $path, "filter" => $this->recursiveFilter]);
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

		return new Directory(["object" => $this, "path" => $directory->getPath(), "filter" => $this->recursiveFilter]);
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
		 * @var \League\Flysystem\Directory $file
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

	/**
	 * How many files and dirs do we have
	 */
	public function count(): int {
		return count($this->listContents(false));
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
				$dirOrFile = new File($file["path"], $this);
			} else {
				$dirOrFile = new Directory(["object" => $this, "path" => $file["path"], "filter" => $this->recursiveFilter]);
			}
			$add = true;
			foreach ($this->filter as $i => $filter) {
				/**
				 * @var \common\io\Filter $filter
				 */
				$add = $add && $filter->filter($dirOrFile);
			}
			if ($add) {
				$a[$dirOrFile->getName()] = $dirOrFile;
			}
		}

		return $a;
	}

	public function getIterator() {
		return new \ArrayIterator($this->listContents(false));
	}

	public function offsetExists($offset) {
		return isset($this->listContents(false)[$offset]);
	}

	public function offsetGet($offset) {
		if ($offset == "..") {
			return $this->parent();
		}
		$c = $this->listContents(false);
		if (!isset($c[$offset])) {
			throw new FileNotFoundException($this->dirPath . "/" . $offset);
		}

		return $c[$offset];
	}

	public function offsetSet($offset, $value) {
		if ($offset == "..") {
			return $this->parent();
		}
		$c = $this->listContents(false);
		if (!isset($c[$offset])) {
			throw new FileNotFoundException($this->dirPath . "/" . $offset);
		}

		/**
		 * @var \common\io\Directory|\common\io\File $thing
		 */
		$thing = $c[$offset];

		if ($thing->isFile()) {
			$thing->write($value);
		} else {
			$thing->createFile($value);
		}
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
		$this->getDir()->put($this->getFullPath() . "/" . $name, $content);

		return new File($name, $this);
	}

	/**
	 * get current path with protocol
	 *
	 * @return string
	 */
	public function getFullPath(): string {
		return Paths::normalize(Paths::makePath($this->getProtocol(), $this->getPath()));
	}

	public function offsetUnset($offset) {
		$c = $this->listContents(false);
		if (!isset($c[$offset])) {
			throw new FileNotFoundException($this->dirPath . "/" . $offset);
		}

		/**
		 * @var \common\io\Directory|\common\io\File $thing
		 */
		$thing = $c[$offset];
		$thing->delete();
	}

	public function searchFile(string $word) {
		$content = $this->listFiles(true);

		return $this->fullSearch($word, $content);
	}

	/**
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return array(@var int => @var File|Directory)
	 */
	public function listFiles(bool $recursion = true): array {
		$filter = new class extends Filter {
			public function filter($dirOrPath): bool {
				/**
				 * @var \common\io\Directory|\common\io\File $dirOrPath
				 */
				return $dirOrPath->isFile();
			}
		};
		$index  = $this->addFilter($filter);
		$result = $this->listContents($recursion);
		$this->removeFilter($index);

		return $result;
	}

	public function addFilter($filter, bool $recursive = false) {
		if (!is_object($filter)) {
			$filter = new $filter;
		}

		$this->filter[] = $filter;

		if ($recursive) {
			$this->recursiveFilter[$this->filter->getCounter()] = $filter;
		}

		return $this->filter->getCounter();
	}

	public function removeFilter($index) {
		$this->filter[$index] = NULL;
		if (isset($this->recursiveFilter[$index])) {
			unset($this->recursiveFilter[$index]);
		}
	}

	public function fullSearch(string $word, $content) {
		$word = strtolower($word);

		$content = array_change_key_case($content, CASE_LOWER);
		$matches = preg_grep('/(.*)' . $word . '(.*)/m', $content);

		return $matches;
	}

	public function searchDirectory(string $word) {
		$content = $this->listDirectories(true);

		return $this->fullSearch($word, $content);
	}

	/**
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return array(@var int => @var File|Directory)
	 */
	public function listDirectories(bool $recursion = true): array {
		$filter = new class extends Filter {
			public function filter($dirOrPath): bool {
				/**
				 * @var \common\io\Directory|\common\io\File $dirOrPath
				 */
				return $dirOrPath->isDirectory();
			}
		};
		$index  = $this->addFilter($filter);
		$result = $this->listContents($recursion);
		$this->removeFilter($index);

		return $result;
	}

	public function searchContent($word) {
		$matches = $this->listFiles(true);
	}

	public function search(string $word) {
		$content = $this->listContents(true);

		return $this->fullSearch($word, $content);
	}

	public function printTree($int = 0) {
		$signs = "";
		for ($i = 0; $i <= $int; $i++) {
			$signs .= "   ";
		}
		$signs .= "---";
		echo $this->getName() . PHP_EOL;
		foreach ($this->listContents(false) as $value) {
			echo $signs . $value->getName() . PHP_EOL;
			if ($value->isDirectory()) {
				$value->printTree($int++);
			}
		}
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
}