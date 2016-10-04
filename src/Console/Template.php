<?php

namespace Zerifas\Supermodel\Console;

class Template
{
    protected static $path = '';

    protected $name;
    protected $data = [];

    public static function setPath($path)
    {
        static::$path = $path;
    }

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function set($key, $value = null)
    {
        if ($value === null && is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }

    public function render(array $data = null)
    {
        if ($data !== null) {
            $this->set($data);
        }
        extract($this->data);
        require static::$path . '/' . $this->name . '.template.php';
    }
}
