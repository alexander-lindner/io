<?php

namespace common\io;

use League\Flysystem\Util;

/**
 * Class Paths
 *
 * @package common\io
 */
class Paths {
	/**
	 * Normalize a path
	 *
	 * @param   string $path path
	 *
	 * @return  string  normalized path
	 */
	public static function normalize(string $path): string {
		if (!(false === strpos($path, '://'))) {
			$ex      = explode("://", $path);
			$protocl = $ex[0];

			return $protocl . "://" . Util::normalizePath("/" . ltrim($ex[1], "/"));
		}

		return "/" . ltrim(Util::normalizePath($path), "/");
	}

	/**
	 * get path without file name
	 *
	 * @param string $path file path
	 *
	 * @return string
	 */
	public static function getPath(string $path): string {
		$e = explode("/", $path);
		unset($e[count($e) - 1]);

		return implode("/", $e);
	}

	/**
	 * get file name form full file path
	 *
	 * @param string $path file path
	 *
	 * @return mixed
	 */
	public static function getFile(string $path): string {
		$e = explode("/", rtrim($path, "/"));

		return $e[count($e) - 1];
	}

	/**
	 * make a full featured path
	 *
	 * @param string $protocol protocol
	 * @param string $name     name
	 *
	 * @return string
	 */
	public static function makePath(string $protocol, string $name): string {
		return $protocol . "://" . $name;
	}

	public static function trim($dirPath) {
		return "/" . rtrim(ltrim($dirPath, '/'), '/') . '/';
	}
}