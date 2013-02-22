<?php

abstract class EtuDev_Cache_Engine {

	/**
	 * @var EtuDev_Cache_Driver
	 */
	protected $driver;

	protected $loaded = false;

	public function __construct() {
		$this->loadDriver();
		$this->loaded = true;
	}

	abstract protected function loadDriver();

	/**
	 * @return bool
	 */
	public function isEnabled() {
		if (!$this->loaded) {
			$this->loadDriver();
		}

		if (!$this->driver) {
			return false;
		}

		return $this->driver->isEnabled();
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 *
	 * @return bool|mixed
	 */
	public function set($key, $value, $ttl = 3600) {
		if (!$this->loaded) {
			$this->loadDriver();
		}

		if (!$this->driver) {
			return false;
		}

		return $this->driver->set($key, $value, $ttl);
	}

	/**
	 * @param string $key
	 *
	 * @return bool|mixed
	 */
	public function get($key) {
		if (!$this->loaded) {
			$this->loadDriver();
		}

		if (!$this->driver) {
			return false;
		}

		return $this->driver->get($key);
	}


	/**
	 * @return mixed
	 */
	public function flush() {
		if (!$this->loaded) {
			$this->loadDriver();
		}

		if (!$this->driver) {
			return false;
		}

		return $this->driver->flush();
	}

	/**
	 * @param string $key
	 *
	 * @return bool|mixed
	 */
	public function delete($key) {
		if (!$this->loaded) {
			$this->loadDriver();
		}

		if (!$this->driver) {
			return false;
		}

		return $this->driver->delete($key);
	}


}