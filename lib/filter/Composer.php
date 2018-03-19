<?php

namespace common\io\filter;


use common\io\File;
use common\io\Filter;

class Composer extends Filter {
	function filter(File $dirOrFile): bool {
		return !preg_match('/vendor/', $dirOrFile->getAbsolutePath()) &&
			!preg_match('/composer\.json/', $dirOrFile->getAbsolutePath()) &&
			!preg_match('/composer\.lock/', $dirOrFile->getAbsolutePath());
	}
}