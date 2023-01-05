<?php

namespace Zerifas\Supermodel;

/**
 * This trait adds magic getter and setter methods. Whilst useful for rapid
 * development, it it unlikely to be performant in production.
 */
trait AutoAccessorsTrait
{
    public function __call($method, $args)
    {
        $prefix = substr($method, 0, 3);
        if ($prefix !== 'get' && $prefix !== 'set') {
            throw new \Exception("No such method: $method");
        }

        $argCount = count($args);
        $key = strtolower(substr($method, 3, 1)) . substr($method, 4);

        if (!isset($this->$key)) {
            $class = get_class($this);
            throw new \InvalidArgumentException("Property $key does not exist on $class");
        }

        if ($prefix === 'set' && $argCount === 1) {
            $this->$key = $args[0];
            return $this;
        } elseif ($prefix === 'get' && $argCount === 0) {
            return $this->$key;
        }

        $map = function ($arg) {
            if (is_object($arg)) {
                return get_class($arg);
            }

            return gettype($arg);
        };
        $types = implode(', ', array_map($map, $args));
        throw new \InvalidArgumentException("Invalid auto-accessor call: $method($types)");
    }
}
