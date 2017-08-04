<?php

namespace common\io;

use LogicException;

/**
 * Class Paths
 *
 * @package common\io
 */
class Paths {
	/**
	 * Normalize a path
	 *
	 * @param   string $path      path
	 * @param   string $separator separator
	 *
	 * @return  string  normalized path
	 * @see http://stackoverflow.com/a/20545583
	 */
	public static function normalize(string $path, string $separator = '\\/'): string {
		$a          = ($path);
		$path       = str_replace("\\", "/", $path);
		$normalized = preg_replace('#\p{C}+|^\./#u', '', $path);
		$normalized = preg_replace('#/\.(?=/)|^\./|\./$#', '', $normalized);
		$regex      = '#\/*[^/\.]+/\.\.#Uu';
		while (preg_match($regex, $normalized)) {
			$normalized = preg_replace($regex, '', $normalized);
		}
		if (preg_match('#/\.{2}|\.{2}/#', $normalized)) {
			throw new LogicException(
				'Path is outside of the defined root, path: [' . $path . '], resolved: [' . $normalized . ']'
			);
		}
		$path = preg_replace('/([A-Za-z]*?):\/\/(\/*)(.*)/', '$1://$3', trim($normalized, $separator));
		if (strpos($path, "://") === false) {
			$path = "/" . ltrim($path, "/");
		}

		return $path;
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
		$e = explode("/", $path);

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