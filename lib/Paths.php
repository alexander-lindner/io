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
			$ex = explode("://", $path);

			return $ex[0] . "://" . "/" . ltrim(Util::normalizePath("/" . ltrim($ex[1], "/")));
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

	public static function appendPath($url, $path) {
		$parts = self::parseUrl($url);
		if (!isset($parts["path"])) {
			$parts["path"] = "";
		}
		$parts["path"] .= "/" . $path;

		return self::unparseUrl($parts);
	}

	/**
	 * @param $parsed_url
	 *
	 * @return string
	 * @see http://php.net/manual/de/function.parse-url.php#106731
	 */
	protected static function unparseUrl($parsed_url) {
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
		$pass     = ($user || $pass) ? "$pass@" : '';
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

		return "$scheme$user$pass$host$port$path$query$fragment";
	}

	public static function makeUrl($fragment, $default) {
		$parts = self::parseUrl($fragment);
		foreach (array_keys($default) as $key) {
			$parts[$key] = $part ?? $default[$key];
		}

		return self::unparseUrl($parts);

	}

	public static function parseUrl($url) {
		if (($parts = parse_url($url)) === false) {
			$split = explode("://", $url);

				return ["scheme" => $split[0], "path" => $split[1]];

		} else {
			return $parts;
		}
	}
}