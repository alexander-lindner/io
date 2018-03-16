<?php

namespace common\io\adapters;

interface Adapter {
	public function getRootPath(): string;

	public function getCurrentPath(): string;

	public function getStartPath(): string;
}