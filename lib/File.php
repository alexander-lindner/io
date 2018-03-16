<?php

namespace common\io;

use ArrayAccess;
use ArrayIterator;
use common\io\exceptions\DirectoryNotFoundException;
use common\io\exceptions\NoParentAvailableException;
use common\io\filter\Search;
use common\io\filter\SearchContent;
use Countable;
use IteratorAggregate;
use League\Flysystem\MountManager;
use LogicException;
use Traversable;

/**
 * Class File
 *
 * @package common\io
 */
class File implements Countable, ArrayAccess, IteratorAggregate {
	/**
	 * @var MountManager
	 */
	protected $manager;
	/**
	 * @var string
	 */
	protected $url;
	/**
	 * @var FilterArray
	 */
	protected $filter;
	/**
	 * @var FilterArray
	 */
	protected $recursiveFilter;

	/**
	 * @var string
	 */
	protected $workingDir;
	/**
	 * @var []
	 */
	protected $defaultUrl = ["scheme" => "file"];

	/**
	 * File constructor.
	 *
	 * @param string $path
	 */
	public function __construct(string $path = NULL) {
		if ($path === NULL || empty($path)) {
			$this->url = Paths::makeUrl($this->workingDir ?? getcwd(), $this->defaultUrl);
		} else {
			if (strpos($path, "://") !== false) {
				$this->url = $path;
			} else {
				if (Utils::startsWith($path, ".") || !Utils::startsWith($path, "/")) {
					$this->url = Paths::appendPath(Paths::makeUrl($this->workingDir ?? getcwd(), $this->defaultUrl), $path);
				} else {
					$this->url = Paths::makeUrl($path, $this->defaultUrl);
				}
			}
		}

		$this->manager         = Manager::getManager();
		$this->filter          = new FilterArray();
		$this->recursiveFilter = new FilterArray();
	}

	/**
	 * get file path
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->getAbsolutePath();
	}
	/*========================= COMMON =============================*/
	/**
	 * check if this file exists
	 *
	 * @return bool
	 */
	public function isFile(): bool {
		return $this->manager->has($this->getUrl()) && !$this->isDirectory();
	}

	/**
	 * get used internal protocol
	 *
	 * @return string
	 */
	public function getProtocol(): string {
		return Paths::parseUrl($this->url)["scheme"];
	}

	/**
	 * get file name
	 *
	 * @return string
	 */
	public function getName(): string {
		return Paths::getFile($this->getAbsolutePath());
	}


	/**
	 * rename file
	 *
	 * @param string $newName new file name
	 *
	 * @return bool
	 */
	public function rename(string $newName): bool {
		return $this->manager->rename($this->getUrl(), Paths::getPath($this->getAbsolutePath()) . "/" . $newName);
	}


	/**
	 * copy this file to an other place
	 *
	 * @param File $to other place
	 *
	 * @return File
	 */
	public function copy(File $to): File {
		$this->manager->copy(
			$this->getUrl(),
			$path = Paths::normalize(Paths::appendPath($to->getUrl(), $this->getName()))
		);

		return new File($path);
	}


	/*========================= DIRECTORY =============================*/

	/**
	 * check if this file is readable
	 *
	 * @return bool
	 */
	public function isReadable(): bool {
		return is_string($this->manager->read($this->getUrl()));
	}

	/**
	 * check if this file is writable
	 *
	 * @return bool
	 */
	public function isWritable(): bool {
		return $this->manager->getVisibility($this->getUrl()) == "public";
	}

	/**
	 * create current or given path
	 *
	 * @param string $path path which should be create
	 *
	 * @return File
	 */
	public function mkdir(string $path = ""): File {
		if (!empty($path)) {
			$dir = new File(Paths::appendPath($this->getUrl(), $path));
			$dir->mkdir();

			return $dir;
		} else {
			if (!$this->isDirectory() && !$this->isFile()) {
				$this->manager->createDir($this->getUrl());
			}

			return $this;
		}
	}

	/**
	 * delete this file
	 */
	public function delete() {
		if ($this->isDirectory()) {
			$this->manager->deleteDir($this->getUrl());
		} else {
			$this->manager->delete($this->getUrl());
		}
	}

	/**
	 * check if this dir exists
	 *
	 * @return bool
	 */
	public function isDirectory(): bool {
		if ($this->isRoot()) {
			return true;
		}

		$content = $this->manager->listContents(
			$this->getParent()->getUrl(),
			false
		);

		return count(
				array_filter(
					$content,
					function ($item) {
						if ($item["basename"] == $this->getName()) {
							return $item["type"] == "dir";
						}

						return false;
					}
				)
			) === 1;
	}

	/**
	 * @return File
	 */
	public function getParent(): File {
		if ($this->isRoot()) {
			throw new NoParentAvailableException();
		}
		$path = Paths::normalize(Paths::appendPath($this->getUrl(), ".."));
		$file = new File($path);

		return $file;
	}

	/*========================= FILTER =============================*/
	/**
	 * @param $index
	 *
	 * @throws \LogicException
	 */
	public function removeFilter($index) {
		if (!isset($this->filter[$index])) {
			throw  new LogicException("Filter not found. Index is not set.");
		}
		unset($this->filter[$index]);
		if (isset($this->recursiveFilter[$index])) {
			unset($this->recursiveFilter[$index]);
		}
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
	/*========================= LISTS =============================*/
	/**
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return File[]
	 * @throws DirectoryNotFoundException
	 */
	public function listFiles(bool $recursion = true): array {
		$filter = new class extends Filter {
			public function filter(File $dirOrPath): bool {
				/**
				 * @var File $dirOrPath
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
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return File[]
	 * @throws DirectoryNotFoundException
	 */
	public function listDirectories(bool $recursion = true): array {
		$filter = new class extends Filter {
			public function filter(File $dirOrPath): bool {
				/**
				 * @var File $dirOrPath
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
	 * get all files and directory in current path
	 *
	 * @param bool $recursion recursion
	 *
	 * @return File[]
	 * @throws DirectoryNotFoundException
	 */
	public function listContents(bool $recursion = true): array {
		if (!$this->isDirectory()) {
			throw new DirectoryNotFoundException();
		}
		$a        = [];
		$dirs     = [];
		$contents = $this->manager->listContents($this->getUrl(), $recursion);

		foreach ($contents as $file) {
			$file = Paths::normalize("/".$file["path"]);
			$add= str_replace($this->getAbsolutePath(),"",$file);


			$dirs[] = new File(Paths::appendPath($this->getUrl(), $add));
		}
		foreach ($dirs as $dir) {
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

		return $a;
	}

	public function getAbsolutePath() {
		return Paths::normalize($this->getPath());
	}

	public function getPath() {
		return Paths::parseUrl($this->url)["path"];
	}

	public function getUrl() {
		return $this->url;
	}

	private function isRoot() {
		return $this->getAbsolutePath() == "/";
	}

	/**
	 * get a sub directory of current directory
	 *
	 * @param string $path name of directory
	 *
	 * @return File
	 */
	public function get(string $path): File {
		return new File(Paths::appendPath($this->getUrl(), $path));
	}

	/*========================= FILE =============================*/
	/**
	 * creates a file
	 *
	 * @param string $name    name of file
	 * @param string $content content
	 *
	 * @return File
	 */
	public function createFile(string $name, string $content = ""): File {
		$url = Paths::normalize(Paths::appendPath($this->getUrl(), $name));
		$this->manager->put($url, $content);

		return new File($url);
	}


	/**
	 * get md5 string of file
	 *
	 * @return string
	 */
	public function md5(): string {
		return md5($this->getContent());
	}

	/**
	 * get sha1 string of file
	 *
	 * @return string
	 */
	public function sha1(): string {
		return sha1($this->getContent());
	}

	/**
	 * get file content
	 *
	 * @return string
	 */
	public function getContent(): string {
		return $this->manager->read($this->getUrl());
	}

	/**
	 * @param string $content
	 */
	public function write(string $content) {
		$this->manager->put($this->getUrl(), $content);
	}

	/**
	 * get file extension
	 *
	 * @return string
	 */
	public function getExtension(): string {
		$ex = explode(".", $this->getName());

		return $ex[count($ex) - 1];
	}

	/**
	 * get file size
	 *
	 * @return int
	 */
	public function getSize(): int {
		return $this->manager->getSize($this->getUrl());
	}

	/**
	 * get mimetype of file
	 *
	 * @return string
	 */
	public function getMimeType(): string {
		return $this->manager->getMimetype($this->getUrl());
	}

	/**
	 * get modified time of file
	 *
	 * @return int
	 */
	public function getModifiedTime(): int {
		return $this->manager->getTimestamp($this->getUrl());
	}

	/*========================= OPERATIONS =============================*/

	/**
	 * move file to other directory
	 *
	 * @param File $to new place
	 *
	 * @return $this|File
	 */
	public function move(File $to): File {
		$this->manager->move(
			$this->getUrl(),
			Paths::appendPath($to->getUrl(), $this->getName())
		);

		return new File(Paths::appendPath($this->getUrl(), $this->getName()));
	}
	/*========================= Countable Interface =============================*/
	/**
	 * Count elements of an object
	 *
	 * @link  http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count() {
		try {
			return count($this->listContents(false));
		} catch (DirectoryNotFoundException $e) {
			return 0;
		}
	}
	/*========================= ArrayAccess =============================*/
	/**
	 * Whether a offset exists
	 *
	 * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param mixed $offset <p>
	 *                      An offset to check for.
	 *                      </p>
	 *
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	public function offsetExists($offset) {
		try {
			foreach ($this->listContents(false) as $listContent) {
				if ($listContent->getName() == $offset) {
					return true;
				}
			}
		} catch (DirectoryNotFoundException $e) {
			return false;
		}

		return false;
	}

	/**
	 * Offset to retrieve
	 *
	 * @link  http://php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to retrieve.
	 *                      </p>
	 *
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * Offset to set
	 *
	 * @link  http://php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to assign the value to.
	 *                      </p>
	 * @param mixed $value  <p>
	 *                      The value to set.
	 *                      </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetSet($offset, $value) {
		$thing = $this->get($offset);
		if (!$thing->isFile() && $thing->isDirectory()) {
			throw  new LogicException("Cannot write to directory.");
		}
		/**
		 * @var File $thing
		 */

		if ($thing->isFile()) {
			$thing->write($value);
		} else {
			$this->createFile($offset, $value);
		}
	}

	/**
	 * Offset to unset
	 *
	 * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to unset.
	 *                      </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetUnset($offset) {
		$this->get($offset)->delete();
	}

	/*========================= Iterator =============================*/
	/**
	 * Retrieve an external iterator
	 *
	 * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator() {
		try {
			return new ArrayIterator($this->listContents(false));
		} catch (DirectoryNotFoundException $e) {
			return new ArrayIterator();
		}
	}

	/*========================= Utils =============================*/
	/**
	 * print a file tree
	 *
	 * @param int $int recursions number
	 */
	public function printTree($int = 0) {
		try {
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
		} catch (DirectoryNotFoundException $e) {
		}
	}

	/*========================= Search =============================*/

	/**
	 * @param string $word
	 *
	 * @return File[]
	 * @throws DirectoryNotFoundException
	 */
	public function searchFile(string $word) {
		$index   = $this->addFilter(new Search($word));
		$content = $this->listFiles(true);
		$this->removeFilter($index);

		return $content;
	}


	/**
	 * @param string $word
	 *
	 * @return File[]
	 * @throws DirectoryNotFoundException
	 */
	public function searchDirectory(string $word) {
		$index   = $this->addFilter(new Search($word));
		$content = $this->listDirectories(true);
		$this->removeFilter($index);

		return $content;
	}


	/**
	 * @param $word
	 *
	 * @return File[]
	 * @throws DirectoryNotFoundException
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
	 * @return File[]
	 * @throws DirectoryNotFoundException
	 */
	public function search(string $word) {
		$index   = $this->addFilter(new Search($word));
		$content = $this->listContents(true);
		$this->removeFilter($index);

		return $content;
	}
}