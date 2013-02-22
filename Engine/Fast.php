<?php

/**
 * Class EtuDev_Cache_Engine_Fast
 *
 * engine to be used for fast cache
 *
 * 1.XCache
 * 2.APC
 *
 *
 */
class EtuDev_Cache_Engine_Fast extends EtuDev_Cache_Engine {


	/**
	 * @var EtuDev_Cache_Engine_Fast
	 */
	static protected $instance;

	/**
	 * @static
	 * @return EtuDev_Cache_Engine_Fast
	 */
	static public function getInstance() {
		if (!static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}


	protected function loadDriver() {

		if (extension_loaded('xcache')) {
			$this->driver = EtuDev_Cache_Driver_XCache::getInstance();
		} elseif (extension_loaded('apc')) {
			$this->driver = EtuDev_Cache_Driver_APC::getInstance();
		}

	}

	/**
	 * @param string $prefix
	 *
	 * @return bool
	 */
	public function deleteAllPrefix($prefix) {
		if (!$this->loaded) {
			$this->loadDriver();
		}

		if (!$this->driver) {
			return false;
		}

		/** @var $d EtuDev_Cache_DriverWithPrefix */
		$d = $this->driver;
		return $d->deleteAllPrefix($prefix);
	}

}