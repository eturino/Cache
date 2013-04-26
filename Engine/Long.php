<?php

/**
 * Class EtuDev_Cache_Engine_Long
 *
 * engine to be used for long cache
 *
 * 1.Memcached
 * 2.XCache
 * 3.APC
 *
 *
 */
class EtuDev_Cache_Engine_Long extends EtuDev_Cache_Engine
{


    /**
     * @var EtuDev_Cache_Engine_Long
     */
    static protected $instance;

    /**
     * @static
     * @return EtuDev_Cache_Engine_Long
     */
    static public function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function loadDriver()
    {

        if (extension_loaded('memcached')) {
            $this->driver = EtuDev_Cache_Driver_Memcached::getInstance();
        } elseif (extension_loaded('xcache')) {
            $this->driver = EtuDev_Cache_Driver_XCache::getInstance();
        } elseif (extension_loaded('apc')) {
            $this->driver = EtuDev_Cache_Driver_APC::getInstance();
        }

    }


}