<?php

namespace common\io\filter;


use common\io\File;
use common\io\Filter;
use common\io\Utils;

class Search extends Filter {
	protected $word;

	public function __construct($word) {
		$this->word = $word;
	}

	function filter(File $dirOrFile): bool {
		return Utils::strcontains(strtolower($dirOrFile->getName()), strtolower($this->word));
	}
}