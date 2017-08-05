<?php

namespace common\io;

use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memory as CacheStore;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;

/**
 * Class Manager
 *
 * @package common\io
 */
class Manager {
	/**
	 * @var MountManager
	 */
	private static $manager = NULL;
	/**
	 * @var array
	 */
	private static $filesystems = [];

	/**
	 * init default filesystems
	 */
	private static function init() {
		static::$manager = new MountManager(self::$filesystems);
		$local           = new CachedAdapter(new Local(getcwd()), new CacheStore());
		self::addAdapter("local", $local);
		self::addAdapter("file", $local);
	}

	/**
	 * add an adapter
	 *
	 * @param string           $name
	 * @param AdapterInterface $adapter
	 *
	 * @return void
	 */
	public static function addAdapter(string $name, AdapterInterface $adapter) {
		$adapter = new Filesystem($adapter);
		if (static::$manager == NULL && empty(self::$filesystems)) {
			self::init();
		}
		self::$filesystems[$name] = $adapter;
		static::$manager->mountFilesystem($name, $adapter);
	}


	/**
	 * get filesystem manager
	 *
	 * @return MountManager
	 */
	public static function getManager(): MountManager {
		if (static::$manager == NULL) {
			self::init();
		}

		return self::$manager;
	}
}