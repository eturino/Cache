<?php
/**
 * driver for memcached cache php extension
 */
class EtuDev_Cache_Driver_Memcached implements EtuDev_Cache_Driver {

	/**
	 * @var Memcached
	 */
	protected $lib;

	/**
	 * @static
	 * @return Memcached
	 */
	protected function getLib() {
		if (!$this->lib) {
			static::loadLib();
		}

		return $this->lib;
	}

	/**
	 * @static
	 *
	 */
	protected function loadLib() {
		$this->lib = new Memcached();
		$this->loadServers();
	}


	public function __construct() {
		if (defined('MEMCACHED_SERVERS') && MEMCACHED_SERVERS) {
			$this->parseAndPrepareServers(MEMCACHED_SERVERS);
		}
	}

	static protected $keyprefix;

	static protected function completeKey($key) {
		if (!static::$keyprefix) {
			static::$keyprefix = substr(md5(__FILE__), 0, 3) . '_';
		}

		return static::$keyprefix . $key;
	}

	protected function parseAndPrepareServers($serverString) {
		$parsed = $this->parseServers($serverString);
		if ($parsed) {
			if ($this->lib) { // ya está cargada, agregamos servers a la lib
				$this->addServers($parsed);
			} else {
				$this->servers = $parsed; // no está cargada, los preparamos para cuando se necesiten
			}
		}
	}

	public function parseServers($serverString) {
		$a       = array();
		$servers = explode(',', trim($serverString));
		if ($servers) {
			foreach ($servers as $s) {
				$parts = explode(':', trim($s));
				$h     = $parts[0];
				$p     = $parts[1];
				if ($h && $p) {
					$k     = $h . ':' . $p;
					$a[$k] = array(self::SERVCONF_HOST => $h, self::SERVCONF_PORT => $p);
				}
			}
		}

		return $a;
	}

	protected $servers = array();

	const SERVCONF_HOST = 'h';
	const SERVCONF_PORT = 'p';

	public function addServers($servers = array(array(self::SERVCONF_HOST => 'localhost', self::SERVCONF_PORT => 11211))) {
		$l = $this->getLib();
		foreach ($servers as $s) {
			$h = trim($s[self::SERVCONF_HOST]);
			$p = trim($s[self::SERVCONF_PORT]);
			$k = $h . ':' . $p;
			if (!array_key_exists($k, $this->servers)) {
				$l->addServer($s[self::SERVCONF_HOST], $s[self::SERVCONF_PORT]);
				$this->servers[$k] = $s;
			}
		}
	}

	protected function loadServers() {
		$l = $this->getLib();
		foreach ($this->servers as $s) {
			$l->addServer($s[self::SERVCONF_HOST], $s[self::SERVCONF_PORT]);
		}
	}

	/**
	 * @var EtuDev_Cache_Driver_Memcached
	 */
	static protected $instance;

	/**
	 * @static
	 * @return EtuDev_Cache_Driver_Memcached
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
			$this->enabled = (bool) extension_loaded('memcached');
		}

		return $this->enabled;
	}

	/**
	 * @param string      $key
	 * @param mixed       $value
	 * @param int         $ttl
	 * @param string|null $specific_server_key
	 *
	 * @return bool
	 * @throws EtuDev_Cache_Exception
	 */
	public function set($key, $value, $ttl = 3600, $specific_server_key = null) {
		if (!$this->isEnabled()) {
			return false;
		}
		$l = $this->getLib();
		$this->checkServers();

		$isPreparedForCache = ($value instanceof EtuDev_Interfaces_PreparedForCache);
		if ($isPreparedForCache) {
			/** @var $value EtuDev_Interfaces_PreparedForCache */
			$value->prepareForCache();
		}

		if ($specific_server_key) {
			$ret = $l->setByKey($specific_server_key, static::completeKey($key), $value, $ttl);
		} else {
			$ret = $l->set(static::completeKey($key), $value, $ttl);
		}

		if ($isPreparedForCache) {
			/** @var $value EtuDev_Interfaces_PreparedForCache */
			$value->afterCache();
		}

		return $ret;
	}

	/**
	 * @param string      $key
	 * @param string|null $specific_server_key
	 *
	 * @return mixed|null
	 * @throws EtuDev_Cache_Exception
	 */
	public function get($key, $specific_server_key = null) {
		if (!$this->isEnabled()) {
			return null;
		}
		$l = $this->getLib();
		$this->checkServers();
		if ($specific_server_key) {
			$ret = $l->getByKey($specific_server_key, static::completeKey($key));
		} else {
			$ret = $l->get(static::completeKey($key));
		}

		if (!$ret && $l->getResultCode() == Memcached::RES_NOTFOUND) {
			return null;
		}

		return $ret;
	}


	/**
	 * @return bool
	 * @throws EtuDev_Cache_Exception
	 */
	public function checkServers() {
		$servers = $this->getLib()->getServerList();
		if (!$servers) {
			throw new EtuDev_Cache_Exception('no server in the memcache client');
		}

		return true;
	}

	public function flush() {
		if (!$this->isEnabled()) {
			return false;
		}

		return $this->getLib()->flush();
	}

	public function delete($k) {
		if (!$this->isEnabled()) {
			return false;
		}

		return $this->getLib()->delete(static::completeKey($k));
	}

	public function getServerStatus() {
		if (!$this->isEnabled()) {
			return false;
		}

		return $this->getLib()->getStats();
	}

}
