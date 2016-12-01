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
        $argCount = count($args);
        $key = strtolower(substr($method, 3, 1)) . substr($method, 4);

        if ($prefix === 'set' && $argCount === 1) {
            $this->$key = $args[0];
            return $this;
        } elseif ($prefix === 'get' && $argCount === 0) {
            if (isset($this->$key)) {
                return $this->$key;
            }
        }

        throw new \Exception("No such method: $method");
    }
}
