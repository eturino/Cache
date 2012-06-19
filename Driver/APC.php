<?php

/**
 * driver for APC cache php extension
 */
class EtuDev_Cache_Driver_APC implements EtuDev_Cache_Driver {

	/**
	 * @var EtuDev_Cache_Driver_APC
	 */
	static protected $instance;

	/**
	 * @static
	 * @return EtuDev_Cache_Driver_APC
	 */
	static public function getInstance() {
		if (!static::$instance) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * @var null|boolean
	 */
	protected $enabled = null;

	/**
	 * @return boolean
	 */
	public function isEnabled() {
		if (is_null($this->enabled)) {
			$this->enabled = (bool) extension_loaded('apc');
		}
		return $this->enabled;
	}

	public function exists($key) {
		return apc_exists($key);
	}


	/**
	 * @param string      $key
	 * @param mixed       $value
	 * @param int         $ttl
	 *
	 * @return bool
	 */
	public function set($key, $value, $ttl = 3600) {
		if (!$this->isEnabled()) {
			return false;
		}

		$isPreparedForCache = ($value instanceof EtuDev_Interfaces_PreparedForCache);
		if ($isPreparedForCache) {
			/** @var $value EtuDev_Interfaces_PreparedForCache */
			$value->prepareForCache();
		}

		$ret = apc_store($key, $value, $ttl);

		if ($isPreparedForCache) {
			/** @var $value EtuDev_Interfaces_PreparedForCache */
			$value->afterCache();
		}

		return $ret;
	}

	/**
	 * @param string      $key
	 *
	 * @return mixed|null
	 * @throws EtuDev_Cache_Exception
	 */
	public function get($key) {
		if (!$this->isEnabled()) {
			return null;
		}
		$x = apc_fetch($key, $success);
//		//ya no usamos el cache array object
//		/** @var $x EtuDev_Cache_ArrayObject */
//		if ($x instanceOf EtuDev_Cache_ArrayObject) {
//			return $x->getArrayCopy();
//		}
		return $success ? $x : null;
	}

	public function flush() {
		if (!$this->isEnabled()) {
			return false;
		}
		return apc_clear_cache('user');
	}

	public function delete($k) {
		if (!$this->isEnabled()) {
			return false;
		}
		return apc_delete($k);
	}

	public function getServerStatus() {
		if (!$this->isEnabled()) {
			return false;
		}
		return array('sma' => apc_sma_info(), 'cache_info' => apc_cache_info('user'));
	}

	static public function deletePrefix($prefix) {
		$toDelete = static::getAllKeysPrefix($prefix);
		return apc_delete($toDelete);
	}

	/**
	 * @static
	 *
	 * @param $prefix
	 *
	 * @return APCIterator
	 */
	static public function getAllKeysPrefix($prefix) {
		return new APCIterator('user', "/^$prefix/", APC_ITER_VALUE);
	}

}