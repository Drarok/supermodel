<?php

namespace Zerifas\Supermodel\Cache;

class FileCache implements CacheInterface
{
    private $pathname;
    private $dirty = false;
    private $data = [];

    public function __construct(string $pathname)
    {
        $this->pathname = $pathname;

        if (is_readable($pathname)) {
            $this->data = unserialize(file_get_contents($pathname));
        }
    }

    public function __destruct()
    {
        // TODO: Move away from magic methods.
        if ($this->dirty) {
            file_put_contents($this->pathname, serialize($this->data));
        }
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function set(string $key, $value)
    {
        $this->dirty = true;
        $this->data[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function delete(string $key)
    {
        $this->dirty = true;
        unset($this->data[$key]);
    }

    public function clear()
    {
        $this->dirty = true;
        $this->data = [];
    }
}
