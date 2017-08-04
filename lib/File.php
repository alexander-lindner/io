<?php

namespace common\io;

use League\Flysystem\Filesystem;

/**
 * Class File
 *
 * @package common\io
 */
class File {
	/**
	 * @var Filesystem
	 */
	private $file;
	/**
	 * @var string
	 */
	private $filePath;
	/**
	 * @var Directory
	 */
	private $directory = NULL;

	/**
	 * file constructor.
	 *
	 * @param string    $file
	 * @param Directory $directory
	 */
	public function __construct(string $file, Directory $directory) {
		$this->directory = $directory;
		$this->filePath  = $file;
		$this->file      = $this->directory->getDir();
	}

	/**
	 * get a file instance
	 *
	 * @param string $path file path
	 *
	 * @return File
	 */
	public static function get(string $path): File {
		return new self(Paths::getFile($path), new Directory(Paths::getPath($path)));
	}

	/**
	 * check if this file exists
	 *
	 * @return bool
	 */
	public function isFile(): bool {
		return $this->file->has($this->getFullPath());
	}

	/**
	 * get current path with protocol
	 *
	 * @return string
	 */
	public function getFullPath(): string {
		return Paths::normalize(Paths::makePath($this->directory->getProtocol(), $this->getPath()));
	}

	/**
	 * get file path
	 *
	 * @return string
	 */
	public function getPath(): string {
		return Paths::normalize($this->directory->getPath() . "/" . $this->filePath);
	}

	/**
	 * check if this file is readable
	 *
	 * @return bool
	 */
	public function isReadable(): bool {
		return is_string($this->file->read($this->getFullPath()));
	}

	/**
	 * check if this file is writable
	 *
	 * @return bool
	 */
	public function isWritable(): bool {
		return $this->file->getVisibility(
				$this->getFullPath()
			) == "public";
	}

	/**
	 * get file path
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->getPath();
	}

	/**
	 * get file size
	 *
	 * @return int
	 */
	public function getSize(): int {
		return $this->file->getSize($this->getFullPath());
	}

	/**
	 * get mimetype of file
	 *
	 * @return string
	 */
	public function getMimeType(): string {
		return $this->file->getMimetype($this->getFullPath());
	}

	/**
	 * get modified time of file
	 *
	 * @return int
	 */
	public function getModifiedTime(): int {
		return $this->file->getTimestamp($this->getFullPath());
	}

	/**
	 * get file extension
	 *
	 * @return string
	 */
	public function getExtension(): string {
		$e = explode(".", $this->getName());

		return $e[count($e) - 1];
	}

	/**
	 * get file name
	 *
	 * @return string
	 */
	public function getName(): string {
		$e = explode("/", $this->filePath);

		return $e[count($e) - 1];
	}

	/**
	 * get file content
	 *
	 * @return string
	 */
	public function read(): string {
		return $this->getContent();
	}

	/**
	 * get file content
	 *
	 * @return string
	 */
	public function getContent(): string {
		return $this->file->read($this->getFullPath());
	}

	/**
	 * rename file
	 *
	 * @param string $newName new file name
	 *
	 * @return bool
	 */
	public function rename(string $newName): bool {
		return $this->file->rename($this->getFullPath(), $this->directory->getPath() . "/" . $newName);
	}

	/**
	 * move file to other directory
	 *
	 * @param Directory $to new place
	 *
	 * @return $this|File
	 */
	public function move(Directory $to): File {
		$this->file->move(
			$this->getFullPath(),
			Paths::makePath($to->getProtocol(), $to->getPath() . "/" . $this->getName())
		);

		return new File($to->getPath() . "/" . $this->getName(), $to);
	}

	/**
	 * delete this file
	 *
	 * @return Directory
	 */
	public function delete(): Directory {
		$this->file->delete($this->getFullPath());

		return $this->getDirectory();
	}

	/**
	 * get directory object of file
	 *
	 * @return Directory
	 */
	public function getDirectory(): Directory {
		if (is_object($this->directory) && !is_null($this->directory)) {
			return $this->directory;
		} else {
			return $this->directory = new Directory($this->getParentDirectory());
		}
	}

	/**
	 * get directory string of file
	 *
	 * @return string
	 */
	public function getParentDirectory(): string {
		$ex = explode("/", $this->directory->getProtocol() . $this->getPath());
		unset($ex[count($ex) - 1]);

		return implode("/", $ex);
	}

	/**
	 * copy this file to an other place
	 *
	 * @param Directory $to other place
	 *
	 * @return File
	 */
	public function copy(Directory $to): File {
		$this->file->copy(
			$this->getFullPath(),
			$to->getFullPath() . "/" . $this->getName()
		);

		return new File($this->getName(), $to);
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
	 * check if this dir exists
	 *
	 * @return bool
	 */
	public function isDirectory(): bool {
		return false;
	}

	public function write(string $content) {
		$this->file->put($this->getFullPath(), $content);
	}
}