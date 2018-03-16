<?php

namespace common\io;


use Closure;

class Utils {
	public static function isClosure($t) {
		return is_object($t) && ($t instanceof Closure);
	}

	public static function strcontains($haystack, $needle) {
		return strpos($haystack, $needle) !== false;
	}

	public static function startsWith($haystack, $needle) {
		return $haystack[0] === $needle[0] ? strncmp($haystack, $needle, strlen($needle)) === 0 : false;
	}
}