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
}