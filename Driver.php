<?php

interface EtuDev_Cache_Driver
{

    /**
     * @abstract
     * @return boolean
     */
    function isEnabled();

    /**
     * @abstract
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return mixed
     */
    function set($key, $value, $ttl = 3600);

    /**
     * @abstract
     *
     * @param string $key
     *
     * @return mixed
     */
    function get($key);

    /**
     * @abstract
     * @return mixed
     */
    function flush();

    /**
     * @abstract
     *
     * @param $key
     *
     * @return mixed
     */
    function delete($key);
}