<?php

namespace Lazy\DelayQueue;

use Redis;

class RedisProxy
{
    /**
     * Redis namespace
     * @var string
     */
    private static $defaultNamespace = 'delayqueue:';

    /**
     * @var Redis
     */
    private $driver;

    public function __construct(Redis $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Set Redis namespace (prefix) default: delayqueue
     * @param string $namespace
     */
    public static function prefix($namespace)
    {
        if (substr($namespace, -1) !== ':' && $namespace != '') {
            $namespace .= ':';
        }

        self::$defaultNamespace = $namespace;
    }

    /**
     * @return string
     */
    public static function getPrefix()
    {
        return self::$defaultNamespace;
    }

    /**
     * Magic method to handle all function requests and prefix key based
     *
     * @param string $method The name of the method called.
     * @param array $args Array of supplied arguments to the method.
     * @return mixed  Result
     */
    public function __call($method, $args)
    {
        if (is_array($args[0])) {
            foreach ($args[0] as $i => $v) {
                $args[0][$i] = self::$defaultNamespace . $v;
            }
        } else {
            $args[0] = self::$defaultNamespace . $args[0];
        }

        return $this->driver->{$method}(...$args);
    }
}