<?php

namespace common\io\filter;


use common\io\File;
use common\io\Filter;
use common\io\Utils;

class SearchContent extends Filter {
	protected $word;

	public function __construct($word) {
		$this->word = $word;
	}

	function filter(File $dirOrFile): bool {
		return $dirOrFile->isFile() && Utils::strcontains(strtolower($dirOrFile->getContent()), strtolower($this->word));
	}
}