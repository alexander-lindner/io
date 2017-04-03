<?php

namespace common\libs\io;

use League\Flysystem\Filesystem;

class file {
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
	 * @param Directory $parent
	 */
	public function __construct($file, $parent) {
		$this->directory = $parent;
		$this->filePath = $file;

		$this->file = $parent->getDir();
	}

	public function isFile() {
		return $this->file->has($this->filePath);
	}

	public function isReadable() {
		return is_string($this->file->read($this->filePath));
	}

	public function isWritable() {
		return $this->file->getVisibility($this->filePath) == "public";
	}

	public function __toString() {
		return $this->getPath();
	}

	public function getPath() {
		return Paths::normalize($this->filePath);
	}

	public function getContent() {
		return $this->file->read($this->filePath);
	}

	public function getSize() {
		return $this->file->getSize($this->filePath);
	}

	public function getMimeType() {
		return $this->file->getMetadata($this->filePath)["type"];
	}

	public function getModifiedTime() {

		return $this->file->getTimestamp($this->filePath);
	}

	public function getName() {
		$e = explode("/", $this->filePath);

		return $e[count($e) - 1];
	}

	public function getExtension() {
		$e = explode(".", $this->getName());

		return $e[count($e) - 1];
	}

	public function read() {
		return $this->getContent();
	}

	public function getDirectory() {
		if (is_object($this->directory) && !is_null($this->directory)) {
			return $this->directory;
		} else {
			return $this->directory = new Directory($this->getParentDirectory());
		}
	}

	public function rename($newName) {
		return $this->file->rename($this->filePath, $newName);
	}

	/**
	 * @param Directory $to
	 */
	public function move($to) {
		$this->file->rename($this->filePath, $to->getPath() . "/" . $this->getName());
	}

	public function md5() {
		return md5($this->file->read($this->filePath));
	}

	public function sha1() {
		return sha1($this->file->read($this->filePath));
	}

	public function getParentDirectory() {
		$ex = explode("/", $this->getPath());
		unset($ex[count($ex) - 1]);
		return implode("/", $ex);
	}

	public static function get($path) {
		return new self(Paths::getFile($path),new Directory(Paths::getPath($path)));
	}
}