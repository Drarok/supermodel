<?php

namespace Zerifas\Supermodel\Cache;

interface CacheInterface
{
    public function has(string $key): bool;
    public function set(string $key, $value);
    public function get(string $key, $default = null);
    public function delete(string $key);
    public function clear();
}
