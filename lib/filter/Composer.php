<?php

namespace common\io\filter;


use common\io\Filter;

class Composer extends Filter {
	function filter($dirOrFile): bool {
		return !preg_match('/vendor/', $dirOrFile) && !preg_match('/composer\.json/', $dirOrFile) && !preg_match('/composer\.lock/', $dirOrFile);
	}
}