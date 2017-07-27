<?php

namespace common\io;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class FilterArray implements ArrayAccess, Countable, IteratorAggregate {
	private $filter = [];
	private $counter;

	public function __construct() {
		$this->counter = (int)-1;
	}

	public function offsetExists($offset) {
		return isset($this->filter[$offset]) && !is_null($this->filter[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->filter[$offset]) ? $this->filter[$offset] : NULL;
	}

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$offset = $this->counter++;
		}
		$this->filter[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->filter[$offset]);
	}

	public function count() {
		return count($this->filter);
	}

	public function getIterator() {
		return new ArrayIterator($this->filter);
	}

	/**
	 * @return int
	 */
	public function getCounter(): int {
		return $this->counter;
	}
}