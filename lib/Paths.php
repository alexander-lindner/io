<?php
/**
 * Created by PhpStorm.
 * User: alexander
 * Date: 30.03.2017
 * Time: 11:08
 */

namespace common\libs\io;


use LogicException;

class Paths {
	/**
	 * Normalize path
	 *
	 * @param   string $path
	 * @param   string $separator
	 *
	 * @return  string  normalized path
	 * @see http://stackoverflow.com/a/20545583
	 */
	public static function normalize($path, $separator = '\\/') {
		$normalized = preg_replace('#\p{C}+|^\./#u', '', $path);
		$normalized = preg_replace('#/\.(?=/)|^\./|\./$#', '', $normalized);
		$regex = '#\/*[^/\.]+/\.\.#Uu';
		while (preg_match($regex, $normalized)) {
			$normalized = preg_replace($regex, '', $normalized);
		}
		if (preg_match('#/\.{2}|\.{2}/#', $normalized)) {
			throw new LogicException('Path is outside of the defined root, path: [' . $path . '], resolved: [' . $normalized . ']');
		}
		return trim($normalized, $separator);
	}

	public static function getPath($path) {
		$e = explode("/", $path);
		unset($e[count($e) - 1]);
		return implode("/", $e);
	}

	public static function getFile($path) {
		$e = explode("/", $path);
		return $e[count($e) - 1];
	}
}