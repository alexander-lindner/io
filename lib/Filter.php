<?php

namespace common\io;


abstract class Filter {

	/**
	 * Filter constructor.
	 */
	public function __construct() {
	}

	abstract function filter($dirOrFile): bool;
}