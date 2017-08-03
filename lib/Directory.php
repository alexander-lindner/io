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
	 * @param string|array            $dir
	 * @param Filesystem|MountManager $filesystem
	 */
	public function __construct($dir, $filesystem = NULL) {
		if (is_array($dir) && isset($dir["object"]) && $dir["object"] instanceof Directory) {
			$this->parent          = $dir["object"];
			$this->dirPath         = $dir["path"];
			$this->recursiveFilter = $this->getParent()->getRecursiveFilter();
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
		$this->loadDir();

		return $this->dir->rename($this->getFullPath(), $newName);
	}

	private function loadDir() {
		if (is_null($this->dir)) {
			$this->dir = $this->getDir();
			$this->dir->get($this->getFullPath());
		}
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
		return Paths::makePath($this->getProtocol(), Paths::normalize($this->getPath()));
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
			$content = $this->getDir()->listContents($this->getParent()->getFullPath());
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
		$e = explode("/", Paths::normalize($this->dirPath));

		return $e[count($e) - 1];
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
	 * @return Directory|\common\io\File
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
		/**
		 * @var \League\Flysystem\Directory $file
		 */
		$file = $this->dir->get($this->getFullPath() . "/" . $path);

		return new File($file->getPath(), $this);
	}

	/**
	 * Delete current directory
	 *
	 * @return Directory
	 */
	public function delete(): Directory {
		$this->loadDir();
		$this->dir->deleteDir($this->getFullPath());
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
	 * @return Directory[]|File[]
	 */
	public function listContents(bool $recursion = true): array {
		$a = [];
		$this->loadDir();
		$contents = $this->dir->listContents(
			Paths::makePath($this->getProtocol(), $this->dirPath . "."),
			$recursion
		);

		foreach ($contents as $file) {
			$file["path"] = "/" . ltrim($file["path"], '/');
			if ($file["type"] == "file") {
				$dirOrFile = new File($file["path"], $this);
			} else {
				$dirOrFile = new Directory(["object" => $this, "path" => $file["path"]]);
			}
			$add = true;
			foreach ($this->filter as $i => $filter) {
				/**
				 * @var Filter $filter
				 */
				$add = $add && $filter->filter($dirOrFile);
			}
			if ($add) {
				$a[$dirOrFile->getName()] = $dirOrFile;
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
		return isset($this->listContents(false)[$offset]);
	}

	/**
	 * Array access
	 *
	 * @param string $offset
	 *
	 * @return Directory|File
	 * @throws \League\Flysystem\FileNotFoundException
	 */
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

	/**
	 * Array access
	 *
	 * @param string $offset
	 * @param string $value
	 *
	 * @return Directory
	 * @throws \League\Flysystem\FileNotFoundException
	 */
	public function offsetSet($offset, $value) {
		if ($offset == "..") {
			return $this->parent();
		}
		$c = $this->listContents(false);
		if (!isset($c[$offset])) {
			throw new FileNotFoundException($this->dirPath . "/" . $offset);
		}

		/**
		 * @var Directory|File $thing
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
		$this->loadDir();
		$this->getDir()->put($this->getFullPath() . "/" . $name, $content);

		return new File($name, $this);
	}

	/**
	 * Array access
	 *
	 * @param string $offset
	 *
	 * @throws \League\Flysystem\FileNotFoundException
	 */
	public function offsetUnset($offset) {
		$c = $this->listContents(false);
		if (!isset($c[$offset])) {
			throw new FileNotFoundException($this->dirPath . "/" . $offset);
		}

		/**
		 * @var Directory|File $thing
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

	/**
	 * add an (file) filter
	 *
	 * @param Filter $filter
	 * @param bool   $recursive
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
	 * @todo
	 *
	 * @param $word
	 */
	public function searchContent($word) {
		$matches = $this->listFiles(true);
	}

	/**
	 * search for files which contains given string in their name
	 *
	 * @param string $word
	 *
	 * @return array
	 */
	public function search(string $word) {
		$content = $this->listContents(true);

		return $this->fullSearch($word, $content);
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