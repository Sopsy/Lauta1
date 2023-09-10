<?php

class cache
{
    public function __construct()
    {
    }

    public function set($key, $var, $ttl = 0)
    {
        return apcu_store($key, $var, $ttl);
    }

    public function get($key)
    {
        return apcu_fetch($key);
    }

    public function exists($key)
    {
        return apcu_exists($key);
    }

    public function del($key)
    {
        return apcu_delete($key);
    }
}
