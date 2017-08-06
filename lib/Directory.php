<?php

namespace common\io;

use ArrayAccess;
use common\io\exceptions\DirectoryNotFoundException;
use common\io\exceptions\NoParentAvailableException;
use common\io\filter\Search;
use common\io\filter\SearchContent;
use Countable;
use IteratorAggregate;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use LogicException;

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
	private $dir = NULL;
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
	 * @param string|[] $dir
	 *
	 * @throws \RuntimeException
	 */
	public function __construct($dir) {
		if (is_array($dir)) {
			if (!isset($dir["object"]) || !($dir["object"] instanceof Directory)) {
				throw new \RuntimeException("No parent object given");
			}
			if (!isset($dir["path"])) {
				throw new \RuntimeException("No path given");
			}
			$this->parent          = $dir["object"];
			$this->dirPath         = $dir["path"];
			$this->recursiveFilter = $this->getParent()->getRecursiveFilter();
		} else {
			$this->dir             = Manager::getManager();
			$this->dirPath         = Paths::normalize($dir);
			$this->isRoot          = true;
			$this->recursiveFilter = new FilterArray();
		}
		$this->filter  = new FilterArray();
		$this->dirPath = Paths::trim($this->dirPath);
	}

	/**
	 * @return FilterArray
	 */
	public function getRecursiveFilter(): FilterArray {
		return $this->recursiveFilter;
	}

	/**
	 * get Parent directory
	 *
	 * @return Directory
	 * @throws NoParentAvailableException
	 */
	public function getParent(): Directory {
		return $this->parent();
	}

	/**
	 * get Parent directory
	 *
	 * @return Directory
	 * @throws NoParentAvailableException
	 */
	public function parent(): Directory {
		if ($this->isIsRoot() || is_null($this->parent)) {
			throw new NoParentAvailableException();
		}

		return $this->parent;
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
	 * to string, Echos path
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
		return Paths::trim(Paths::normalize($this->dirPath));
	}

	/**
	 * Rename current directory
	 *
	 * @param string $newName new name
	 *
	 * @return bool
	 */
	public function rename(string $newName): bool {
		if (!$this->isIsRoot()) {
			$newName = $this->getParent()->getPath() . "/" . $newName;
		}

		return $this->getDir()->rename($this->getFullPath(), $newName);
	}

	/**
	 * get Flysystems Filesystem
	 *
	 * @return \League\Flysystem\MountManager
	 */
	public function getDir(): MountManager {
		return $this->isIsRoot() ? $this->dir : $this->getParent()->getDir();
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
	 * get used internal protocol
	 *
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
			$this->getDir()->createDir($this->getFullPath());

			return $this;
		}
	}

	/**
	 * check if this dir exists
	 *
	 * @return bool
	 */
	public function isDirectory(): bool {
		if ($this->isIsRoot()) {
			return true;
		}
		try {
			$this->getDir()->get($this->getFullPath());
			$content = $this->getDir()->listContents($this->getParent()->getFullPath(), false);
			foreach ($content as $item) {
				if ($item["basename"] == $this->getName()) {
					return $item["type"] == "dir";
				}
			}

			return false;

		} catch (FileNotFoundException $e) {
			return false;
		}
	}

	/**
	 * get name of current directory
	 *
	 * @return string
	 */
	public function getName(): string {
		return Paths::getFile(Paths::normalize($this->dirPath));
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
	 * Delete current directory
	 *
	 * @return Directory
	 */
	public function delete() {
		$this->getDir()->deleteDir($this->getFullPath());
		if ($this->isIsRoot()) {
			return NULL;
		} else {
			return $this->getParent();
		}
	}

	private function loadDir() {
		if (is_null($this->dir)) {
			$this->dir = $this->getDir();
			$this->dir->get($this->getFullPath());
		}
	}

	/**
	 * How many files and dirs do we have
	 */
	public function count() {
		return count($this->listContents(false));
	}

	/**
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return array
	 * @throws DirectoryNotFoundException
	 */
	public function listContents(bool $recursion = true): array {
		$a        = [];
		$files    = [];
		$dirs     = [];
		$contents = $this->getDir()->listContents($this->getFullPath(), $recursion);
		if (!$this->isDirectory()) {
			throw new DirectoryNotFoundException();
		}
		foreach ($contents as $file) {
			$file["path"] = "/" . ltrim($file["path"], '/');
			if ($file["type"] == "file") {
				$files[] = $file;
			} else {
				$dirs[$file["path"]] = new Directory(["object" => $this, "path" => $file["path"]]);
			}
		}
		foreach ($dirs as $key => $dir) {
			$add = true;
			foreach ($this->filter as $i => $filter) {
				if (Utils::isClosure($filter)) {
					/**
					 * @var \Closure $filter
					 */
					$add = $add && call_user_func($filter, $dir);
				} else {
					/**
					 * @var Filter $filter
					 */
					$add = $add && $filter->filter($dir);
				}
			}
			if ($add) {
				$a[] = $dir;
			}
		}
		foreach ($files as $file) {
			$path    = Paths::normalize($file["dirname"]);
			$parent  = $path == "/" || !isset($dirs[$path]) ? $this : $dirs[$path];
			$files[] = $f = new File($file["basename"], $parent);

			$add = true;
			foreach ($this->filter as $i => $filter) {
				if (Utils::isClosure($filter)) {
					/**
					 * @var \Closure $filter
					 */
					$add = $add && call_user_func($filter, $f);
				} else {
					/**
					 * @var Filter $filter
					 */
					$add = $add && $filter->filter($f);
				}
			}
			if ($add) {
				$a[] = $f;
			}
		}

		return $a;
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->listContents(false));
	}

	/**
	 * Array access
	 *
	 * @param string $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset) {
		foreach ($this->listContents(false) as $listContent) {
			if ($listContent->getName() == $offset) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Array access
	 *
	 * @param string $offset
	 *
	 * @return Directory|File
	 * @throws FileNotFoundException
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * get a sub directory of current directory
	 *
	 * @param string $path name of directory
	 *
	 * @return Directory|File
	 */
	public function get(string $path) {
		$ex  = explode("/", $path);
		$dir = $this;
		$n   = 1;
		foreach ($ex as $item) {
			$isDir = true;
			if (!$dir->isIsRoot() && $n == count($ex)) {
				$content = $dir->getDir()->listContents($dir->getFullPath());
				foreach ($content as $i) {
					if ($i["basename"] == $item) {
						$isDir = !($i["type"] == "file");
					}
				}
			}
			if ($isDir) {
				$dir = new Directory(["object" => $dir, "path" => $dir->getPath() . "/" . $item]);
			} else {
				$dir = $dir->file($item);
			}
			$n++;
		}

		return $dir;
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
	 * @todo
	 *
	 * @return File
	 */
	public function getFile(string $path): File {
		$this->loadDir();

		return new File($path, $this);
	}

	/**
	 * Array access
	 *
	 * @param string $offset
	 * @param string $value
	 *
	 * @throws LogicException
	 */
	public function offsetSet($offset, $value) {
		$thing = $this->get($offset);
		if (!$thing->isFile() && $thing->isDirectory()) {
			throw  new LogicException("Cannot write to directory.");
		}
		/**
		 * @var Directory|File $thing
		 */

		if ($thing->isFile()) {
			$thing->write($value);
		} else {
			$this->createFile($offset, $value);
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
	 * Array access
	 *
	 * @param string $offset
	 *
	 * @throws FileNotFoundException
	 */
	public function offsetUnset($offset) {
		$this->get($offset)->delete();
	}

	/**
	 * @param string $word
	 *
	 * @return \common\io\File[]
	 */
	public function searchFile(string $word) {
		$index   = $this->addFilter(new Search($word));
		$content = $this->listFiles(true);
		$this->removeFilter($index);

		return $content;
	}

	/**
	 * add an (file) filter
	 *
	 * @param Filter|\Closure $filter
	 * @param bool            $recursive
	 *
	 * @return int filter index
	 */
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

	/**
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return File[]
	 */
	public function listFiles(bool $recursion = true): array {
		$filter = new class extends Filter {
			public function filter($dirOrPath): bool {
				/**
				 * @var Directory|File $dirOrPath
				 */
				return $dirOrPath->isFile();
			}
		};
		$index  = $this->addFilter($filter);
		$result = $this->listContents($recursion);
		$this->removeFilter($index);

		return $result;
	}

	public function removeFilter($index) {
		unset($this->filter[$index]);
		if (isset($this->recursiveFilter[$index])) {
			unset($this->recursiveFilter[$index]);
		}
	}

	/**
	 * @param string $word
	 *
	 * @return \common\io\Directory[]
	 */
	public function searchDirectory(string $word) {
		$index   = $this->addFilter(new Search($word));
		$content = $this->listDirectories(true);
		$this->removeFilter($index);

		return $content;
	}

	/**
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return Directory[]
	 */
	public function listDirectories(bool $recursion = true): array {
		$filter = new class extends Filter {
			public function filter($dirOrPath): bool {
				/**
				 * @var Directory|File $dirOrPath
				 */
				return $dirOrPath->isDirectory();
			}
		};
		$index  = $this->addFilter($filter);
		$result = $this->listContents($recursion);
		$this->removeFilter($index);

		return $result;
	}


	/**
	 * @param $word
	 *
	 * @return File[]
	 */
	public function searchContent($word) {
		$index   = $this->addFilter(new SearchContent($word));
		$content = $this->listFiles(true);
		$this->removeFilter($index);

		return $content;
	}

	/**
	 * search for files which contains given string in their name
	 *
	 * @param string $word
	 *
	 * @return Directory[]|File[]
	 */
	public function search(string $word) {
		$index   = $this->addFilter(new Search($word));
		$content = $this->listContents(true);
		$this->removeFilter($index);

		return $content;
	}

	/**
	 * print a file tree
	 *
	 * @param int $int recursions number
	 */
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
}