<?php

/**
 * driver for XCache cache php extension
 */
class EtuDev_Cache_Driver_XCache implements EtuDev_Cache_Driver {

	/**
	 * @var EtuDev_Cache_Driver_XCache
	 */
	static protected $instance;

	/**
	 * @static
	 * @return EtuDev_Cache_Driver_XCache
	 */
	static public function getInstance() {
		if (!static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Available options
	 *
	 * =====> (string) user :
	 * xcache.admin.user (necessary for the clean() method)
	 *
	 * =====> (string) password :
	 * xcache.admin.pass (clear, not MD5) (necessary for the clean() method)
	 *
	 */
	public function __construct() {
		if (ini_get('xcache.admin.enable_auth')) {
			if (defined('XCACHE_ADMIN_USER')) {
				$this->admin_user = XCACHE_ADMIN_USER;
			} elseif (class_exists('EtuDev_Zend_Util_App')) {
				$u = EtuDev_Zend_Util_App::getZFConfig('xcache.admin.user');
				if ($u) {
					$this->admin_user = $u;
				}
			}

			if (defined('XCACHE_ADMIN_PASS')) {
				$this->admin_pass = XCACHE_ADMIN_PASS;
			} elseif (class_exists('EtuDev_Zend_Util_App')) {
				$p = EtuDev_Zend_Util_App::getZFConfig('xcache.admin.pass');
				if ($p) {
					$this->admin_pass = $p;
				}
			}
		}
	}

	/**
	 * Available options
	 *
	 * =====> (string) user :
	 * xcache.admin.user (necessary for the clean() method)
	 *
	 * =====> (string) password :
	 * xcache.admin.pass (clear, not MD5) (necessary for the clean() method)
	 *
	 * @var array available options
	 */
	protected $admin_user;
	protected $admin_pass;

	/**
	 * @param $u
	 *
	 * @return $this
	 */
	public function setAdminUser($u) {
		$this->admin_user = $u;
		return $this;
	}

	/**
	 * @param $p
	 *
	 * @return $this
	 */
	public function setAdminPass($p) {
		$this->admin_pass = $p;
		return $this;
	}


	static protected $keyprefix;

	static protected function completeKey($key) {
		if (!static::$keyprefix) {
			static::$keyprefix = substr(md5(__FILE__), 0, 3) . '_';
		}

		return static::$keyprefix . $key;
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
			$this->enabled = (bool) extension_loaded('xcache');
		}

		return $this->enabled;
	}

	public function exists($key) {
		return xcache_isset(static::completeKey($key));
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

		//serialize because you cannot store objects into xcache
		$v = serialize($value);

		$ret = xcache_set(static::completeKey($key), $v, $ttl);

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
		$k = static::completeKey($key);
		if (xcache_isset($k)) {
			$x = xcache_get($k);
			//serialize because you cannot store objects into xcache
			return unserialize($x);
		}
		return null;
	}

	public function flush() {
		if (!$this->isEnabled()) {
			return false;
		}

		if (ini_get('xcache.admin.enable_auth')) {
			// Necessary because xcache_clear_cache() need basic authentification
			$backup = array();
			if (isset($_SERVER['PHP_AUTH_USER'])) {
				$backup['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
			}
			if (isset($_SERVER['PHP_AUTH_PW'])) {
				$backup['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];
			}
			if ($this->admin_user) {
				$_SERVER['PHP_AUTH_USER'] = $this->admin_user;
			}
			if ($this->admin_pass) {
				$_SERVER['PHP_AUTH_PW'] = $this->admin_pass;
			}
		}

		$cnt = xcache_count(XC_TYPE_VAR);
		for ($i = 0; $i < $cnt; $i++) {
			xcache_clear_cache(XC_TYPE_VAR, $i);
		}

		if (ini_get('xcache.admin.enable_auth')) {
			if (isset($backup['PHP_AUTH_USER'])) {
				$_SERVER['PHP_AUTH_USER'] = $backup['PHP_AUTH_USER'];
				$_SERVER['PHP_AUTH_PW']   = $backup['PHP_AUTH_PW'];
			}
		}

		return true;
	}

	public function delete($k) {
		if (!$this->isEnabled()) {
			return false;
		}

		return xcache_unset(static::completeKey($k));
	}

	public function getServerStatus() {
		if (!$this->isEnabled()) {
			return false;
		}


		if (ini_get('xcache.admin.enable_auth')) {
			// Necessary because xcache_clear_cache() need basic authentification
			$backup = array();
			if (isset($_SERVER['PHP_AUTH_USER'])) {
				$backup['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
			}
			if (isset($_SERVER['PHP_AUTH_PW'])) {
				$backup['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];
			}
			if ($this->admin_user) {
				$_SERVER['PHP_AUTH_USER'] = $this->admin_user;
			}
			if ($this->admin_pass) {
				$_SERVER['PHP_AUTH_PW'] = $this->admin_pass;
			}
		}

		$res = array(XC_TYPE_VAR => xcache_info(XC_TYPE_VAR, -1), XC_TYPE_PHP => xcache_info(XC_TYPE_PHP, -1));

		if (ini_get('xcache.admin.enable_auth')) {
			if (isset($backup['PHP_AUTH_USER'])) {
				$_SERVER['PHP_AUTH_USER'] = $backup['PHP_AUTH_USER'];
				$_SERVER['PHP_AUTH_PW']   = $backup['PHP_AUTH_PW'];
			}
		}


		return $res;
	}

	static public function deletePrefix($prefix) {
		return xcache_unset_by_prefix(static::completeKey($prefix));
	}

}
