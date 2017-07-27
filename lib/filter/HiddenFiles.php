<?php

namespace common\io\filter;


use common\io\Filter;

class HiddenFiles extends Filter {
	function filter($dirOrFile): bool {
		return !preg_match('/(^|\/)\.[^\/\.]/', $dirOrFile);
	}
}