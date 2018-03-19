<?php

namespace common\io\filter;


use common\io\File;
use common\io\Filter;

class HiddenFiles extends Filter {
	function filter(File $dirOrFile): bool {
		return !preg_match('/(^|\/)\.[^\/\.]/', $dirOrFile);
	}
}