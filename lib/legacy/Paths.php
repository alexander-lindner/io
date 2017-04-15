<?php

namespace common\io\legacy;

use LogicException;

/**
 * Class Paths
 *
 * @package common\io\legacy
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
	public static function normalize($path, $separator = '\\/') {
		$path       = str_replace("\\", "/", $path);
		$normalized = preg_replace('#\\p{C}+|^\\./#u', '', $path);
		$normalized = preg_replace('#/\\.(?=/)|^\\./|\\./$#', '', $normalized);
		$regex      = '#\\/*[^/\\.]+/\\.\\.#Uu';
		while (preg_match($regex, $normalized)) {
			$normalized = preg_replace($regex, '', $normalized);
		}
		if (preg_match('#/\\.{2}|\\.{2}/#', $normalized)) {
			throw new LogicException(
				'Path is outside of the defined root, path: [' . $path . '], resolved: [' . $normalized . ']'
			);
		}

		return trim($normalized, $separator);
	}

	/**
	 * get path without file name
	 *
	 * @param string $path file path
	 *
	 * @return string
	 */
	public static function getPath($path) {
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
	public static function getFile($path) {
		$e = explode("/", $path);

		return $e[count($e) - 1];
	}

	/**
	 * @param $path
	 *
	 * @deprecated
	 *
	 * @return string
	 */
	public static function getProtocol($path) {
		$protocols = "";
		foreach (Manager::getFilesystems() as $key => $filesystem) {
			$protocols .= $key . ",";
		}
		$protocols = rtrim($protocols, ",");
		$regex     = "/([{$protocols}]*)(:\\/\\/)/m";
		if (!preg_match($regex, $path)) {
			$path = "file://{$path}";
		}

		return explode("://", $path)[0] . "://";
	}

	/**
	 * @param $path
	 *
	 * @deprecated
	 *
	 * @return mixed
	 */
	public static function getPathWithoutProtocol($path) {
		$protocols = "";
		foreach (Manager::getFilesystems() as $key => $filesystem) {
			$protocols .= $key . ",";
		}
		$protocols = rtrim($protocols, ",");
		$regex     = "/([{$protocols}]*)(:\\/)/m";
		if (!preg_match($regex, $path)) {
			$path = "file:/{$path}";
		}

		return preg_split("/([a-zA-Z]*)(:\\/)/m", $path)[1];
	}

	/**
	 * make a full featured path
	 *
	 * @param string $protocol protocol
	 * @param string $name     name
	 *
	 * @return string
	 */
	public static function makePath($protocol, $name) {
		return $protocol . "://" . $name;
	}
}