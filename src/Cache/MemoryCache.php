<?php

namespace Zerifas\Supermodel\Cache;

class MemoryCache implements CacheInterface
{
    protected $data = [];

    public function __construct()
    {
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function delete(string $key)
    {
        unset($this->data[$key]);
    }

    public function clear()
    {
        $this->data = [];
    }
}
